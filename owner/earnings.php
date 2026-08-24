<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('owner');
$ownerId = get_owner_id();
$month = trim($_GET['month'] ?? date('Y-m'));
if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');

$stmt = $conn->prepare("SELECT COALESCE(SUM(b.total_amount),0) AS total, COUNT(*) AS cnt
    FROM bookings b JOIN homestays h ON h.id=b.homestay_id
    JOIN payments p ON p.booking_id=b.id AND p.status='paid'
    WHERE h.owner_id=? AND b.status IN ('confirmed','checked_in','completed') AND DATE_FORMAT(b.created_at,'%Y-%m')=?");
$stmt->execute([$ownerId, $month]);
$sum = $stmt->fetch();

$list = $conn->prepare("SELECT b.booking_ref, b.total_amount, b.created_at, h.title
    FROM bookings b JOIN homestays h ON h.id=b.homestay_id
    JOIN payments p ON p.booking_id=b.id AND p.status='paid'
    WHERE h.owner_id=? AND b.status IN ('confirmed','checked_in','completed') AND DATE_FORMAT(b.created_at,'%Y-%m')=?
    ORDER BY b.created_at DESC");
$list->execute([$ownerId, $month]);
$list = $list->fetchAll();

$pageTitle = 'Earnings';
$sidebarRole = 'owner';
$sidebarActive = 'earnings';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="dashboard-main animate__animated animate__fadeIn">
        
        <!-- Header Bar -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 pb-3 border-bottom">
            <div>
                <h1 class="h3 fw-bold text-dark m-0">Earnings Console</h1>
                <p class="small text-muted mb-0">Track monthly payments and incoming bookings revenues.</p>
            </div>
            <div>
                <form method="GET" class="d-flex align-items-center gap-2">
                    <label class="small text-muted fw-bold text-uppercase mb-0 text-nowrap">Filter Month:</label>
                    <input type="month" name="month" class="form-control form-control-sm" value="<?= e($month) ?>" onchange="this.form.submit()" style="max-width: 160px">
                </form>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="stat-card border">
                    <div class="stat-icon teal shadow-xs"><i class="fas fa-sack-dollar"></i></div>
                    <div>
                        <div class="stat-value text-teal-deep"><?= money($sum['total']) ?></div>
                        <div class="stat-label">Month Revenues</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card border">
                    <div class="stat-icon blue shadow-xs"><i class="fas fa-check-double"></i></div>
                    <div>
                        <div class="stat-value text-dark"><?= (int)$sum['cnt'] ?></div>
                        <div class="stat-label">Verified Paid Bookings</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions Card -->
        <div class="dash-card border p-4">
            <h5 class="fw-bold text-dark mb-3"><i class="fas fa-receipt text-teal me-2"></i>Incoming Payments</h5>
            <div class="d-flex flex-column">
                <?php foreach ($list as $t): ?>
                    <div class="d-flex align-items-center justify-content-between border-bottom py-3 last-no-border">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-light p-2.5 rounded-3 border">
                                <i class="fas fa-arrow-down-long text-teal"></i>
                            </div>
                            <div>
                                <strong class="text-dark small d-block">#<?= e($t['booking_ref']) ?></strong>
                                <span class="small text-muted d-block"><?= e($t['title']) ?></span>
                                <span class="small text-muted d-block" style="font-size: 0.75rem"><?= format_date($t['created_at']) ?></span>
                            </div>
                        </div>
                        <strong class="text-teal-deep"><?= money($t['total_amount']) ?></strong>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($list)): ?>
                    <div class="text-center py-5">
                        <i class="far fa-folder-open fs-2 text-muted mb-2"></i>
                        <p class="text-muted small mb-0">No transactions recorded for this month.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
