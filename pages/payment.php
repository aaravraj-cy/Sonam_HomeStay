<?php
// Dummy payment page
require_once __DIR__ . '/../includes/functions.php';
require_login('user');
require_verification();

$pending = $_SESSION['pending_booking'] ?? null;
if (!$pending) {
    set_flash('error', 'No pending booking.');
    redirect(BASE_URL . 'pages/search.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $method = $_POST['payment_method'] ?? 'card';
    if (!in_array($method, ['card', 'upi'], true)) {
        $method = 'card';
    }
    $cardNum = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
    $cardExpiry = trim($_POST['card_expiry'] ?? '');
    $cardCvv = trim($_POST['card_cvv'] ?? '');
    $upiId = trim($_POST['upi_id'] ?? '');

    if ($method === 'card' && !preg_match('/^\d{16}$/', $cardNum)) {
        $error = 'Enter a valid 16-digit card number for the Razorpay demo checkout.';
    } elseif ($method === 'card' && !preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $cardExpiry)) {
        $error = 'Enter card expiry in MM/YY format.';
    } elseif ($method === 'card' && !preg_match('/^\d{3,4}$/', $cardCvv)) {
        $error = 'Enter a valid CVV code.';
    } elseif ($method === 'upi' && !preg_match('/^[a-zA-Z0-9._-]{2,}@[a-zA-Z]{2,}$/', $upiId)) {
        $error = 'Enter a valid UPI ID, for example guest@upi.';
    } elseif (empty($pending['is_fallback']) && !is_room_available($pending['room_id'], $pending['check_in'], $pending['check_out'])) {
        $error = 'Room is no longer available for these dates.';
    } else {
        try {
            $conn->beginTransaction();
            $ref = booking_ref();

            $stmt = $conn->prepare("INSERT INTO bookings (booking_ref, user_id, homestay_id, check_in, check_out, guests,
                guest_name, guest_email, guest_phone, special_requests, subtotal, cleaning_fee, service_fee, total_amount, status)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,'pending')");
            $stmt->execute([
                $ref, $_SESSION['user_id'], $pending['homestay_id'], $pending['check_in'], $pending['check_out'],
                $pending['guests'], $pending['guest_name'], $pending['guest_email'], $pending['guest_phone'],
                $pending['special_requests'] ?: null, $pending['subtotal'], $pending['cleaning_fee'],
                $pending['service_fee'], $pending['total_amount']
            ]);
            $bookingId = $conn->lastInsertId();

            if (empty($pending['is_fallback'])) {
                $conn->prepare('INSERT INTO booking_details (booking_id, room_id, room_name, price_per_night, nights, quantity, amount) VALUES (?,?,?,?,?,?,?)')
                    ->execute([$bookingId, $pending['room_id'], $pending['room_name'], $pending['price_per_night'], $pending['nights'], 1, $pending['subtotal']]);
            }

            $txn = 'TXN' . strtoupper(bin2hex(random_bytes(5)));
            $conn->prepare("INSERT INTO payments (booking_id, transaction_id, payment_method, amount, status, paid_at) VALUES (?,?,?,?,'paid',NOW())")
                ->execute([$bookingId, $txn, $method, $pending['total_amount']]);

            // Notify owner
            $o = $conn->prepare('SELECT o.user_id FROM owners o JOIN homestays h ON h.owner_id = o.id WHERE h.id = ?');
            $o->execute([$pending['homestay_id']]);
            $ownerUser = $o->fetchColumn();
            if ($ownerUser) {
                add_notification($ownerUser, 'New booking', 'Booking ' . $ref . ' received.', BASE_URL . 'owner/bookings.php');
            }
            add_notification($_SESSION['user_id'], 'Booking submitted', 'Ref: ' . $ref, BASE_URL . 'user/booking-details.php?id=' . $bookingId);

            $conn->commit();
            unset($_SESSION['pending_booking']);
            redirect(BASE_URL . 'pages/booking-success.php?id=' . $bookingId);
        } catch (Exception $e) {
            $conn->rollBack();
            $error = 'Payment failed. Please verify detail entries.';
        }
    }
}

$pageTitle = 'Secure Payment';
require __DIR__ . '/../includes/header.php';
?>
<!-- Payment Hero Header -->
<div class="page-header-bar animate__animated animate__fadeIn">
    <div class="container">
        <h1 class="display-font fw-bold text-teal-deep mb-1">Razorpay Secure Checkout</h1>
        <p class="text-muted mb-0">Complete payment for <?= e($pending['room_name']) ?> &bull; Demo gateway account</p>
    </div>
</div>

