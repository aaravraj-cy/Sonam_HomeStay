<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('owner');
$ownerId = get_owner_id();
ensure_owner_sonam_inventory($ownerId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    $chk = $conn->prepare('SELECT * FROM homestays WHERE id = ? AND owner_id = ?');
    $chk->execute([$id, $ownerId]);
    $hs = $chk->fetch();

    if ($hs && $action === 'delete') {
        $active = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE homestay_id=? AND status IN ('pending','confirmed')");
        $active->execute([$id]);
        if ((int)$active->fetchColumn() > 0) {
            set_flash('error', 'Cannot delete homestay: active upcoming bookings exist.');
        } else {
            $conn->prepare('DELETE FROM homestays WHERE id=?')->execute([$id]);
            set_flash('success', 'Homestay deleted successfully.');
        }
    }
    if ($hs && $action === 'toggle') {
        $conn->prepare('UPDATE homestays SET is_active = IF(is_active=1,0,1) WHERE id=?')->execute([$id]);
        set_flash('success', 'Property active status updated.');
    }
    redirect(BASE_URL . 'owner/manage-homestays.php');
}

$list = $conn->prepare('SELECT h.*, 
    (SELECT COUNT(*) FROM rooms WHERE homestay_id=h.id) AS room_count,
    (SELECT AVG(rating) FROM reviews WHERE homestay_id=h.id AND is_approved=1) AS avg_rating,
    (SELECT COUNT(*) FROM reviews WHERE homestay_id=h.id AND is_approved=1) AS review_count
    FROM homestays h 
    WHERE owner_id=? 
    ORDER BY created_at DESC');
$list->execute([$ownerId]);
$list = $list->fetchAll();

// Count stats
$totalStays = count($list);
$activeStays = 0;
foreach ($list as $hsItem) {
    if ($hsItem['is_active']) $activeStays++;
}
$inactiveStays = $totalStays - $activeStays;

$pageTitle = 'Manage Homestays';
$sidebarRole = 'owner';
$sidebarActive = 'homestays';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="dashboard-main animate__animated animate__fadeIn">
        
        <!-- Header Bar -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 pb-3 border-bottom">
            <div>
                <h1 class="h3 fw-bold text-dark m-0">Homestay Info</h1>
                <p class="small text-muted mb-0">Manage details, room inventories, and booking states for Sonam Homestay.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= BASE_URL ?>" class="btn btn-light border btn-sm fw-bold">
                    <i class="fas fa-up-right-from-square me-1"></i> View Public Site
                </a>
                <a href="<?= BASE_URL ?>owner/add-homestay.php" class="btn btn-teal btn-sm fw-bold">
                    <i class="fas fa-circle-plus me-1"></i> Add Homestay
                </a>
            </div>
        </div>

        <!-- Property Stats Header -->
        <div class="row g-3 mb-4">
            <div class="col-4 col-md-4">
                <div class="bg-light p-3 rounded-3 border text-center">
                    <span class="text-muted d-block small mb-1 fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.04em">Total Properties</span>
                    <strong class="text-dark fs-5"><?= $totalStays ?></strong>
                </div>
            </div>
            <div class="col-4 col-md-4">
                <div class="bg-light p-3 rounded-3 border text-center">
                    <span class="text-muted d-block small mb-1 fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.04em">Active Listings</span>
                    <strong class="text-teal fs-5"><?= $activeStays ?></strong>
                </div>
            </div>
            <div class="col-4 col-md-4">
                <div class="bg-light p-3 rounded-3 border text-center">
                    <span class="text-muted d-block small mb-1 fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.04em">Inactive Listings</span>
                    <strong class="text-danger fs-5"><?= $inactiveStays ?></strong>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <?php if (empty($list)): ?>
                <div class="col-12 text-center py-5">
                    <div class="bg-light p-4 rounded-4 border max-width-auto d-inline-block px-5">
                        <i class="fas fa-mountain fs-1 text-muted mb-3"></i>
                        <h5 class="fw-bold text-dark mb-1">No homestays published</h5>
                        <p class="text-muted small mb-3">Get started by listing your homestay property.</p>
                        <a href="<?= BASE_URL ?>owner/add-homestay.php" class="btn btn-teal btn-sm fw-bold">List Homestay</a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($list as $h): ?>
                <div class="col-md-6">
                    <div class="dash-card border p-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <!-- Header with Status -->
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?= e(display_image($h)) ?>" class="rounded object-fit-cover shadow-xs border" style="width: 80px; height: 60px" alt="Cover">
                                    <div>
                                        <h5 class="fw-bold text-dark mb-1 mt-0"><?= e($h['title']) ?></h5>
                                        <span class="small text-muted"><i class="fas fa-map-marker-alt text-teal me-1"></i><?= e($h['city']) ?></span>
                                    </div>
                                </div>
                                <?= status_badge($h['is_active'] ? 'active' : 'inactive') ?>
                            </div>
                            
                            <p class="text-muted small mb-3" style="line-height: 1.5; height: 3em; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                <?= e($h['description']) ?>
                            </p>
                            
                            <!-- Rooms and Ratings details -->
                            <div class="d-flex align-items-center gap-4 mb-4 small">
                                <span class="text-dark"><i class="fas fa-bed text-teal me-2"></i><?= (int)$h['room_count'] ?> Room<?= $h['room_count'] > 1 ? 's' : '' ?></span>
                                <?php if ($h['review_count'] > 0): ?>
                                    <span class="text-warning">
                                        <?= stars($h['avg_rating']) ?> 
                                        <span class="text-muted font-monospace ms-1" style="font-size: 0.75rem">(<?= (int)$h['review_count'] ?>)</span>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted"><i class="far fa-star me-1"></i>No reviews</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="d-flex flex-wrap gap-2 pt-3 border-top mt-auto">
                            <a href="<?= BASE_URL ?>owner/edit-homestay.php?id=<?= (int)$h['id'] ?>" class="btn btn-sm btn-light border fw-semibold">
                                <i class="far fa-edit me-1"></i> Edit Details
                            </a>
                            <a href="<?= BASE_URL ?>owner/manage-rooms.php?homestay_id=<?= (int)$h['id'] ?>" class="btn btn-sm btn-outline-teal fw-semibold">
                                <i class="fas fa-bed me-1"></i> Manage Rooms
                            </a>
                            <a href="<?= BASE_URL ?>owner/manage-gallery.php" class="btn btn-sm btn-outline-teal fw-semibold">
                                <i class="far fa-images me-1"></i> Gallery
                            </a>
                            <a href="<?= BASE_URL ?>pages/homestay-details.php?id=<?= (int)$h['id'] ?>" class="btn btn-sm btn-light border fw-semibold">
                                <i class="fas fa-up-right-from-square me-1"></i> Public View
                            </a>
                            <form method="POST" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
                                <button class="btn btn-sm btn-outline-warning fw-semibold">
                                    <i class="fas fa-power-off me-1"></i> <?= $h['is_active'] ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
