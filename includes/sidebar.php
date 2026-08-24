<?php
$sidebarRole = $sidebarRole ?? (is_owner() ? 'owner' : 'user');
$sidebarActive = $sidebarActive ?? '';
$base = BASE_URL . $sidebarRole . '/';
$unreadNotif = is_logged_in() ? unread_count() : 0;

if ($sidebarRole === 'owner') {
    $items = [
        'dashboard' => ['Dashboard', 'fa-tachometer-alt', 'dashboard.php'],
        'homestays' => ['Homestay Info', 'fa-home', 'manage-homestays.php'],
        'rooms' => ['Rooms', 'fa-bed', 'manage-rooms.php'],
        'gallery' => ['Gallery', 'fa-images', 'manage-gallery.php'],
        'bookings' => ['Bookings', 'fa-calendar-check', 'bookings.php'],
        'reviews' => ['Reviews', 'fa-star', 'reviews.php'],
        'earnings' => ['Earnings', 'fa-chart-line', 'earnings.php'],
        'profile' => ['Profile', 'fa-user', 'profile.php'],
        'notifications' => ['Notifications', 'fa-bell', 'notifications.php'],
    ];
} else {
    $items = [
        'dashboard' => ['Dashboard', 'fa-tachometer-alt', 'dashboard.php'],
        'bookings' => ['My Trips', 'fa-suitcase', 'booking-history.php'],
        'profile' => ['Profile', 'fa-user', 'profile.php'],
        'notifications' => ['Notifications', 'fa-bell', 'notifications.php'],
    ];
}
?>
<aside class="sn-sidebar" id="snSidebar">
    <!-- Sidebar User profile card -->
    <div class="sidebar-user border rounded-3 p-3 bg-light bg-opacity-30 mb-4 d-flex align-items-center gap-3">
        <img src="<?= profile_img($_SESSION['profile_image'] ?? null) ?>" class="sidebar-avatar rounded-circle border object-fit-cover shadow-sm" style="width: 44px; height: 44px" alt="Avatar">
        <div class="text-truncate">
            <div class="fw-bold text-dark text-truncate small mb-0.5" style="line-height: 1.2"><?= e($_SESSION['full_name']) ?></div>
            <small class="text-muted-50 text-uppercase fw-bold font-monospace" style="font-size: 0.65rem; letter-spacing: 0.05em"><?= e($sidebarRole === 'owner' ? 'Host / Owner' : 'Guest Traveler') ?></small>
        </div>
    </div>
    
    <!-- Navigation Menu -->
    <nav class="sidebar-nav d-flex flex-column gap-1">
        <?php foreach ($items as $key => $item): ?>
            <a href="<?= $base . $item[2] ?>" class="sidebar-link d-flex align-items-center justify-content-between <?= $sidebarActive === $key ? 'active' : '' ?>">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas <?= $item[1] ?>" style="width: 18px"></i> 
                    <span><?= e($item[0]) ?></span>
                </div>
                <!-- Inline Notifications Count Badge -->
                <?php if ($key === 'notifications' && $unreadNotif > 0): ?>
                    <span class="badge bg-teal rounded-pill text-white font-monospace px-2 py-1 fs-8" style="font-size:0.7rem"><?= $unreadNotif ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
        
        <hr class="my-3 text-muted opacity-25">
        
        <a href="<?= BASE_URL ?>authentication/logout.php" class="sidebar-link text-danger mt-auto">
            <i class="fas fa-sign-out-alt" style="width: 18px"></i> <span>Logout</span>
        </a>
    </nav>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
