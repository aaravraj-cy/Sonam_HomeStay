<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('user');
$uid = $_SESSION['user_id'];

// Get user profile details
$userStmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
$userStmt->execute([$uid]);
$user = $userStmt->fetch();

$upcoming = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status IN ('pending','confirmed','checked_in') AND (check_in >= CURDATE() OR status = 'checked_in')");
$upcoming->execute([$uid]);
$cUpcoming = (int)$upcoming->fetchColumn();

$totalB = $conn->prepare('SELECT COUNT(*) FROM bookings WHERE user_id = ?');
$totalB->execute([$uid]);
$cTotal = (int)$totalB->fetchColumn();

$unreadCount = unread_count();

$list = $conn->prepare("SELECT b.*, h.title, h.city, h.cover_image FROM bookings b JOIN homestays h ON h.id = b.homestay_id
    WHERE b.user_id = ? AND b.status IN ('pending','confirmed','checked_in') AND b.check_out >= CURDATE()
    ORDER BY b.check_in LIMIT 5");
$list->execute([$uid]);
$list = $list->fetchAll();

$pageTitle = 'Dashboard';
$sidebarRole = 'user';
$sidebarActive = 'dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout animate__animated animate__fadeIn">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-hero d-flex justify-content-between align-items-center mb-4">
            <button class="btn btn-outline-primary mobile-sidebar-toggle d-lg-none"><i class="fas fa-bars"></i> Menu</button>
            <div class="d-none d-lg-block">
                <h1 class="h3 display-font fw-bold mb-1">Hello, <?= e(first_name($_SESSION['full_name'] ?? '')) ?> <span class="wave">👋</span></h1>
                <p class="text-muted small mb-0">Welcome back to Sonam Homestay. Ready for your next journey?</p>
            </div>
            <!-- Profile avatar link -->
            <a href="<?= BASE_URL ?>user/profile.php" class="d-flex align-items-center gap-2 text-decoration-none">
                <img src="<?= profile_img($user['profile_image'] ?? null) ?>" class="rounded-circle border" style="width:40px; height:40px; object-fit:cover" alt="">
                <span class="d-none d-sm-inline fw-semibold text-dark small">My Account</span>
            </a>
        </div>

        <!-- Mobile header fallback -->
        <div class="d-lg-none mb-4">
            <h1 class="h3 display-font fw-bold mb-1">Hello, <?= e(first_name($_SESSION['full_name'] ?? '')) ?> <span class="wave">👋</span></h1>
            <p class="text-muted small mb-0">Welcome back. Ready for your next journey?</p>
        </div>

        <!-- Stat Cards Row -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card border">
                    <div class="stat-icon teal"><i class="fas fa-calendar-check"></i></div>
                    <div>
                        <div class="stat-value text-teal"><?= $cUpcoming ?></div>
                        <div class="stat-label">Upcoming Trips</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card border">
                    <div class="stat-icon blue"><i class="fas fa-suitcase"></i></div>
                    <div>
                        <div class="stat-value text-primary"><?= $cTotal ?></div>
                        <div class="stat-label">Total Bookings</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card border">
                    <div class="stat-icon yellow"><i class="fas fa-bell"></i></div>
                    <div>
                        <div class="stat-value text-warning"><?= $unreadCount ?></div>
                        <div class="stat-label">Unread Alerts</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Cards Row -->
        <div class="row g-4 mb-4">
            <!-- Upcoming Bookings Panel -->
            <div class="col-lg-8">
                <div class="dash-card border h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="m-0 fw-bold"><i class="far fa-calendar-alt me-2 text-teal"></i>Upcoming Bookings</h5>
                        <a href="<?= BASE_URL ?>user/booking-history.php" class="btn btn-sm btn-outline-primary py-1 px-3 fs-7">View History</a>
                    </div>
                    <?php if (empty($list)): ?>
                        <div class="empty-state py-5 border rounded-3 bg-light bg-opacity-25">
                            <i class="fas fa-plane-departure text-muted fs-2 mb-3"></i>
                            <p class="text-muted mb-3">No upcoming trips. Ready to explore new places?</p>
                            <a href="<?= BASE_URL ?>pages/search.php" class="btn btn-primary btn-sm height-auto width-"><i class="fas fa-search me-2"></i>Find a Stay</a>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($list as $b): 
                                $hImage = display_image(['id' => $b['homestay_id'], 'cover_image' => $b['cover_image']]);
                            ?>
                                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center border rounded-3 p-3 bg-white hover-shadow transition">
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?= e($hImage) ?>" class="rounded-3 border" style="width:70px; height:50px; object-fit:cover" alt="">
                                        <div>
                                            <a href="<?= BASE_URL ?>user/booking-details.php?id=<?= (int)$b['id'] ?>" class="fw-bold text-dark text-decoration-none hover-teal"><?= e($b['title']) ?></a>
                                            <div class="small text-muted d-flex align-items-center gap-1 mt-1">
                                                <i class="far fa-calendar-alt text-teal"></i> <?= format_date($b['check_in']) ?> - <?= format_date($b['check_out']) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-3 mt-3 mt-sm-0 justify-content-between justify-content-sm-end">
                                        <?= status_badge($b['status']) ?>
                                        <a href="<?= BASE_URL ?>user/booking-details.php?id=<?= (int)$b['id'] ?>" class="btn btn-sm btn-light border px-3">Manage</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions Sidebar Panel -->
            <div class="col-lg-4">
                <div class="dash-card border h-100 bg-white">
                    <h5 class="fw-bold mb-3"><i class="fas fa-lightbulb text-warning me-2"></i>Quick Actions</h5>
                    <div class="d-flex flex-column gap-2">
                        <a href="<?= BASE_URL ?>pages/search.php" class="btn btn-outline-primary text-start py-3 px-3 w-100 d-flex align-items-center justify-content-between rounded-3 border-light-subtle">
                            <span class="fw-semibold"><i class="fas fa-search me-2 text-teal"></i>Explore Stays</span>
                            <i class="fas fa-chevron-right fs-7 text-muted"></i>
                        </a>
                        <a href="<?= BASE_URL ?>user/profile.php" class="btn btn-outline-primary text-start py-3 px-3 w-100 d-flex align-items-center justify-content-between rounded-3 border-light-subtle">
                            <span class="fw-semibold"><i class="fas fa-user-edit me-2 text-primary"></i>Update Profile</span>
                            <i class="fas fa-chevron-right fs-7 text-muted"></i>
                        </a>
                        <a href="<?= BASE_URL ?>user/notifications.php" class="btn btn-outline-primary text-start py-3 px-3 w-100 d-flex align-items-center justify-content-between rounded-3 border-light-subtle">
                            <span class="fw-semibold"><i class="fas fa-bell me-2 text-warning"></i>Notifications</span>
                            <i class="fas fa-chevron-right fs-7 text-muted"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
