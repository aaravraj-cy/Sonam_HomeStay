<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('owner');
$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'read_all') {
        $conn->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$uid]);
        set_flash('success', 'All notifications marked as read.');
    }
    
    if ($action === 'read_single') {
        $nid = (int)($_POST['notification_id'] ?? 0);
        $conn->prepare('UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?')->execute([$nid, $uid]);
        set_flash('success', 'Notification marked as read.');
    }
    
    if ($action === 'delete_single') {
        $nid = (int)($_POST['notification_id'] ?? 0);
        $conn->prepare('DELETE FROM notifications WHERE id=? AND user_id=?')->execute([$nid, $uid]);
        set_flash('success', 'Notification deleted.');
    }
    
    if ($action === 'delete_all') {
        $conn->prepare('DELETE FROM notifications WHERE user_id=?')->execute([$uid]);
        set_flash('success', 'Notification history cleared.');
    }
    
    redirect(BASE_URL . 'owner/notifications.php');
}

$filter = trim($_GET['filter'] ?? 'all');
if (!in_array($filter, ['all', 'unread'])) {
    $filter = 'all';
}

$sql = 'SELECT * FROM notifications WHERE user_id=?';
$params = [$uid];

if ($filter === 'unread') {
    $sql .= ' AND is_read=0';
}

$sql .= ' ORDER BY created_at DESC LIMIT 50';
$list = $conn->prepare($sql);
$list->execute($params);
$list = $list->fetchAll();

$pageTitle = 'Notifications';
$sidebarRole = 'owner';
$sidebarActive = 'notifications';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="dashboard-main animate__animated animate__fadeIn">
        
        <!-- Header Bar -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 pb-3 border-bottom">
            <div>
                <h1 class="h3 fw-bold text-dark m-0">Notifications</h1>
                <p class="small text-muted mb-0">Stay informed about booking updates and payment receipts.</p>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <?php if (!empty($list)): ?>
                <form method="POST" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="read_all">
                    <button class="btn btn-sm btn-outline-teal fw-bold"><i class="fas fa-check-double me-2"></i>Mark all read</button>
                </form>
                
                <form method="POST" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete_all">
                    <button type="button" class="btn btn-sm btn-outline-danger fw-bold btn-confirm" data-confirm="Clear all notification history? This cannot be undone."><i class="far fa-trash-can me-2"></i>Clear History</button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="d-flex gap-2 mb-4">
            <a href="?" class="btn btn-sm <?= $filter === 'all' ? 'btn-teal' : 'btn-light border text-muted' ?> px-3 py-1.5 rounded-pill fw-semibold">
                All Alerts
            </a>
            <a href="?filter=unread" class="btn btn-sm <?= $filter === 'unread' ? 'btn-teal' : 'btn-light border text-muted' ?> px-3 py-1.5 rounded-pill fw-semibold">
                Unread Only
            </a>
        </div>

        <div class="dash-card border p-0 overflow-hidden" style="max-width: 800px">
            <div class="d-flex flex-column">
                <?php foreach ($list as $n):
                    $isUnread = !$n['is_read'];
                    $iconClass = 'fa-bell text-muted';
                    $bgClass = 'bg-light';
                    
                    if (stripos($n['title'], 'booking') !== false) {
                        $iconClass = 'fa-calendar-alt text-blue';
                        $bgClass = 'bg-blue bg-opacity-10';
                    } elseif (stripos($n['title'], 'pay') !== false || stripos($n['title'], 'earn') !== false) {
                        $iconClass = 'fa-wallet text-teal';
                        $bgClass = 'bg-teal bg-opacity-10';
                    } elseif (stripos($n['title'], 'cancel') !== false || stripos($n['title'], 'reject') !== false) {
                        $iconClass = 'fa-circle-xmark text-danger';
                        $bgClass = 'bg-danger bg-opacity-10';
                    }
                ?>
                    <div class="d-flex align-items-center justify-content-between p-3.5 border-bottom last-no-border position-relative <?= $isUnread ? 'bg-light bg-opacity-20' : '' ?>">
                        <!-- Unread Indicator Dot -->
                        <?php if ($isUnread): ?>
                            <span class="position-absolute start-0 top-50 translate-middle-y rounded-circle bg-teal" style="width: 5px; height: 5px; margin-left: 8px;"></span>
                        <?php endif; ?>

                        <div class="d-flex align-items-center gap-3 ps-2">
                            <div class="rounded-circle d-flex align-items-center justify-content-center <?= $bgClass ?>" style="width: 38px; height: 38px; min-width: 38px">
                                <i class="fas <?= $iconClass ?> small"></i>
                            </div>
                            <div>
                                <strong class="text-dark small d-block"><?= e($n['title']) ?></strong>
                                <p class="small text-muted mb-0 mt-0.5" style="line-height: 1.4"><?= e($n['message']) ?></p>
                                <span class="small text-muted" style="font-size: 0.7rem;"><?= time_elapsed($n['created_at']) ?></span>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2 ms-3">
                            <?php if ($n['link']): ?>
                                <a href="<?= e($n['link']) ?>" class="btn btn-sm btn-light border fw-semibold px-2.5 py-1">View</a>
                            <?php endif; ?>
                            
                            <?php if ($isUnread): ?>
                                <form method="POST" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="read_single">
                                    <input type="hidden" name="notification_id" value="<?= (int)$n['id'] ?>">
                                    <button class="btn btn-sm btn-light border text-teal py-1 px-2" title="Mark as read"><i class="fas fa-check"></i></button>
                                </form>
                            <?php endif; ?>

                            <form method="POST" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_single">
                                <input type="hidden" name="notification_id" value="<?= (int)$n['id'] ?>">
                                <button class="btn btn-sm btn-light border text-danger py-1 px-2" title="Delete notification"><i class="far fa-trash-can"></i></button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($list)): ?>
                    <div class="text-center py-5">
                        <i class="far fa-bell-slash fs-2 text-muted mb-2"></i>
                        <h6 class="fw-bold text-dark mb-1">No notifications</h6>
                        <p class="text-muted small mb-0">No alerts match your filter criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
