<?php
// Booking form
require_once __DIR__ . '/../includes/functions.php';
require_login('user');

$homestayId = (int)($_GET['homestay_id'] ?? $_POST['homestay_id'] ?? 0);
$roomId = (int)($_GET['room_id'] ?? $_POST['room_id'] ?? 0);
$fallbackRoomIndex = max(0, (int)($_GET['fallback_room'] ?? $_POST['fallback_room'] ?? 0));
$checkIn = trim($_GET['check_in'] ?? $_POST['check_in'] ?? '');
$checkOut = trim($_GET['check_out'] ?? $_POST['check_out'] ?? '');
$guests = max(1, (int)($_GET['guests'] ?? $_POST['guests'] ?? 1));

$hs = $conn->prepare('SELECT * FROM homestays WHERE id = ? AND is_active = 1');
$hs->execute([$homestayId]);
$homestay = $hs->fetch();
if (!$homestay && $homestayId > 0) {
    $hs = $conn->prepare('SELECT * FROM homestays WHERE id = ?');
    $hs->execute([$homestayId]);
    $homestay = $hs->fetch();
}

$isFallbackRoom = false;
$room = null;
if ($roomId > 0) {
    $rm = $conn->prepare('SELECT * FROM rooms WHERE id = ? AND homestay_id = ? AND is_active = 1');
    $rm->execute([$roomId, $homestayId]);
    $room = $rm->fetch();
    if (!$room) {
        $rm = $conn->prepare('SELECT * FROM rooms WHERE id = ? AND homestay_id = ?');
        $rm->execute([$roomId, $homestayId]);
        $room = $rm->fetch();
    }
    if (!$room) {
        $rm = $conn->prepare('SELECT * FROM rooms WHERE id = ?');
        $rm->execute([$roomId]);
        $room = $rm->fetch();
        if ($room) {
            $homestayId = (int)$room['homestay_id'];
            $hs = $conn->prepare('SELECT * FROM homestays WHERE id = ?');
            $hs->execute([$homestayId]);
            $homestay = $hs->fetch();
        }
    }
} elseif ($fallbackRoomIndex > 0) {
    $fallbackRooms = fallback_public_rooms();
    if (isset($fallbackRooms[$fallbackRoomIndex - 1])) {
        $room = $fallbackRooms[$fallbackRoomIndex - 1];
        $room['id'] = 0;
        $isFallbackRoom = true;
    }
}

if (!$homestay && $isFallbackRoom) {
    $fallbackHomestay = $conn->query('SELECT * FROM homestays ORDER BY is_active DESC, id ASC LIMIT 1')->fetch();
    if ($fallbackHomestay) {
        $homestay = $fallbackHomestay;
        $homestayId = (int)$fallbackHomestay['id'];
    } else {
        $homestay = [
            'id' => $homestayId ?: 1,
            'title' => 'Sonam Homestay',
            'city' => 'Khecheopalri',
            'cover_image' => '',
            'is_synthetic' => true,
        ];
    }
}

if (!$homestay || !$room) {
    set_flash('error', 'Invalid room selected.');
    redirect(BASE_URL . 'pages/search.php');
}

$userStmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
$userStmt->execute([$_SESSION['user_id']]);
$user = $userStmt->fetch();

$error = '';
$notice = '';

function valid_booking_date($date)
{
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt && $dt->format('Y-m-d') === $date;
}

function booking_validation_error($checkIn, $checkOut, $guests, $room, $roomId, $isFallbackRoom = false)
{
    $today = date('Y-m-d');
    if ($checkIn === '' || $checkOut === '') {
        return 'Please select both check-in and check-out dates before continuing to payment.';
    }
    if (!valid_booking_date($checkIn) || !valid_booking_date($checkOut)) {
        return 'Please use valid booking dates.';
    }
    if ($checkIn < $today) {
        return 'Check-in date cannot be in the past.';
    }
    if ($checkOut <= $checkIn) {
        return 'Check-out date must be after check-in date.';
    }
    if ($guests < 1 || $guests > (int)$room['max_guests']) {
        return 'Please choose between 1 and ' . (int)$room['max_guests'] . ' guest' . ((int)$room['max_guests'] > 1 ? 's' : '') . ' for this room.';
    }
    if (!$isFallbackRoom && !is_room_available($roomId, $checkIn, $checkOut)) {
        return 'Room is not available for these dates. Please choose another date range.';
    }
    return '';
}

