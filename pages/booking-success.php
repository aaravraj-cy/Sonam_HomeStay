<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('user');

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT b.*, h.title FROM bookings b JOIN homestays h ON h.id = b.homestay_id WHERE b.id = ? AND b.user_id = ?');
$stmt->execute([$id, $_SESSION['user_id']]);
$b = $stmt->fetch();
if (!$b) {
    set_flash('error', 'Booking not found.');
    redirect(BASE_URL . 'user/booking-history.php');
}

$pageTitle = 'Success';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5 text-center">
    <div class="feature-icon mx-auto mb-3"><i class="fas fa-check"></i></div>
    <h1>Booking submitted!</h1>
    <p>Reference: <strong><?= e($b['booking_ref']) ?></strong></p>
    <p class="text-muted"><?= e($b['title']) ?> · Waiting for owner confirmation</p>
    <a href="<?= BASE_URL ?>pages/invoice.php?id=<?= $id ?>" class="btn btn-primary">Invoice</a>
    <a href="<?= BASE_URL ?>user/booking-details.php?id=<?= $id ?>" class="btn btn-outline-primary">Details</a>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
