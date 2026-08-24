<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('user');

$uid = $_SESSION['user_id'];

// Mark all read via form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (($_POST['action'] ?? '') === 'read_all') {
        $conn->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$uid]);
        set_flash('success', 'All notifications marked as read.');
        redirect(BASE_URL . 'user/notifications.php');
    }
    if (($_POST['action'] ?? '') === 'read_one') {
        $nid = (int)($_POST['id'] ?? 0);
        $conn->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?')->execute([$nid, $uid]);
        // Return JSON or redirect back
        if (!empty($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
        redirect(BASE_URL . 'user/notifications.php');
    }
}

$list = $conn->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
$list->execute([$uid]);
$list = $list->fetchAll();

$pageTitle = 'Notifications';
$sidebarRole = 'user';
$sidebarActive = 'notifications';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout animate__animated animate__fadeIn">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="dashboard-main">
        <!-- Dashboard Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <button class="btn btn-outline-primary mobile-sidebar-toggle d-lg-none"><i class="fas fa-bars"></i> Menu</button>
            <div>
                <h1 class="h3 display-font fw-bold mb-1"><i class="fas fa-bell text-teal me-2"></i>Notifications</h1>
                <p class="text-muted small mb-0">Keep track of stay confirmations, host notifications, and invoices.</p>
            </div>
            <?php if (!empty($list)): ?>
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="read_all">
                    <button class="btn btn-sm btn-outline-primary py-1.5 px-3 fw-semibold"><i class="fas fa-check-double me-1"></i>Mark all read</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Notification List Panel -->
        <div class="dash-card border p-0 overflow-hidden">
            <?php if (empty($list)): ?>
                <div class="empty-state py-5 bg-light bg-opacity-25" data-aos="fade-up">
                    <i class="far fa-bell-slash text-muted fs-1 mb-3"></i>
                    <p class="text-muted mb-0">You're all caught up! No notifications yet.</p>
                </div>
            <?php else: foreach ($list as $n): 
                $iconClass = 'fa-info-circle text-primary';
                if (stripos($n['title'], 'booking') !== false) {
                    $iconClass = 'fa-calendar text-teal';
                } elseif (stripos($n['title'], 'pay') !== false || stripos($n['title'], 'invoice') !== false) {
                    $iconClass = 'fa-file-invoice-dollar text-success';
                } elseif (stripos($n['title'], 'welcome') !== false) {
                    $iconClass = 'fa-gift text-danger';
                }
            ?>
                <div class="px-4 py-3 border-bottom transition hover-bg d-flex align-items-start gap-3 position-relative" style="<?= !$n['is_read'] ? 'background: rgba(13, 148, 136, 0.03)' : '' ?>">
                    <!-- Unread Indicator Dot -->
                    <?php if (!$n['is_read']): ?>
                        <span class="position-absolute start-0 top-50 translate-middle-y bg-teal rounded-circle" style="width: 6px; height: 6px; margin-left: 8px"></span>
                    <?php endif; ?>
                    
                    <!-- Icon flag -->
                    <div class="p-2 rounded bg-light d-flex align-items-center justify-content-center border" style="width: 38px; height: 38px; flex-shrink: 0">
                        <i class="fas <?= $iconClass ?>"></i>
                    </div>

                    <!-- Notification Body -->
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-baseline gap-2 mb-1">
                            <span class="fw-bold text-dark fs-6"><?= e($n['title']) ?></span>
                            <small class="text-muted font-monospace" style="font-size:0.75rem"><?= format_date($n['created_at']) ?></small>
                        </div>
                        <p class="text-muted small mb-2"><?= e($n['message']) ?></p>
                        
                        <!-- Action Links and Marks -->
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <?php if ($n['link']): ?>
                                    <a href="<?= e($n['link']) ?>" class="btn btn-sm btn-teal py-1 px-3 fw-semibold fs-7"><i class="fas fa-arrow-up-right-from-square me-1"></i>View details</a>
                                <?php endif; ?>
                            </div>
                            <?php if (!$n['is_read']): ?>
                                <form method="POST" style="margin:0">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="read_one">
                                    <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-link text-teal text-decoration-none py-0 px-2 fw-semibold fs-7"><i class="fas fa-check me-1"></i>Mark read</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>
