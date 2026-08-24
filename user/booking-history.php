<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('user');

$status = trim($_GET['status'] ?? '');
$uid = $_SESSION['user_id'];
$sql = 'SELECT b.*, h.title, h.city, h.cover_image FROM bookings b JOIN homestays h ON h.id = b.homestay_id WHERE b.user_id = ?';
$params = [$uid];

if (in_array($status, ['pending','confirmed','checked_in','cancelled','completed','rejected'])) {
    $sql .= ' AND b.status = ?';
    $params[] = $status;
}
$sql .= ' ORDER BY b.created_at DESC';
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$pageTitle = 'My Trips';
$sidebarRole = 'user';
$sidebarActive = 'bookings';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout animate__animated animate__fadeIn">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="dashboard-main">
        <!-- Dashboard Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <button class="btn btn-outline-primary mobile-sidebar-toggle d-lg-none"><i class="fas fa-bars"></i> Menu</button>
            <div>
                <h1 class="h3 display-font fw-bold mb-1"><i class="fas fa-suitcase text-teal me-2"></i>My Trips</h1>
                <p class="text-muted small mb-0">Manage your homestay bookings and view receipts.</p>
            </div>
            <a href="<?= BASE_URL ?>pages/search.php" class="btn btn-primary btn-sm px-3"><i class="fas fa-plus me-1"></i>New Booking</a>
        </div>

        <!-- Filter Navigation -->
        <div class="d-flex flex-wrap gap-2 mb-4 bg-white p-2 rounded-3 border">
            <a href="?" class="btn btn-sm <?= $status === '' ? 'btn-primary' : 'btn-light border text-muted' ?> px-3 py-1.5 fw-semibold">
                <i class="fas fa-list me-1"></i> All Stays
            </a>
            <a href="?status=pending" class="btn btn-sm <?= $status === 'pending' ? 'btn-primary' : 'btn-light border text-muted' ?> px-3 py-1.5 fw-semibold">
                <i class="fas fa-clock text-warning me-1"></i> Pending
            </a>
            <a href="?status=confirmed" class="btn btn-sm <?= $status === 'confirmed' ? 'btn-primary' : 'btn-light border text-muted' ?> px-3 py-1.5 fw-semibold">
                <i class="fas fa-check-circle text-success me-1"></i> Confirmed
            </a>
            <a href="?status=checked_in" class="btn btn-sm <?= $status === 'checked_in' ? 'btn-primary' : 'btn-light border text-muted' ?> px-3 py-1.5 fw-semibold">
                <i class="fas fa-door-open text-info me-1"></i> Checked In
            </a>
            <a href="?status=completed" class="btn btn-sm <?= $status === 'completed' ? 'btn-primary' : 'btn-light border text-muted' ?> px-3 py-1.5 fw-semibold">
                <i class="fas fa-calendar-check text-info me-1"></i> Completed
            </a>
            <a href="?status=cancelled" class="btn btn-sm <?= $status === 'cancelled' ? 'btn-primary' : 'btn-light border text-muted' ?> px-3 py-1.5 fw-semibold">
                <i class="fas fa-ban text-danger me-1"></i> Cancelled
            </a>
        </div>

        <!-- Booking Table Card -->
        <div class="table-responsive table-sn border">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th class="py-3 px-4">Property</th>
                        <th class="py-3">Dates</th>
                        <th class="py-3">Amount</th>
                        <th class="py-3">Status</th>
                        <th class="py-3 text-end px-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($bookings)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-plane-departure text-muted fs-2 mb-3"></i>
                                <p class="text-muted mb-3">No trips found matching the selected criteria.</p>
                                <a href="<?= BASE_URL ?>pages/search.php" class="btn btn-teal btn-sm px-4">Browse Homestays</a>
                            </div>
                        </td>
                    </tr>
                <?php else: foreach ($bookings as $b): 
                    $hImage = display_image(['id' => $b['homestay_id'], 'cover_image' => $b['cover_image']]);
                ?>
                    <tr class="hover-shadow-sm transition">
                        <td class="py-3 px-4">
                            <div class="d-flex align-items-center gap-3">
                                <img src="<?= e($hImage) ?>" class="rounded-3 border" style="width:64px; height:48px; object-fit:cover" alt="">
                                <div>
                                    <span class="fw-bold text-dark d-block"><?= e($b['title']) ?></span>
                                    <small class="text-muted font-monospace text-uppercase" style="font-size:0.75rem"><?= e($b['booking_ref']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td class="py-3">
                            <div class="fw-semibold text-dark"><?= format_date($b['check_in']) ?></div>
                            <small class="text-muted d-block" style="font-size:0.78rem">to <?= format_date($b['check_out']) ?></small>
                        </td>
                        <td class="py-3">
                            <span class="fw-bold text-teal"><?= money($b['total_amount']) ?></span>
                        </td>
                        <td class="py-3">
                            <?= status_badge($b['status']) ?>
                        </td>
                        <td class="py-3 text-end px-4">
                            <div class="d-inline-flex gap-2">
                                <a href="<?= BASE_URL ?>user/booking-details.php?id=<?= (int)$b['id'] ?>" class="btn btn-sm btn-light border px-3 fw-semibold">View</a>
                                <?php if ($b['status'] === 'confirmed' || $b['status'] === 'checked_in' || $b['status'] === 'completed'): ?>
                                    <a href="<?= BASE_URL ?>pages/invoice.php?id=<?= (int)$b['id'] ?>" class="btn btn-sm btn-outline-primary px-3 fw-semibold" target="_blank" title="Print Invoice"><i class="fas fa-file-invoice"></i></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
