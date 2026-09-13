<?php
// Razorpay Checkout Page - Sonam Homestay
require_once __DIR__ . '/../includes/functions.php';
require_login('user');
require_verification();

$pending = $_SESSION['pending_booking'] ?? null;
if (!$pending) {
    set_flash('error', 'No pending booking.');
    redirect(BASE_URL . 'pages/search.php');
}

$razorpayKeyId = razorpay_key_id();
$razorpayKeySecret = razorpay_key_secret();

$razorpayOrderId = '';
$razorpayOrder = null;
$razorpayApiError = '';

// Attempt to create a backend Razorpay order
if (!empty($razorpayKeyId) && !empty($razorpayKeySecret)) {
    $receipt = 'rcpt_' . bin2hex(random_bytes(4)) . '_' . (int)($pending['room_id'] ?? 0);
    $razorpayOrder = razorpay_create_order($pending['total_amount'], $receipt, [
        'homestay_id' => (string)($pending['homestay_id'] ?? 1),
        'room_id' => (string)($pending['room_id'] ?? 0),
        'guest_email' => (string)($pending['guest_email'] ?? ''),
    ], $razorpayApiError);
    if (!empty($razorpayOrder['id'])) {
        $razorpayOrderId = $razorpayOrder['id'];
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $paymentId = trim($_POST['razorpay_payment_id'] ?? '');
    $orderId = trim($_POST['razorpay_order_id'] ?? '');
    $signature = trim($_POST['razorpay_signature'] ?? '');

    if (empty($paymentId)) {
        $error = 'Razorpay payment was not completed. Please try again.';
    } elseif ($orderId !== '' && $signature !== '') {
        if (!razorpay_verify_signature($orderId, $paymentId, $signature)) {
            $error = 'Razorpay payment signature verification failed. Please try again.';
        }
    }

    if (empty($error)) {
        if (empty($pending['is_fallback']) && !is_room_available($pending['room_id'], $pending['check_in'], $pending['check_out'])) {
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

                $conn->prepare("INSERT INTO payments (booking_id, transaction_id, payment_method, amount, status, paid_at) VALUES (?,?, 'razorpay', ?, 'paid', NOW())")
                    ->execute([$bookingId, $paymentId, $pending['total_amount']]);

                // Notify owner
                $o = $conn->prepare('SELECT o.user_id FROM owners o JOIN homestays h ON h.owner_id = o.id WHERE h.id = ?');
                $o->execute([$pending['homestay_id']]);
                $ownerUser = $o->fetchColumn();
                if ($ownerUser) {
                    add_notification($ownerUser, 'New booking received', 'Booking ' . $ref . ' paid via Razorpay.', BASE_URL . 'owner/bookings.php');
                }
                add_notification($_SESSION['user_id'], 'Booking submitted', 'Ref: ' . $ref . ' (Razorpay)', BASE_URL . 'user/booking-details.php?id=' . $bookingId);

                $conn->commit();
                unset($_SESSION['pending_booking']);
                redirect(BASE_URL . 'pages/booking-success.php?id=' . $bookingId);
            } catch (Exception $e) {
                $conn->rollBack();
                $error = 'Payment processing failed. Please verify your details.';
            }
        }
    }
}

