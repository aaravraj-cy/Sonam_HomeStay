<?php
$current = basename($_SERVER['PHP_SELF'] ?? '');
$unread = is_logged_in() ? unread_count() : 0;
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
?>
<nav class="navbar navbar-expand-lg sn-navbar sticky-top">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand sn-brand" href="<?= BASE_URL ?>">
            <span class="brand-mark"><i class="fas fa-home"></i></span>
            <span class="brand-text"><?= e(APP_NAME) ?></span>
        </a>
        
        <!-- Toggle Button for Mobile -->
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Navbar Items -->
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav mx-auto gap-lg-2">
                <li class="nav-item">
                    <a class="nav-link <?= ($current === 'index.php' || rtrim($requestUri, '/') === '/HomeStay') ? 'active' : '' ?>" href="<?= BASE_URL ?>">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current === 'rooms.php' || $current === 'room-details.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>pages/rooms.php">Rooms</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current === 'gallery.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>pages/gallery.php">Gallery</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>#why-us">Why Us</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>#guest-reviews">Reviews</a>
                </li>
                <?php if (is_logged_in() && is_owner()): ?>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($requestUri, '/owner/') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>owner/dashboard.php">Owner Panel</a>
                </li>
                <?php endif; ?>
            </ul>
            
            <!-- Navbar Right Actions -->
            <div class="d-flex align-items-center gap-3 sn-nav-actions mt-3 mt-lg-0">
                <?php if (is_logged_in()): ?>
                    <!-- Notification Bell -->
                    <a href="<?= BASE_URL ?><?= is_owner() ? 'owner' : 'user' ?>/notifications.php" class="btn btn-icon position-relative transition hover-shadow-sm" title="Notifications">
                        <i class="fas fa-bell text-secondary"></i>
                        <?php if ($unread > 0): ?>
                            <span class="notif-badge animate__animated animate__pulse animate__infinite" style="box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.2)"><?= $unread ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <!-- User Dropdown Menu -->
                    <div class="dropdown">
                        <button class="btn btn-user dropdown-toggle d-flex align-items-center gap-2 border px-2.5 py-1.5 rounded-pill bg-white hover-shadow transition" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?= profile_img($_SESSION['profile_image'] ?? null) ?>" class="nav-avatar rounded-circle border object-fit-cover" style="width: 28px; height: 28px" alt="">
                            <span class="d-none d-sm-inline fw-semibold text-dark fs-7"><?= e(first_name($_SESSION['full_name'] ?? '')) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 p-1 sn-dropdown animate__animated animate__fadeInUp animate__faster" style="min-width: 220px; border-radius: var(--sn-radius-md)">
                            <!-- Dropdown User Info Header -->
                            <li class="px-3 py-2.5 border-bottom bg-light bg-opacity-40 mb-1" style="border-radius: var(--sn-radius-md) var(--sn-radius-md) 0 0">
                                <span class="d-block fw-bold text-dark fs-7" style="line-height:1.2"><?= e($_SESSION['full_name'] ?? 'Guest') ?></span>
                                <span class="d-block text-muted text-truncate font-monospace" style="font-size: 0.72rem; margin-top: 1px"><?= e($_SESSION['email'] ?? '') ?></span>
                            </li>
                            
                            <?php if (is_owner()): ?>
                                <li><a class="dropdown-item py-2 px-3 rounded-2 fs-7" href="<?= BASE_URL ?>owner/dashboard.php"><i class="fas fa-chart-line text-muted me-2" style="width: 16px"></i>Dashboard</a></li>
                                <li><a class="dropdown-item py-2 px-3 rounded-2 fs-7" href="<?= BASE_URL ?>owner/manage-homestays.php"><i class="fas fa-home text-muted me-2" style="width: 16px"></i>My Homestays</a></li>
                                <li><a class="dropdown-item py-2 px-3 rounded-2 fs-7" href="<?= BASE_URL ?>owner/manage-gallery.php"><i class="fas fa-images text-muted me-2" style="width: 16px"></i>Photo Gallery</a></li>
                                <li><a class="dropdown-item py-2 px-3 rounded-2 fs-7" href="<?= BASE_URL ?>owner/bookings.php"><i class="fas fa-calendar-check text-muted me-2" style="width: 16px"></i>Bookings</a></li>
                                <li><a class="dropdown-item py-2 px-3 rounded-2 fs-7" href="<?= BASE_URL ?>owner/profile.php"><i class="fas fa-user-gear text-muted me-2" style="width: 16px"></i>Profile Settings</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item py-2 px-3 rounded-2 fs-7" href="<?= BASE_URL ?>user/dashboard.php"><i class="fas fa-gauge text-muted me-2" style="width: 16px"></i>Dashboard</a></li>
                                <li><a class="dropdown-item py-2 px-3 rounded-2 fs-7" href="<?= BASE_URL ?>user/booking-history.php"><i class="fas fa-suitcase text-muted me-2" style="width: 16px"></i>My Trips</a></li>
                                <li><a class="dropdown-item py-2 px-3 rounded-2 fs-7" href="<?= BASE_URL ?>user/profile.php"><i class="fas fa-user-gear text-muted me-2" style="width: 16px"></i>Profile Settings</a></li>
                            <?php endif; ?>
                            
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><a class="dropdown-item text-danger py-2 px-3 rounded-2 fs-7" href="<?= BASE_URL ?>authentication/logout.php"><i class="fas fa-sign-out-alt me-2" style="width: 16px"></i>Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <!-- Guest Authentication Actions -->
                    <a href="<?= BASE_URL ?>authentication/login.php" class="btn btn-light btn-sm border px-3 py-1.5 fw-semibold transition">Login</a>
                    <a href="<?= BASE_URL ?>authentication/register.php" class="btn btn-primary btn-sm px-3 py-1.5 fw-semibold transition shadow-sm">Sign up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
