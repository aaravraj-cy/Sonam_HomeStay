<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('owner');
$ownerId = get_owner_id();
ensure_owner_sonam_inventory($ownerId);

$uid = $_SESSION['user_id'];
$userStmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
$userStmt->execute([$uid]);
$user = $userStmt->fetch();

$c1 = $conn->prepare('SELECT COUNT(*) FROM homestays WHERE owner_id = ?');
$c1->execute([$ownerId]);
$homestays = (int)$c1->fetchColumn();

$c2 = $conn->prepare('SELECT COUNT(*) FROM bookings b JOIN homestays h ON h.id=b.homestay_id WHERE h.owner_id = ?');
$c2->execute([$ownerId]);
$bookings = (int)$c2->fetchColumn();

$c3 = $conn->prepare("SELECT COUNT(*) FROM bookings b JOIN homestays h ON h.id=b.homestay_id WHERE h.owner_id = ? AND b.status='pending'");
$c3->execute([$ownerId]);
$pending = (int)$c3->fetchColumn();

$cancelledStmt = $conn->prepare("SELECT COUNT(*) FROM bookings b JOIN homestays h ON h.id=b.homestay_id WHERE h.owner_id = ? AND b.status='cancelled'");
$cancelledStmt->execute([$ownerId]);
$cancelled = (int)$cancelledStmt->fetchColumn();

$rejectedStmt = $conn->prepare("SELECT COUNT(*) FROM bookings b JOIN homestays h ON h.id=b.homestay_id WHERE h.owner_id = ? AND b.status='rejected'");
$rejectedStmt->execute([$ownerId]);
$rejected = (int)$rejectedStmt->fetchColumn();

$roomCountStmt = $conn->prepare('SELECT COUNT(*) FROM rooms r JOIN homestays h ON h.id=r.homestay_id WHERE h.owner_id = ?');
$roomCountStmt->execute([$ownerId]);
$roomCount = (int)$roomCountStmt->fetchColumn();
if ($roomCount === 0) {
    $roomCount = count(fallback_public_rooms());
}

$galleryCountStmt = $conn->prepare('SELECT COUNT(*) FROM gallery_images WHERE owner_id = ?');
$galleryCountStmt->execute([$ownerId]);
$galleryCount = (int)$galleryCountStmt->fetchColumn();
if ($galleryCount === 0) {
    $galleryCount = count(fallback_gallery_items(10));
}

$reviewCountStmt = $conn->prepare('SELECT COUNT(*) FROM reviews r JOIN homestays h ON h.id=r.homestay_id WHERE h.owner_id = ?');
$reviewCountStmt->execute([$ownerId]);
$reviewCount = (int)$reviewCountStmt->fetchColumn();
if ($reviewCount === 0) {
    $reviewCount = count(fallback_owner_reviews());
}