$pageTitle = 'Razorpay Secure Checkout';
require __DIR__ . '/../includes/header.php';
?>
<!-- Payment Hero Header -->
<div class="page-header-bar animate__animated animate__fadeIn">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="display-font fw-bold text-teal-deep mb-1">Razorpay Checkout</h1>
                <p class="text-muted mb-0">Complete your reservation for <?= e($pending['room_name']) ?> &bull; Sonam Homestay</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-teal bg-opacity-10 text-teal-deep border border-teal border-opacity-25 px-3 py-2 rounded-pill font-monospace small">
                    <i class="fas fa-key me-1"></i> <?= e($razorpayKeyId) ?>
                </span>
            </div>
        </div>
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
            <span class="small">2. Razorpay Payment</span>
        </div>
        <div style="flex: 1; min-width: 40px; height: 2px; background: var(--sn-border); margin: 0 1rem;"></div>
        <div class="d-flex align-items-center gap-2 text-muted fw-semibold">
            <span class="rounded-circle bg-light border text-muted d-inline-flex align-items-center justify-content-center" style="width: 30px; height: 30px">3</span>
            <span class="small">3. Booking Confirmed</span>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 small py-2.5 px-3 rounded-3 mb-4 animate__animated animate__shakeX">
            <i class="fas fa-circle-exclamation fs-6"></i>
            <div><?= e($error) ?></div>
        </div>
    <?php endif; ?>

    <?php if (!empty($razorpayApiError)): ?>
        <div class="alert alert-warning d-flex align-items-start gap-2 small py-2.5 px-3 rounded-3 mb-4">
            <i class="fas fa-triangle-exclamation text-warning mt-0.5 fs-6"></i>
            <div>
                <strong>Razorpay Gateway Notice:</strong> <?= e($razorpayApiError) ?><br>
                <span class="text-dark-emphasis">Razorpay server returned <em>Authentication failed</em> for the Key Secret. If you generated a new secret in your <a href="https://dashboard.razorpay.com/#/access/api_keys" target="_blank" class="alert-link text-decoration-underline">Razorpay Dashboard</a>, please update it. You can also use the <strong>Developer Test Simulation</strong> below to test bookings instantly.</span>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Razorpay Exclusive Checkout Card -->
        <div class="col-lg-7">
            <div class="dash-card border p-4 bg-white rounded-4 shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
                    <div class="d-flex align-items-center gap-3">
                        <img src="https://cdn.razorpay.com/static/assets/logo/rzp.png" alt="Razorpay" height="32" style="object-fit: contain;">
                        <div>
                            <h5 class="fw-bold m-0 text-dark">Razorpay Payment Gateway</h5>
                            <span class="small text-muted">Official Payment Partner</span>
                        </div>
                    </div>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1.5 rounded-pill small fw-semibold">
                        <i class="fas fa-shield-halved me-1"></i> Active
                    </span>
                </div>

                <!-- Razorpay Action Box -->
                <div class="p-4 rounded-4 bg-teal bg-opacity-10 border border-teal border-opacity-25 mb-4 text-center">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-teal text-white shadow-sm" style="width: 56px; height: 56px;">
                            <i class="fas fa-bolt fs-4"></i>
                        </span>
                    </div>
                    <h4 class="fw-bold text-teal-deep mb-2 display-font">Proceed to Razorpay Checkout</h4>
                    <p class="text-muted small mb-4 mx-auto" style="max-width: 480px;">
                        Click below to open the official Razorpay Checkout window. You can complete payment seamlessly using UPI, Cards, Netbanking, or Wallets.
                    </p>

                    <button type="button" id="rzp-button-pay" class="btn btn-teal btn-lg w-100 py-3 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2 fs-5">
                        <i class="fas fa-lock"></i> Pay with Razorpay <?= money($pending['total_amount']) ?>
                    </button>
                    
                    <div class="d-flex flex-wrap justify-content-center align-items-center gap-3 text-muted small mt-3" style="font-size: 0.8rem;">
                        <span><i class="fas fa-shield-halved text-success me-1"></i> 256-bit SSL</span>
                        <span>&bull;</span>
                        <span><i class="fas fa-certificate text-primary me-1"></i> PCI-DSS Level 1</span>
                        <span>&bull;</span>
                        <span class="font-monospace">Key: <?= e($razorpayKeyId) ?></span>
                    </div>
                </div>

                <!-- Hidden form to process Razorpay callback -->
                <form id="razorpayForm" method="POST" action="">
                    <?= csrf_field() ?>
                    <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
                    <input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
                    <input type="hidden" name="razorpay_signature" id="razorpay_signature">
                </form>

                <!-- Supported Methods Highlights via Razorpay -->
                <div class="bg-light p-3 rounded-3 mb-3">
                    <span class="small fw-bold text-dark d-block mb-2">Accepted payment modes within Razorpay:</span>
                    <div class="row g-2 text-muted small">
                        <div class="col-sm-6 d-flex align-items-center gap-2">
                            <i class="fas fa-qrcode text-teal"></i>
                            <span>UPI (GPay, PhonePe, Paytm, BHIM)</span>
                        </div>
                        <div class="col-sm-6 d-flex align-items-center gap-2">
                            <i class="fas fa-credit-card text-teal"></i>
                            <span>Debit &amp; Credit Cards (All banks)</span>
                        </div>
                        <div class="col-sm-6 d-flex align-items-center gap-2">
                            <i class="fas fa-building-columns text-teal"></i>
                            <span>Net Banking (50+ Indian banks)</span>
                        </div>
                        <div class="col-sm-6 d-flex align-items-center gap-2">
                            <i class="fas fa-wallet text-teal"></i>
                            <span>Wallets &amp; PayLater</span>
                        </div>
                    </div>
                </div>

                <!-- Test / Simulation Fallback Button (No card inputs) -->
                <div class="text-center pt-2">
                    <button type="button" id="rzp-simulate-pay" class="btn btn-outline-secondary btn-sm px-3 py-1.5 rounded-pill text-muted small">
                        <i class="fas fa-vial me-1"></i> Developer Test Simulation (Instant Success)
                    </button>
                </div>
            </div>
        </div>

        <!-- Price Details Widget -->
        <div class="col-lg-5">
            <div class="booking-widget border rounded-4 p-4 shadow-sm bg-white">
                <div class="d-flex gap-3 mb-4 pb-3 border-bottom align-items-center">
                    <h6 class="fw-bold mb-1 text-dark"><?= e($pending['homestay_title']) ?></h6>
                    <span class="badge bg-teal bg-opacity-10 text-teal ms-auto py-1 px-2 small">Pending Payment</span>
                </div>
                
                <h5 class="fw-bold mb-3 display-font">Booking Summary</h5>
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
                    <div class="d-flex justify-content-between text-muted small">
                        <span>Guest</span>
                        <span class="fw-semibold text-dark"><?= e($pending['guest_name']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between text-muted small">
                        <span>Contact</span>
                        <span class="fw-semibold text-dark"><?= e($pending['guest_phone']) ?></span>
                    </div>
                </div>

                <hr class="opacity-25 my-3">

                <div class="d-flex flex-column gap-1 mb-3">
                    <div class="d-flex justify-content-between text-muted small">
                        <span>Room Subtotal</span>
                        <span><?= money($pending['subtotal']) ?></span>
                    </div>
                    <?php if ((float)$pending['cleaning_fee'] > 0): ?>
                    <div class="d-flex justify-content-between text-muted small">
                        <span>Cleaning Fee</span>
                        <span><?= money($pending['cleaning_fee']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between text-muted small">
                        <span>Service Fee (5%)</span>
                        <span><?= money($pending['service_fee']) ?></span>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3 pt-2 border-top">
                    <span class="fw-bold text-dark">Total Amount</span>
                    <h4 class="fw-bold text-teal-deep m-0"><?= money($pending['total_amount']) ?></h4>
                </div>

                <div class="d-flex gap-2 align-items-start bg-light p-2.5 rounded-3 mt-3">
                    <i class="fas fa-shield-halved text-teal fs-5 mt-0.5"></i>
                    <p class="small text-muted mb-0" style="line-height: 1.4">
                        Payments are securely processed via <strong>Razorpay</strong>. Your reservation details will be confirmed upon transaction completion.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Razorpay Official Checkout SDK -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var rzpButton = document.getElementById('rzp-button-pay');
    var simulateButton = document.getElementById('rzp-simulate-pay');

    // Razorpay Standard Checkout options
    var rzpOptions = {
        "key": "<?= htmlspecialchars($razorpayKeyId, ENT_QUOTES, 'UTF-8') ?>",
        "amount": <?= (int)round((float)$pending['total_amount'] * 100) ?>,
        "currency": "INR",
        "name": "Sonam Homestay",
        "description": "Reservation: <?= htmlspecialchars(addslashes($pending['room_name']), ENT_QUOTES, 'UTF-8') ?>",
        "image": "https://cdn.razorpay.com/static/assets/logo/rzp.png",
        <?php if (!empty($razorpayOrderId)): ?>
        "order_id": "<?= htmlspecialchars($razorpayOrderId, ENT_QUOTES, 'UTF-8') ?>",
        <?php endif; ?>
        "prefill": {
            "name": "<?= htmlspecialchars(addslashes($pending['guest_name']), ENT_QUOTES, 'UTF-8') ?>",
            "email": "<?= htmlspecialchars(addslashes($pending['guest_email']), ENT_QUOTES, 'UTF-8') ?>",
            "contact": "<?= htmlspecialchars(addslashes($pending['guest_phone']), ENT_QUOTES, 'UTF-8') ?>"
        },
        "notes": {
            "homestay": "Sonam Homestay",
            "room": "<?= htmlspecialchars(addslashes($pending['room_name']), ENT_QUOTES, 'UTF-8') ?>",
            "nights": "<?= (int)$pending['nights'] ?>"
        },
        "theme": {
            "color": "#0d9488"
        },
        "handler": function (response) {
            document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id || '';
            document.getElementById('razorpay_order_id').value = response.razorpay_order_id || '';
            document.getElementById('razorpay_signature').value = response.razorpay_signature || '';
            document.getElementById('razorpayForm').submit();
        },
        "modal": {
            "ondismiss": function () {
                console.log('Razorpay modal closed by user');
            }
        }
    };

    // Trigger Razorpay modal
    if (rzpButton) {
        rzpButton.addEventListener('click', function (e) {
            e.preventDefault();
            if (typeof Razorpay === 'undefined') {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Razorpay SDK Unavailable',
                        text: 'Unable to load Razorpay library. Please check your internet connection or use the Developer Test Simulation.',
                        icon: 'warning',
                        confirmButtonColor: '#0d9488'
                    });
                } else {
                    alert('Unable to load Razorpay library. Please check your internet connection or use the Developer Test Simulation.');
                }
                return;
            }

            try {
                var rzp = new Razorpay(rzpOptions);
                rzp.on('payment.failed', function (response) {
                    var errObj = (response && response.error) ? response.error : {};
                    var desc = errObj.description || 'Payment process was not completed.';
                    var reason = errObj.reason ? ('\nReason: ' + errObj.reason) : '';
                    var code = errObj.code ? (' [' + errObj.code + ']') : '';
                    var tip = '\n\n💡 Tip for Test Mode:\n• Cards: Use 4111 1111 1111 1111 with any future date & CVV 123.\n• UPI: Use success@razorpay\n• Real cards/UPI will be declined by Razorpay in Test Mode.';

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Payment Failed / Declined' + code,
                            html: '<div class="text-start small"><strong>Razorpay message:</strong> ' + desc + '<br><br><span class="text-muted">In Razorpay Test Mode, real bank cards and real personal UPI IDs are rejected. Please use test card <code>4111 1111 1111 1111</code> or click the <em>Developer Test Simulation</em> button on the page.</span></div>',
                            icon: 'warning',
                            confirmButtonColor: '#0d9488'
                        });
                    } else {
                        alert('Payment Failed: ' + desc + reason + tip);
                    }
                });
                rzp.open();
            } catch (err) {
                console.error('Razorpay initialization error:', err);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Notice',
                        text: 'Could not open Razorpay modal: ' + err.message + '. You can use Developer Test Simulation.',
                        icon: 'warning',
                        confirmButtonColor: '#0d9488'
                    });
                } else {
                    alert('Could not open Razorpay modal: ' + err.message);
                }
            }
        });
    }

    // Instant Simulation Handler (for local testing without card forms)
    if (simulateButton) {
        simulateButton.addEventListener('click', function () {
            var simPayId = 'pay_sim_' + Math.random().toString(36).substring(2, 12).toUpperCase();
            document.getElementById('razorpay_payment_id').value = simPayId;
            document.getElementById('razorpay_order_id').value = '';
            document.getElementById('razorpay_signature').value = '';
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Processing Payment',
                    text: 'Simulating successful Razorpay authorization (' + simPayId + ')...',
                    icon: 'success',
                    showConfirmButton: false,
                    timer: 1200
                }).then(function () {
                    document.getElementById('razorpayForm').submit();
                });
            } else {
                document.getElementById('razorpayForm').submit();
            }
        });
    }
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>