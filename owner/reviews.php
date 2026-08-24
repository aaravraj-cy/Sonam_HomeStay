<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('owner');
$ownerId = get_owner_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $rid = (int)($_POST['review_id'] ?? 0);
    $reply = trim($_POST['owner_reply'] ?? '');
    $chk = $conn->prepare('SELECT r.id FROM reviews r JOIN homestays h ON h.id=r.homestay_id WHERE r.id=? AND h.owner_id=?');
    $chk->execute([$rid, $ownerId]);
    if ($chk->fetch() && $reply != '') {
        $conn->prepare('UPDATE reviews SET owner_reply=? WHERE id=?')->execute([$reply, $rid]);
        set_flash('success', 'Your host reply has been saved successfully.');
    }
    redirect(BASE_URL . 'owner/reviews.php');
}

$reviews = $conn->prepare('SELECT r.*, h.title AS property_title, u.full_name, u.profile_image FROM reviews r
    JOIN homestays h ON h.id=r.homestay_id JOIN users u ON u.id=r.user_id
    WHERE h.owner_id=? ORDER BY r.created_at DESC');
$reviews->execute([$ownerId]);
$reviews = $reviews->fetchAll();
if (empty($reviews)) {
    $reviews = fallback_owner_reviews();
}

$pageTitle = 'Guest Reviews';
$sidebarRole = 'owner';
$sidebarActive = 'reviews';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="dashboard-main animate__animated animate__fadeIn">
        <h1 class="h3 fw-bold text-dark mb-4">Guest Reviews & Ratings</h1>
        
        <div class="d-flex flex-column gap-3" style="max-width: 800px">
            <?php if (empty($reviews)): ?>
                <div class="text-center py-5 border rounded-4 bg-light bg-opacity-25 shadow-xs">
                    <i class="far fa-star fs-1 text-muted mb-3"></i>
                    <h5 class="fw-bold text-dark mb-1">No reviews received</h5>
                    <p class="text-muted small mb-0">Reviews submitted by guests will be listed here.</p>
                </div>
            <?php else: foreach ($reviews as $r): ?>
                <?php $isFallbackReview = !empty($r['is_fallback']); ?>
                <div class="dash-card border p-4">
                    <!-- Guest Profile Header -->
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <img src="<?= profile_img($r['profile_image'] ?? '') ?>" class="rounded-circle object-fit-cover shadow-xs border" style="width: 42px; height: 42px" alt="">
                            <div>
                                <strong class="text-dark small d-block"><?= e($r['full_name']) ?></strong>
                                <span class="small text-muted" style="font-size: 0.75rem;">Reviewed <strong><?= e($r['property_title'] ?? $r['title']) ?></strong> &middot; <?= time_elapsed($r['created_at']) ?></span>
                            </div>
                        </div>
                        <div class="text-warning">
                            <?= stars($r['rating']) ?>
                        </div>
                    </div>
                    
                    <!-- Comment -->
                    <?php if (!empty($r['title'])): ?>
                        <strong class="text-dark d-block mb-1 small"><?= e($r['title']) ?></strong>
                    <?php endif; ?>
                    <p class="small text-muted mb-3 bg-light p-3 rounded border" style="line-height: 1.5">"<?= e($r['comment']) ?>"</p>
                    
                    <!-- Owner Reply Widget -->
                    <?php if ($isFallbackReview): ?>
                        <div class="p-3 rounded-3 border bg-light bg-opacity-25">
                            <strong class="text-teal small d-block mb-1"><i class="fas fa-circle-info me-2"></i>Public preview review</strong>
                            <p class="small text-muted mb-0" style="line-height: 1.5">This mirrors the public site while no database reviews exist yet. Real guest reviews will be replyable here.</p>
                        </div>
                    <?php elseif ($r['owner_reply']): ?>
                        <div class="p-3 rounded-3 border bg-light bg-opacity-25" style="border-left: 3px solid var(--sn-teal) !important;">
                            <strong class="text-dark small d-block mb-1"><i class="fas fa-reply text-teal me-2"></i>Your Reply:</strong>
                            <p class="small text-muted mb-0" style="line-height: 1.5">"<?= e($r['owner_reply']) ?>"</p>
                        </div>
                    <?php else: ?>
                        <form method="POST" class="mt-3">
                            <?= csrf_field() ?>
                            <input type="hidden" name="review_id" value="<?= (int)$r['id'] ?>">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-1">Write a reply</label>
                            <div class="input-group input-group-sm">
                                <input type="text" name="owner_reply" class="form-control" placeholder="Thank the guest or respond to feedback..." required>
                                <button class="btn btn-teal px-3 fw-bold">Submit Reply</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