$c4 = $conn->prepare("SELECT COALESCE(SUM(b.total_amount),0) FROM bookings b JOIN homestays h ON h.id=b.homestay_id
    JOIN payments p ON p.booking_id=b.id AND p.status='paid'
    WHERE h.owner_id=? AND b.status IN ('confirmed','checked_in','completed')
    AND MONTH(b.created_at)=MONTH(CURDATE()) AND YEAR(b.created_at)=YEAR(CURDATE())");
$c4->execute([$ownerId]);
$earnings = (float)$c4->fetchColumn();

$recent = $conn->prepare("SELECT b.*, h.title, u.full_name AS guest, u.profile_image FROM bookings b
    JOIN homestays h ON h.id=b.homestay_id JOIN users u ON u.id=b.user_id
    WHERE h.owner_id=? ORDER BY b.created_at DESC LIMIT 6");
$recent->execute([$ownerId]);
$recent = $recent->fetchAll();

// Fetch latest guest review
$latestReview = $conn->prepare("SELECT r.*, h.title AS property_title, u.full_name AS guest, u.profile_image FROM reviews r
    JOIN homestays h ON h.id=r.homestay_id JOIN users u ON u.id=r.user_id
    WHERE h.owner_id=? ORDER BY r.created_at DESC LIMIT 1");
$latestReview->execute([$ownerId]);
$latestReview = $latestReview->fetch();
if (!$latestReview) {
    $latestReview = fallback_owner_reviews()[0];
}

$pageTitle = 'Owner Dashboard';
$sidebarRole = 'owner';
$sidebarActive = 'dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="dashboard-main animate__animated animate__fadeIn">
        
        <!-- Pending Approvals Alert Banner -->
        <?php if ($pending > 0): ?>
            <div class="alert alert-warning border-warning border-opacity-10 d-flex flex-wrap justify-content-between align-items-center rounded-4 p-3 mb-4 animate__animated animate__pulse animate__infinite animate__slower gap-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-warning bg-opacity-15 text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px">
                        <i class="fas fa-bell-concierge"></i>
                    </div>
                    <div>
                        <strong class="text-dark small d-block">Pending Bookings Awaiting Review</strong>
                        <span class="text-muted small">You have <?= $pending ?> new guest reservation request<?= $pending > 1 ? 's' : '' ?> waiting.</span>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>owner/bookings.php?status=pending" class="btn btn-warning btn-sm fw-bold px-3">Review Requests</a>
            </div>
        <?php endif; ?>

        <!-- Welcome Banner Profile Header -->
        <div class="dashboard-hero d-flex flex-wrap align-items-center justify-content-between gap-3 p-4 mb-4">
            <div class="d-flex align-items-center gap-3">
                <img src="<?= profile_img($user['profile_image']) ?>" class="rounded-circle border border-2 border-teal shadow-sm" style="width: 64px; height: 64px; object-fit: cover;" alt="Avatar">
                <div>
                    <h2 class="h4 fw-bold m-0 text-dark">Hello, <?= e($_SESSION['full_name']) ?>! <span class="wave-hand">👋</span></h2>
                    <p class="small text-muted mb-0">Manage Sonam Homestay rooms, bookings, and reviews in one dashboard.</p>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Side Statistics & Lists -->
            <div class="col-lg-8">
                <!-- Stat Cards Row -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="stat-card border">
                            <div class="stat-icon teal shadow-xs"><i class="fas fa-mountain"></i></div>
                            <div>
                                <div class="stat-value text-dark"><?= $homestays ?></div>
                                <div class="stat-label">Properties</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-card border">
                            <div class="stat-icon teal shadow-xs"><i class="fas fa-bed"></i></div>
                            <div>
                                <div class="stat-value text-dark"><?= $roomCount ?></div>
                                <div class="stat-label">Rooms</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-card border">
                            <div class="stat-icon blue shadow-xs"><i class="far fa-images"></i></div>
                            <div>
                                <div class="stat-value text-dark"><?= $galleryCount ?></div>
                                <div class="stat-label">Photos</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-card border">
                            <div class="stat-icon amber shadow-xs"><i class="far fa-star"></i></div>
                            <div>
                                <div class="stat-value text-dark"><?= $reviewCount ?></div>
                                <div class="stat-label">Reviews</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-card border">
                            <div class="stat-icon coral shadow-xs"><i class="fas fa-ban"></i></div>
                            <div>
                                <div class="stat-value text-dark"><?= $cancelled + $rejected ?></div>
                                <div class="stat-label">Cancelled / Rejected</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-card border">
                            <div class="stat-icon blue shadow-xs"><i class="fas fa-calendar-check"></i></div>
                            <div>
                                <div class="stat-value text-dark"><?= $bookings ?></div>
                                <div class="stat-label">Bookings</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-card border">
                            <div class="stat-icon amber shadow-xs"><i class="fas fa-hourglass-half"></i></div>
                            <div>
                                <div class="stat-value text-dark"><?= $pending ?></div>
                                <div class="stat-label">Pending</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-card border">
                            <div class="stat-icon coral shadow-xs"><i class="fas fa-wallet"></i></div>
                            <div>
                                <div class="stat-value text-dark text-truncate" style="max-width: 90px; font-size:1.15rem"><?= money($earnings) ?></div>
                                <div class="stat-label">This Month</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Bookings Console -->
                <div class="dash-card border p-4 mb-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="far fa-clock text-teal me-2"></i>Recent Bookings</h5>
                    <?php if (empty($recent)): ?>
                        <div class="text-center py-4">
                            <i class="far fa-folder-open fs-2 text-muted mb-2"></i>
                            <p class="text-muted small mb-0">No bookings recorded yet.</p>
                        </div>
                    <?php else: foreach ($recent as $b): ?>
                        <div class="d-flex align-items-center justify-content-between border-bottom py-3 last-no-border">
                            <div class="d-flex align-items-center gap-3">
                                <img src="<?= profile_img($b['profile_image'] ?? '') ?>" class="rounded-circle object-fit-cover shadow-xs border" style="width: 42px; height: 42px" alt="">
                                <div>
                                    <strong class="text-dark small d-block"><?= e($b['guest']) ?></strong>
                                    <span class="small text-muted d-block"><?= e($b['title']) ?></span>
                                    <span class="small text-muted d-block" style="font-size: 0.75rem"><?= format_date($b['check_in']) ?> to <?= format_date($b['check_out']) ?></span>
                                </div>
                            </div>
                            <div class="text-end">
                                <strong class="text-teal-deep d-block small mb-1"><?= money($b['total_amount']) ?></strong>
                                <?= status_badge($b['status']) ?>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                    <a href="<?= BASE_URL ?>owner/bookings.php" class="btn btn-sm btn-outline-primary mt-3 fw-semibold">View All Bookings</a>
                </div>

                <!-- Latest Guest Feedback -->
                <?php if ($latestReview): ?>
                <div class="dash-card border p-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="far fa-star text-teal me-2"></i>Latest Review</h5>
                    <div class="border rounded-3 p-3 bg-light bg-opacity-25">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= profile_img($latestReview['profile_image'] ?? '') ?>" class="rounded-circle object-fit-cover shadow-xs border" style="width: 36px; height: 36px" alt="">
                                <div>
                                    <strong class="text-dark small d-block"><?= e($latestReview['guest']) ?></strong>
                                    <span class="small text-muted" style="font-size: 0.7rem;">Stayed at <?= e($latestReview['property_title']) ?></span>
                                </div>
                            </div>
                            <div class="text-warning">
                                <?= stars($latestReview['rating']) ?>
                            </div>
                        </div>
                        <?php if (!empty($latestReview['title'])): ?>
                            <strong class="text-dark d-block mb-1 small"><?= e($latestReview['title']) ?></strong>
                        <?php endif; ?>
                        <p class="small text-muted mb-0" style="line-height: 1.5">"<?= e($latestReview['comment']) ?>"</p>
                    </div>
                    <a href="<?= BASE_URL ?>owner/reviews.php" class="btn btn-sm btn-outline-teal mt-3 fw-semibold">Manage All Reviews</a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Side Quick Actions Grid -->
            <div class="col-lg-4">
                <!-- Revenue Goal Progress Tracker -->
                <div class="dash-card border p-4 mb-4">
                    <h5 class="fw-bold text-dark mb-2"><i class="fas fa-bullseye text-teal me-2"></i>Monthly Progress</h5>
                    <p class="small text-muted mb-3">Track progress towards your monthly target of <?= money(50000) ?>.</p>
                    <?php
                        $target = 50000;
                        $pct = min(100, round(($earnings / $target) * 100));
                    ?>
                    <div class="d-flex justify-content-between small fw-bold text-dark mb-1">
                        <span>Target: <?= $pct ?>%</span>
                        <span><?= money($earnings) ?> / <?= money($target) ?></span>
                    </div>
                    <div class="progress rounded-pill bg-light border" style="height: 10px">
                        <div class="progress-bar bg-teal rounded-pill" role="progressbar" style="width: <?= $pct ?>%" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                <div class="dash-card border p-4 mb-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="fas fa-bolt text-teal me-2"></i>Quick Actions</h5>
                    <div class="d-flex flex-column gap-2">
                        <a href="<?= BASE_URL ?>owner/manage-rooms.php" class="btn btn-light border text-start d-flex align-items-center justify-content-between p-2.5 rounded-3 text-dark small fw-semibold">
                            <span><i class="fas fa-bed text-teal me-2"></i>Manage Rooms</span>
                            <i class="fas fa-chevron-right text-muted small"></i>
                        </a>
                        <a href="<?= BASE_URL ?>owner/bookings.php" class="btn btn-light border text-start d-flex align-items-center justify-content-between p-2.5 rounded-3 text-dark small fw-semibold">
                            <span><i class="fas fa-calendar-alt text-teal me-2"></i>View Bookings</span>
                            <i class="fas fa-chevron-right text-muted small"></i>
                        </a>
                        <a href="<?= BASE_URL ?>owner/earnings.php" class="btn btn-light border text-start d-flex align-items-center justify-content-between p-2.5 rounded-3 text-dark small fw-semibold">
                            <span><i class="fas fa-wallet text-teal me-2"></i>Earnings Report</span>
                            <i class="fas fa-chevron-right text-muted small"></i>
                        </a>
                    </div>
                </div>

                <div class="dash-card border p-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="far fa-envelope text-teal me-2"></i>Quick Support</h5>
                    <p class="small text-muted mb-3" style="line-height: 1.5">Having trouble managing your property? Reach out to our local team in Khechuperi.</p>
                    <a href="mailto:support@sonamhomestay.local" class="btn btn-outline-teal btn-sm w-100 py-2 fw-bold"><i class="far fa-envelope me-2"></i>Contact Support</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
