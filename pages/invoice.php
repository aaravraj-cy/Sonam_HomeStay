<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT b.*, h.title, h.address, h.city, h.state, p.transaction_id, p.payment_method, p.status AS payment_status
    FROM bookings b
    JOIN homestays h ON h.id = b.homestay_id
    LEFT JOIN payments p ON p.booking_id = b.id
    WHERE b.id = ?');
$stmt->execute([$id]);
$b = $stmt->fetch();

if (!$b) {
    set_flash('error', 'Invoice not found.');
    redirect(BASE_URL);
}

$allowed = false;
if (is_user() && $b['user_id'] == $_SESSION['user_id']) $allowed = true;
if (is_owner()) {
    $oid = get_owner_id();
    $c = $conn->prepare('SELECT id FROM homestays WHERE id = ? AND owner_id = ?');
    $c->execute([$b['homestay_id'], $oid]);
    if ($c->fetch()) $allowed = true;
}
if (!$allowed) {
    set_flash('error', 'Access denied.');
    redirect(BASE_URL);
}

$details = $conn->prepare('SELECT * FROM booking_details WHERE booking_id = ?');
$details->execute([$id]);
$details = $details->fetchAll();

$pageTitle = 'Invoice';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <!-- Back & Print Buttons -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print animate__animated animate__fadeIn">
        <a href="<?= BASE_URL ?>user/booking-history.php" class="btn btn-light border btn-sm d-inline-flex align-items-center gap-2">
            <i class="fas fa-arrow-left"></i> Back to Trips
        </a>
        <button onclick="window.print()" class="btn btn-teal btn-sm d-inline-flex align-items-center gap-2">
            <i class="fas fa-print"></i> Print Invoice
        </button>
    </div>
    
    <!-- Invoice Content Frame -->
    <div class="invoice-box border rounded-4 p-4 p-md-5 shadow-sm bg-white animate__animated animate__fadeIn animate__delay-1s" style="max-width: 850px; margin: 0 auto; color: #334155;">
        <!-- Invoice Header -->
        <div class="row g-4 mb-4">
            <div class="col-sm-6 text-center text-sm-start">
                <div class="sn-brand mb-3 justify-content-center justify-content-sm-start">
                    <span class="brand-mark bg-teal text-white rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 36px; height: 36px">
                        <i class="fas fa-mountain"></i>
                    </span>
                    <strong class="h4 text-dark ms-2 m-0 align-middle">Sonam Homestay</strong>
                </div>
                <h5 class="fw-bold mb-1 text-dark display-font">INVOICE RECEIPT</h5>
                <p class="text-muted small mb-0">Booking Reference: <strong class="text-dark"><?= e($b['booking_ref']) ?></strong></p>
                <p class="text-muted small">Date Issued: <?= format_date($b['created_at']) ?></p>
            </div>
            <div class="col-sm-6 text-center text-sm-end">
                <span class="badge bg-success bg-opacity-10 text-success mb-2 py-1.5 px-3 rounded-pill fw-semibold text-uppercase small">
                    <?= e($b['payment_status'] ?? 'paid') ?>
                </span>
                <p class="small text-muted mb-1"><strong>Sonam Homestay Sikkim</strong></p>
                <p class="small text-muted mb-0">Khechuperi, West Sikkim</p>
                <p class="small text-muted mb-0">support@sonamhomestay.local</p>
            </div>
        </div>

        <hr class="opacity-25 my-4">

        <!-- Billing Addresses -->
        <div class="row g-4 mb-5 text-center text-sm-start">
            <div class="col-sm-6">
                <h6 class="text-teal small fw-bold text-uppercase mb-2" style="letter-spacing: 0.05em">Billed To</h6>
                <strong class="text-dark d-block mb-1"><?= e($b['guest_name']) ?></strong>
                <p class="small text-muted mb-1"><?= e($b['guest_email']) ?></p>
                <p class="small text-muted mb-0"><?= e($b['guest_phone']) ?></p>
            </div>
            <div class="col-sm-6 text-sm-end">
                <h6 class="text-teal small fw-bold text-uppercase mb-2" style="letter-spacing: 0.05em">Property Details</h6>
                <strong class="text-dark d-block mb-1"><?= e($b['title']) ?></strong>
                <p class="small text-muted mb-1"><?= e($b['address']) ?></p>
                <p class="small text-muted mb-0"><?= e($b['city']) ?>, <?= e($b['state']) ?></p>
            </div>
        </div>

        <!-- Room Items Table -->
        <div class="table-responsive mb-4">
            <table class="table align-middle">
                <thead>
                    <tr class="bg-light">
                        <th class="py-3 text-dark small fw-bold border-bottom" style="width: 50%">Room Description</th>
                        <th class="py-3 text-dark small fw-bold border-bottom text-center">Price / Night</th>
                        <th class="py-3 text-dark small fw-bold border-bottom text-center">Nights</th>
                        <th class="py-3 text-dark small fw-bold border-bottom text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($details as $d): ?>
                    <tr>
                        <td class="py-3">
                            <strong class="text-dark d-block"><?= e($d['room_name']) ?></strong>
                            <span class="small text-muted">Verified Stay Accommodation</span>
                        </td>
                        <td class="py-3 text-center"><?= money($d['price_per_night']) ?></td>
                        <td class="py-3 text-center"><?= (int)$d['nights'] ?></td>
                        <td class="py-3 text-end fw-semibold text-dark"><?= money($d['amount']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if ((float)$b['cleaning_fee'] > 0): ?>
                    <tr>
                        <td colspan="3" class="text-end py-2 text-muted small border-0">Cleaning Fee</td>
                        <td class="text-end py-2 fw-semibold text-dark border-0"><?= money($b['cleaning_fee']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td colspan="3" class="text-end py-2 text-muted small border-0">Sonam Homestay Service Fee (5%)</td>
                        <td class="text-end py-2 fw-semibold text-dark border-0"><?= money($b['service_fee']) ?></td>
                    </tr>
                    <tr class="border-top">
                        <td colspan="3" class="text-end py-3 text-dark fw-bold border-0 fs-5">Total Paid (INR)</td>
                        <td class="text-end py-3 text-teal-deep fw-bold border-0 fs-5"><?= money($b['total_amount']) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Transaction Details Block -->
        <div class="p-3 bg-light rounded-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="small">
                <span class="text-muted">Payment Method:</span> <strong class="text-dark text-uppercase"><?= e($b['payment_method'] ?? 'card') ?></strong>
            </div>
            <?php if (!empty($b['transaction_id'])): ?>
            <div class="small">
                <span class="text-muted">Transaction ID:</span> <strong class="text-dark"><?= e($b['transaction_id']) ?></strong>
            </div>
            <?php endif; ?>
            <div class="small">
                <span class="text-muted">Status:</span> <span class="badge text-white bg-success bg-opacity-15 text-success py-1 px-2.5 rounded-2 small fw-semibold">Paid</span>
            </div>
        </div>
        
        <div class="text-center mt-5 pt-3">
            <p class="small text-muted mb-0">Thank you for booking your Sikkim homestay with Sonam Homestay!</p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