function create_pending_booking($homestayId, $roomId, $room, $homestay, $user, $checkIn, $checkOut, $guests, $guestName = '', $guestEmail = '', $guestPhone = '', $special = '', $isFallbackRoom = false)
{
    $guestName = trim($guestName) ?: ($user['full_name'] ?? '');
    $guestEmail = trim($guestEmail) ?: ($user['email'] ?? '');
    $guestPhone = trim($guestPhone) ?: ($user['phone'] ?? '');
    $nights = nights_between($checkIn, $checkOut);
    $subtotal = (float)$room['price_per_night'] * $nights;
    $cleaning = (float)$room['cleaning_fee'];
    $service = round($subtotal * 0.05, 2);
    $total = $subtotal + $cleaning + $service;

    $_SESSION['pending_booking'] = [
        'homestay_id' => $homestayId,
        'room_id' => $roomId,
        'check_in' => $checkIn,
        'check_out' => $checkOut,
        'guests' => $guests,
        'guest_name' => $guestName,
        'guest_email' => $guestEmail,
        'guest_phone' => $guestPhone,
        'special_requests' => trim($special),
        'nights' => $nights,
        'subtotal' => $subtotal,
        'cleaning_fee' => $cleaning,
        'service_fee' => $service,
        'total_amount' => $total,
        'room_name' => $room['name'],
        'price_per_night' => $room['price_per_night'],
        'homestay_title' => $homestay['title'],
        'is_fallback' => $isFallbackRoom,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($checkIn !== '' || $checkOut !== '') {
        $error = booking_validation_error($checkIn, $checkOut, $guests, $room, $roomId, $isFallbackRoom);
        if ($error === '') {
            create_pending_booking($homestayId, $roomId, $room, $homestay, $user, $checkIn, $checkOut, $guests, '', '', '', '', $isFallbackRoom);
            redirect(BASE_URL . 'pages/payment.php');
        }
    } else {
        $notice = 'Select your check-in and check-out dates to continue to the payment gateway.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $checkIn = trim($_POST['check_in'] ?? '');
    $checkOut = trim($_POST['check_out'] ?? '');
    $guests = max(1, (int)($_POST['guests'] ?? 1));
    $guestName = trim($_POST['guest_name'] ?? '');
    $guestEmail = trim($_POST['guest_email'] ?? '');
    $guestPhone = trim($_POST['guest_phone'] ?? '');
    $special = trim($_POST['special_requests'] ?? '');

    $error = booking_validation_error($checkIn, $checkOut, $guests, $room, $roomId, $isFallbackRoom);
    if ($error === '' && (strlen($guestName) < 2 || !filter_var($guestEmail, FILTER_VALIDATE_EMAIL) || strlen($guestPhone) < 7)) {
        $error = 'Enter valid guest details.';
    }
    if ($error === '') {
        create_pending_booking($homestayId, $roomId, $room, $homestay, $user, $checkIn, $checkOut, $guests, $guestName, $guestEmail, $guestPhone, $special, $isFallbackRoom);
        redirect(BASE_URL . 'pages/payment.php');
    }
}

$nights = ($checkIn && $checkOut && $checkOut > $checkIn) ? nights_between($checkIn, $checkOut) : 1;
$subtotal = $room['price_per_night'] * $nights;
$cleaning = $room['cleaning_fee'];
$service = round($subtotal * 0.05, 2);
$total = $subtotal + $cleaning + $service;

$pageTitle = 'Book Stay';
require __DIR__ . '/../includes/header.php';
?>
<!-- Booking Page Hero header -->
<div class="page-header-bar animate__animated animate__fadeIn">
    <div class="container">
        <h1 class="display-font fw-bold text-teal-deep mb-1">Confirm your booking</h1>
        <p class="text-muted mb-0"><?= e($homestay['title']) ?> &bull; <?= e($room['name']) ?></p>
    </div>
</div>

<div class="container pb-5 animate__animated animate__fadeIn animate__delay-1s">
    <!-- Stepper indicator -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 bg-white p-3 rounded-3 border">
        <div class="d-flex align-items-center gap-2 text-teal fw-bold">
            <span class="rounded-circle bg-teal text-white d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 30px; height: 30px">1</span>
            <span class="small">1. Confirm Details</span>
        </div>
        <div style="flex: 1; min-width: 40px; height: 2px; background: var(--sn-border); margin: 0 1rem;"></div>
        <div class="d-flex align-items-center gap-2 text-muted fw-semibold">
            <span class="rounded-circle bg-light border text-muted d-inline-flex align-items-center justify-content-center" style="width: 30px; height: 30px">2</span>
            <span class="small">2. Secure Payment</span>
        </div>
        <div style="flex: 1; min-width: 40px; height: 2px; background: var(--sn-border); margin: 0 1rem;"></div>
        <div class="d-flex align-items-center gap-2 text-muted fw-semibold">
            <span class="rounded-circle bg-light border text-muted d-inline-flex align-items-center justify-content-center" style="width: 30px; height: 30px">3</span>
            <span class="small">3. Success Confirmation</span>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 small py-2.5 px-3 rounded-3 mb-4 animate__animated animate__shakeX">
            <i class="fas fa-circle-exclamation fs-6"></i>
            <div><?= e($error) ?></div>
        </div>
    <?php endif; ?>
    <?php if ($notice): ?>
        <div class="alert alert-info d-flex align-items-center gap-2 small py-2.5 px-3 rounded-3 mb-4">
            <i class="fas fa-circle-info fs-6"></i>
            <div><?= e($notice) ?></div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Form Block -->
        <div class="col-lg-7">
            <div class="dash-card border p-4">
                <form method="POST" id="bookingForm" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="homestay_id" value="<?= $homestayId ?>">
                    <input type="hidden" name="room_id" value="<?= $roomId ?>">
                    <?php if ($isFallbackRoom): ?>
                    <input type="hidden" name="fallback_room" value="<?= $fallbackRoomIndex ?>">
                    <?php endif; ?>
                    
                    <h5 class="fw-bold mb-3 text-teal"><i class="far fa-calendar-check me-2"></i>Stay Schedule</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-dark">Check-in</label>
                            <input type="date" name="check_in" id="book_check_in" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= e($checkIn) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-dark">Check-out</label>
                            <input type="date" name="check_out" id="book_check_out" class="form-control" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= e($checkOut) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-dark">Guests</label>
                            <select name="guests" class="form-select">
                                <?php for ($i = 1; $i <= (int)$room['max_guests']; $i++): ?>
                                <option value="<?= $i ?>" <?= $guests == $i ? 'selected' : '' ?>><?= $i ?> Guest<?= $i > 1 ? 's' : '' ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    
                    <hr class="opacity-25 my-4">
                    
                    <h5 class="fw-bold mb-3 text-teal"><i class="far fa-user me-2"></i>Primary Guest Details</h5>
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold text-dark">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-signature text-muted"></i></span>
                                <input type="text" name="guest_name" class="form-control" value="<?= e($_POST['guest_name'] ?? $user['full_name']) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                                <input type="email" name="guest_email" class="form-control" value="<?= e($_POST['guest_email'] ?? $user['email']) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Contact Phone</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-phone text-muted"></i></span>
                                <input type="tel" name="guest_phone" class="form-control" value="<?= e($_POST['guest_phone'] ?? $user['phone'] ?? '') ?>" minlength="7" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4 mt-3">
                        <label class="form-label small fw-semibold text-dark">Special Requests (Optional)</label>
                        <textarea name="special_requests" class="form-control" rows="2" placeholder="Dietary preferences, late check-in times, extra bedding..."><?= e($_POST['special_requests'] ?? '') ?></textarea>
                    </div>
                    
                    <button class="btn btn-primary w-100 py-2.5 fw-bold d-flex align-items-center justify-content-center gap-2">
                        <i class="fas fa-credit-card"></i> Continue to Secure Payment
                    </button>
                </form>
            </div>
        </div>

        <!-- Sidebar Widget -->
        <div class="col-lg-5">
            <div class="booking-widget border rounded-4 p-4 shadow-sm bg-white">
                <!-- Mini Listing Card -->
                <div class="d-flex gap-3 mb-4 pb-3 border-bottom align-items-center">
                    <img src="<?= e(display_image($homestay)) ?>" class="rounded-3 object-fit-cover shadow-sm" style="width: 90px; height: 70px" alt="Cover">
                    <div>
                        <span class="small text-teal fw-bold text-uppercase"><i class="fas fa-map-marker-alt me-1"></i><?= e($homestay['city']) ?></span>
                        <h6 class="fw-bold mb-1 mt-0.5 text-dark"><?= e($homestay['title']) ?></h6>
                        <span class="small text-muted d-block"><?= e($room['name']) ?> &bull; Max <?= (int)$room['max_guests'] ?> Guests</span>
                    </div>
                </div>

                <h5 class="fw-bold mb-3 display-font">Price Details</h5>
                
                <!-- Price Rows -->
                <div class="d-flex flex-column gap-2 mb-3">
                    <div class="d-flex justify-content-between text-muted small">
                        <span><?= money($room['price_per_night']) ?> &times; <span id="calcNights"><?= $nights ?></span> nights</span>
                        <span class="fw-semibold text-dark" id="calcSubtotal"><?= money($subtotal) ?></span>
                    </div>
                    <?php if ($cleaning > 0): ?>
                    <div class="d-flex justify-content-between text-muted small">
                        <span>Cleaning fee</span>
                        <span class="fw-semibold text-dark" id="calcCleaning"><?= money($cleaning) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between text-muted small">
                        <span>Sonam Homestay Service fee (5%)</span>
                        <span class="fw-semibold text-dark" id="calcService"><?= money($service) ?></span>
                    </div>
                </div>
                
                <hr class="opacity-25 my-3">
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fw-bold text-dark">Total (INR)</span>
                    <h4 class="fw-bold text-teal-deep m-0" id="calcTotal"><?= money($total) ?></h4>
                </div>

                <!-- Hidden Option selector for Javascript pricing calculations -->
                <select id="roomSelect" class="d-none">
                    <option selected data-price="<?= $room['price_per_night'] ?>" data-cleaning="<?= $room['cleaning_fee'] ?>"></option>
                </select>

                <div class="d-flex gap-2 align-items-start bg-light p-2.5 rounded-3 mt-3">
                    <i class="fas fa-shield-halved text-success fs-5 mt-0.5"></i>
                    <p class="small text-muted mb-0" style="line-height: 1.4">Your booking is protected by Sonam Homestay security. Valid details continue to Razorpay-style checkout.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var checkInInput = document.getElementById('book_check_in');
    var checkOutInput = document.getElementById('book_check_out');
    var roomSelect = document.getElementById('roomSelect');
    var bookingForm = document.getElementById('bookingForm');

    function addDays(dateString, days) {
        var date = new Date(dateString + 'T00:00:00');
        date.setDate(date.getDate() + days);
        return date.toISOString().split('T')[0];
    }

    function syncCheckoutMin() {
        if (!checkInInput || !checkOutInput || !checkInInput.value) return;
        var nextDay = addDays(checkInInput.value, 1);
        checkOutInput.setAttribute('min', nextDay);
        if (checkOutInput.value && checkOutInput.value <= checkInInput.value) {
            checkOutInput.value = nextDay;
        }
    }

    function showBookingValidation(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Booking dates needed',
                text: message,
                icon: 'warning',
                confirmButtonColor: '#0f766e',
                customClass: { popup: 'rounded-4 shadow-sm' }
            });
        } else {
            alert(message);
        }
    }
    
    function updatePricing() {
        syncCheckoutMin();
        var checkInVal = checkInInput.value;
        var checkOutVal = checkOutInput.value;
        
        if (!checkInVal || !checkOutVal || checkOutVal <= checkInVal) {
            return;
        }
        
        var date1 = new Date(checkInVal);
        var date2 = new Date(checkOutVal);
        var diffTime = Math.abs(date2 - date1);
        var diffNights = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        var option = roomSelect.options[roomSelect.selectedIndex];
        var price = parseFloat(option.getAttribute('data-price') || 0);
        var cleaning = parseFloat(option.getAttribute('data-cleaning') || 0);
        
        var subtotal = price * diffNights;
        var service = Math.round(subtotal * 0.05 * 100) / 100;
        var total = subtotal + cleaning + service;
        
        // Update view
        document.getElementById('calcNights').textContent = diffNights;
        document.getElementById('calcSubtotal').textContent = '₹' + subtotal.toLocaleString();
        document.getElementById('calcService').textContent = '₹' + service.toLocaleString();
        document.getElementById('calcTotal').textContent = '₹' + total.toLocaleString();
    }
    
    checkInInput.addEventListener('change', updatePricing);
    checkOutInput.addEventListener('change', updatePricing);
    if (bookingForm) {
        bookingForm.addEventListener('submit', function (e) {
            syncCheckoutMin();
            if (!checkInInput.value || !checkOutInput.value) {
                e.preventDefault();
                showBookingValidation('Please select check-in and check-out dates before continuing to payment.');
                return;
            }
            if (checkOutInput.value <= checkInInput.value) {
                e.preventDefault();
                showBookingValidation('Check-out date must be after check-in date.');
            }
        });
    }
    syncCheckoutMin();
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