<div class="container pb-5 animate__animated animate__fadeIn animate__delay-1s">
    <!-- Stepper indicator -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 bg-white p-3 rounded-3 border">
        <div class="d-flex align-items-center gap-2 text-teal fw-bold">
            <span class="rounded-circle bg-teal bg-opacity-20 text-teal d-inline-flex align-items-center justify-content-center border border-teal" style="width: 30px; height: 30px"><i class="fas fa-check"></i></span>
            <span class="small">1. Confirm Details</span>
        </div>
        <div style="flex: 1; min-width: 40px; height: 2px; background: var(--sn-teal); margin: 0 1rem;"></div>
        <div class="d-flex align-items-center gap-2 text-teal fw-bold">
            <span class="rounded-circle bg-teal text-white d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 30px; height: 30px">2</span>
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

    <div class="row g-4">
        <!-- Payment Options Card -->
        <div class="col-lg-7">
            <div class="dash-card border p-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <span class="small text-teal fw-bold text-uppercase">Razorpay Demo Gateway</span>
                        <h5 class="fw-bold m-0 text-dark">Payment Information</h5>
                    </div>
                    <div class="d-flex gap-2">
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">Razorpay</span>
                        <i class="fab fa-cc-visa text-muted fs-3"></i>
                        <i class="fab fa-cc-mastercard text-muted fs-3"></i>
                    </div>
                </div>

                <!-- Custom segmented tabs for payment method -->
                <div class="d-flex border rounded-3 overflow-hidden mb-4 p-1 bg-light">
                    <button type="button" id="tabCard" class="btn btn-teal w-50 py-2 rounded-2 fw-bold transition d-flex align-items-center justify-content-center gap-2">
                        <i class="fas fa-credit-card"></i> Credit / Debit Card
                    </button>
                    <button type="button" id="tabUpi" class="btn btn-light w-50 py-2 rounded-2 text-muted fw-bold transition d-flex align-items-center justify-content-center gap-2">
                        <i class="fas fa-mobile-screen-button"></i> BHIM / UPI
                    </button>
                </div>

                <form method="POST" id="paymentForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="payment_method" id="payment_method_input" value="card">

                    <!-- Card Form Container -->
                    <div id="cardSection">
                        <!-- Simulated Interactive Card Graphic -->
                        <div class="card-preview mb-4 position-relative text-white rounded-3 shadow-lg p-3" style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); height: 180px; font-family: var(--sn-font);">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <i class="fas fa-microchip fs-3 text-warning"></i>
                                <span class="fw-bold tracking-widest text-teal-light">VISA</span>
                            </div>
                            <div class="fs-4 mb-3 text-center tracking-widest" id="previewCardNum" style="letter-spacing: 0.1em; font-family: monospace;">4111 1111 1111 1111</div>
                            <div class="d-flex justify-content-between align-items-end mt-4 small">
                                <div>
                                    <span class="text-white-50 small d-block" style="font-size: 0.65rem">CARDHOLDER</span>
                                    <span class="text-white fw-bold text-uppercase" id="previewName"><?= e($pending['guest_name']) ?></span>
                                </div>
                                <div>
                                    <span class="text-white-50 small d-block" style="font-size: 0.65rem">EXPIRES</span>
                                    <span class="text-white fw-bold" id="previewExpiry">12/28</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Fields -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Card Number</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-credit-card text-muted"></i></span>
                                <input type="text" name="card_number" id="card_number_input" class="form-control" value="4111 1111 1111 1111" maxlength="19" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-dark">Expiration Date</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="far fa-calendar text-muted"></i></span>
                                    <input type="text" name="card_expiry" id="card_expiry_input" class="form-control" value="12/28" placeholder="MM/YY" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-dark">CVV / Code</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-shield text-muted"></i></span>
                                    <input type="password" name="card_cvv" class="form-control" value="123" placeholder="123" maxlength="3" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- UPI Form Container -->
                    <div id="upiSection" class="d-none">
                        <div class="text-center p-3 border rounded-3 bg-light bg-opacity-25 mb-4 shadow-xs">
                            <i class="fas fa-qrcode fs-1 text-teal mb-2"></i>
                            <h6 class="fw-bold text-teal mb-1">Instant QR Simulation</h6>
                            <p class="small text-muted mb-0">Use any BHIM / Google Pay / PhonePe app to simulate validation</p>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-dark">UPI ID / Virtual Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-at text-muted"></i></span>
                                <input type="text" name="upi_id" id="upi_id_input" class="form-control" placeholder="guest@upi">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold d-flex align-items-center justify-content-center gap-2">
                        <i class="fas fa-shield-halved"></i> Pay with Razorpay <?= money($pending['total_amount']) ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Price Details Widget -->
        <div class="col-lg-5">
            <div class="booking-widget border rounded-4 p-4 shadow-sm bg-white">
                <div class="d-flex gap-3 mb-4 pb-3 border-bottom align-items-center">
                    <h6 class="fw-bold mb-1 text-dark"><?= e($pending['homestay_title']) ?></h6>
                    <span class="badge bg-teal bg-opacity-10 text-teal ms-auto py-1 px-2 small">Pending Review</span>
                </div>
                
                <h5 class="fw-bold mb-3 display-font">Summary Details</h5>
                <div class="d-flex flex-column gap-2 mb-3">
                    <div class="d-flex justify-content-between text-muted small">
                        <span>Stay Period</span>
                        <span class="fw-semibold text-dark"><?= format_date($pending['check_in']) ?> to <?= format_date($pending['check_out']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between text-muted small">
                        <span>Nights</span>
                        <span class="fw-semibold text-dark"><?= (int)$pending['nights'] ?> night<?= $pending['nights'] > 1 ? 's' : '' ?></span>
                    </div>
                    <div class="d-flex justify-content-between text-muted small">
                        <span>Room Type</span>
                        <span class="fw-semibold text-dark"><?= e($pending['room_name']) ?></span>
                    </div>
                </div>

                <hr class="opacity-25 my-3">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fw-bold text-dark">Amount Charged</span>
                    <h4 class="fw-bold text-teal-deep m-0"><?= money($pending['total_amount']) ?></h4>
                </div>

                <div class="d-flex gap-2 align-items-start bg-light p-2.5 rounded-3 mt-3">
                    <i class="fas fa-circle-info text-info fs-5 mt-0.5"></i>
                    <p class="small text-muted mb-0" style="line-height: 1.4">This is a Razorpay-style demo checkout. No real card, UPI, or bank details are stored.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var tabCard = document.getElementById('tabCard');
    var tabUpi = document.getElementById('tabUpi');
    var cardSection = document.getElementById('cardSection');
    var upiSection = document.getElementById('upiSection');
    var paymentMethodInput = document.getElementById('payment_method_input');
    
    var cardNumInput = document.getElementById('card_number_input');
    var cardExpiryInput = document.getElementById('card_expiry_input');
    var upiIdInput = document.getElementById('upi_id_input');
    var cvvInput = document.querySelector('input[name="card_cvv"]');
    var paymentForm = document.getElementById('paymentForm');

    function showPaymentValidation(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Payment detail needed',
                text: message,
                icon: 'warning',
                confirmButtonColor: '#0f766e',
                customClass: { popup: 'rounded-4 shadow-sm' }
            });
        } else {
            alert(message);
        }
    }
    
    // Switch to Credit Card tab
    tabCard.addEventListener('click', function () {
        tabCard.className = 'btn btn-teal w-50 py-2 rounded-2 fw-bold transition d-flex align-items-center justify-content-center gap-2';
        tabUpi.className = 'btn btn-light w-50 py-2 rounded-2 text-muted fw-bold transition d-flex align-items-center justify-content-center gap-2';
        cardSection.classList.remove('d-none');
        upiSection.classList.add('d-none');
        paymentMethodInput.value = 'card';
        
        cardNumInput.setAttribute('required', 'required');
        cardExpiryInput.setAttribute('required', 'required');
        upiIdInput.removeAttribute('required');
    });

    // Switch to UPI tab
    tabUpi.addEventListener('click', function () {
        tabUpi.className = 'btn btn-teal w-50 py-2 rounded-2 fw-bold transition d-flex align-items-center justify-content-center gap-2';
        tabCard.className = 'btn btn-light w-50 py-2 rounded-2 text-muted fw-bold transition d-flex align-items-center justify-content-center gap-2';
        upiSection.classList.remove('d-none');
        cardSection.classList.add('d-none');
        paymentMethodInput.value = 'upi';
        
        upiIdInput.setAttribute('required', 'required');
        cardNumInput.removeAttribute('required');
        cardExpiryInput.removeAttribute('required');
    });

    // Live update credit card mockup preview values
    cardNumInput.addEventListener('input', function () {
        var val = this.value.replace(/\D/g, '');
        var formatted = '';
        for (var i = 0; i < val.length; i++) {
            if (i > 0 && i % 4 === 0) formatted += ' ';
            formatted += val[i];
        }
        this.value = formatted;
        document.getElementById('previewCardNum').textContent = formatted || '•••• •••• •••• ••••';
    });

    cardExpiryInput.addEventListener('input', function () {
        var val = this.value.replace(/\D/g, '');
        if (val.length >= 2) {
            this.value = val.slice(0, 2) + '/' + val.slice(2, 4);
        } else {
            this.value = val;
        }
        document.getElementById('previewExpiry').textContent = this.value || 'MM/YY';
    });

    if (paymentForm) {
        paymentForm.addEventListener('submit', function (e) {
            if (paymentMethodInput.value === 'card') {
                var cardNum = cardNumInput.value.replace(/\s+/g, '');
                if (!/^\d{16}$/.test(cardNum)) {
                    e.preventDefault();
                    showPaymentValidation('Enter a valid 16-digit card number for the Razorpay demo checkout.');
                    return;
                }
                if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(cardExpiryInput.value)) {
                    e.preventDefault();
                    showPaymentValidation('Enter card expiry in MM/YY format.');
                    return;
                }
                if (!/^\d{3,4}$/.test(cvvInput.value)) {
                    e.preventDefault();
                    showPaymentValidation('Enter a valid CVV code.');
                }
            } else if (!/^[a-zA-Z0-9._-]{2,}@[a-zA-Z]{2,}$/.test(upiIdInput.value)) {
                e.preventDefault();
                showPaymentValidation('Enter a valid UPI ID, for example guest@upi.');
            }
        });
    }
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
