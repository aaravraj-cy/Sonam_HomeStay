<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('user');

$id = (int)($_GET['id'] ?? 0);
$uid = $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT b.*, h.title, h.city, h.cover_image, p.status AS payment_status, p.transaction_id
    FROM bookings b JOIN homestays h ON h.id = b.homestay_id
    LEFT JOIN payments p ON p.booking_id = b.id
    WHERE b.id = ? AND b.user_id = ?');
$stmt->execute([$id, $uid]);
$b = $stmt->fetch();
if (!$b) {
    set_flash('error', 'Booking not found.');
    redirect(BASE_URL . 'user/booking-history.php');
}

$details = $conn->prepare('SELECT * FROM booking_details WHERE booking_id = ?');
$details->execute([$id]);
$details = $details->fetchAll();

$rev = $conn->prepare('SELECT * FROM reviews WHERE booking_id = ?');
$rev->execute([$id]);
$existingReview = $rev->fetch();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'cancel' && in_array($b['status'], ['pending', 'confirmed'])) {
        $reason = trim($_POST['cancellation_reason'] ?? '');
        $conn->prepare("UPDATE bookings SET status='cancelled', cancellation_reason=? WHERE id=?")->execute([$reason ?: null, $id]);
        $o = $conn->prepare('SELECT o.user_id FROM owners o JOIN homestays h ON h.owner_id=o.id WHERE h.id=?');
        $o->execute([$b['homestay_id']]);
        $ou = $o->fetchColumn();
        if ($ou) add_notification($ou, 'Booking cancelled', $b['booking_ref'] . ' was cancelled.', BASE_URL . 'owner/bookings.php');
        set_flash('success', 'Booking cancelled successfully.');
        redirect(BASE_URL . 'user/booking-details.php?id=' . $id);
    }

    if ($action === 'review' && !$existingReview) {
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        $title = trim($_POST['title'] ?? '');
        if ($rating < 1 || $rating > 5 || strlen($comment) < 5) {
            $error = 'Please select a rating and write a comment (min 5 characters).';
        } else {
            $conn->prepare('INSERT INTO reviews (user_id, homestay_id, booking_id, rating, title, comment) VALUES (?,?,?,?,?,?)')
                ->execute([$uid, $b['homestay_id'], $id, $rating, $title ?: null, $comment]);
            set_flash('success', 'Thank you for submitting your review!');
            redirect(BASE_URL . 'user/booking-details.php?id=' . $id);
        }
    }
    $stmt->execute([$id, $uid]);
    $b = $stmt->fetch();
}

$pageTitle = 'Booking Details';
$sidebarRole = 'user';
$sidebarActive = 'bookings';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="dashboard-main animate__animated animate__fadeIn">
        
        <!-- Header Bar -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 pb-3 border-bottom">
            <div>
                <a href="<?= BASE_URL ?>user/booking-history.php" class="btn btn-light border btn-sm mb-2 text-muted fw-semibold">
                    <i class="fas fa-arrow-left me-1"></i> Back to Trips
                </a>
                <h1 class="h3 fw-bold m-0 text-dark">Trip #<?= e($b['booking_ref']) ?></h1>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?= status_badge($b['status']) ?>
                <a href="<?= BASE_URL ?>pages/invoice.php?id=<?= $id ?>" class="btn btn-teal btn-sm fw-semibold">
                    <i class="fas fa-file-invoice me-1"></i> View Invoice
                </a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 small py-2.5 px-3 rounded-3 mb-4 animate__animated animate__shakeX">
                <i class="fas fa-circle-exclamation fs-6"></i>
                <div><?= e($error) ?></div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Left Side Details Card -->
            <div class="col-lg-7">
                <div class="dash-card border p-4 mb-4">
                    <div class="d-flex gap-3 mb-4 align-items-center">
                        <img src="<?= e(display_image($b)) ?>" class="rounded-3 object-fit-cover shadow-sm" style="width: 120px; height: 90px" alt="Cover">
                        <div>
                            <span class="small text-teal fw-bold text-uppercase"><i class="fas fa-map-marker-alt me-1"></i><?= e($b['city']) ?></span>
                            <h5 class="fw-bold mb-1 mt-0.5 text-dark"><?= e($b['title']) ?></h5>
                            <p class="small text-muted mb-0">Stay Details: <?= format_date($b['check_in']) ?> to <?= format_date($b['check_out']) ?></p>
                        </div>
                    </div>

                    <!-- Stay timeline grid -->
                    <div class="row g-3 mb-4 text-center">
                        <div class="col-6 col-sm-4">
                            <div class="bg-light p-2.5 rounded-3 border">
                                <span class="text-muted d-block small mb-1">Check-in</span>
                                <strong class="text-dark small"><i class="far fa-calendar-check text-teal me-2"></i><?= format_date($b['check_in']) ?></strong>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4">
                            <div class="bg-light p-2.5 rounded-3 border">
                                <span class="text-muted d-block small mb-1">Check-out</span>
                                <strong class="text-dark small"><i class="far fa-calendar-xmark text-teal me-2"></i><?= format_date($b['check_out']) ?></strong>
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="bg-light p-2.5 rounded-3 border">
                                <span class="text-muted d-block small mb-1">Guest Occupants</span>
                                <strong class="text-dark small"><i class="fas fa-user-friends text-teal me-2"></i><?= (int)$b['guests'] ?> Guest<?= $b['guests'] > 1 ? 's' : '' ?></strong>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3 text-teal"><i class="fas fa-receipt me-2"></i>Invoice Breakdown</h6>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle small">
                            <tbody>
                                <?php foreach ($details as $d): ?>
                                <tr>
                                    <td><?= e($d['room_name']) ?> &times; <?= (int)$d['nights'] ?> night<?= $d['nights'] > 1 ? 's' : '' ?></td>
                                    <td class="text-end fw-semibold text-dark"><?= money($d['amount']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if ((float)$b['cleaning_fee'] > 0): ?>
                                <tr>
                                    <td class="text-muted">Cleaning Fee</td>
                                    <td class="text-end fw-semibold text-dark"><?= money($b['cleaning_fee']) ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td class="text-muted">Sonam Homestay Service Fee</td>
                                    <td class="text-end fw-semibold text-dark"><?= money($b['service_fee']) ?></td>
                                </tr>
                                <tr class="table-light border-top">
                                    <td class="fw-bold text-dark fs-6 py-2">Total Price Charged</td>
                                    <td class="text-end fw-bold text-teal-deep fs-6 py-2"><?= money($b['total_amount']) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if (!empty($b['special_requests'])): ?>
                <div class="dash-card border p-4 mb-4">
                    <h6 class="fw-bold text-dark mb-2"><i class="far fa-note-sticky text-teal me-2"></i>Your Special Requests</h6>
                    <p class="small text-muted mb-0" style="line-height: 1.5 bg-light p-3 rounded border">"<?= nl2br(e($b['special_requests'])) ?>"</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Side Controls Grid -->
            <div class="col-lg-5">
                <!-- Review Console -->
                <?php if (!$existingReview && in_array($b['status'], ['confirmed','checked_in','completed'])): ?>
                <div class="dash-card border p-4 mb-4">
                    <h5 class="fw-bold text-dark mb-1">Write a Review</h5>
                    <p class="text-muted small mb-3">Share your local homestay experience with other travelers.</p>
                    
                    <form method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="review">
                        
                        <!-- Star Selection Interface -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark d-block">Overall Rating</label>
                            <div class="star-rating d-flex gap-2 fs-3 text-warning mb-2">
                                <i class="far fa-star cursor-pointer star-option" data-value="1"></i>
                                <i class="far fa-star cursor-pointer star-option" data-value="2"></i>
                                <i class="far fa-star cursor-pointer star-option" data-value="3"></i>
                                <i class="far fa-star cursor-pointer star-option" data-value="4"></i>
                                <i class="far fa-star cursor-pointer star-option" data-value="5"></i>
                            </div>
                            <input type="hidden" name="rating" id="review_rating_input" value="" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Review Title</label>
                            <input type="text" name="title" class="form-control" placeholder="Summarize your stay" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Detailed Feedback</label>
                            <textarea name="comment" class="form-control" rows="3" placeholder="What did you love about this stay?" required></textarea>
                        </div>

                        <button class="btn btn-teal btn-sm w-100 py-2 fw-bold"><i class="far fa-paper-plane me-2"></i> Submit Review</button>
                    </form>
                </div>
                <?php elseif ($existingReview): ?>
                <div class="dash-card border p-4 mb-4">
                    <h5 class="fw-bold text-dark mb-1">Your Review</h5>
                    <p class="text-muted small mb-3">You have shared your feedback for this stay.</p>
                    <div class="border rounded-3 p-3 bg-light bg-opacity-25">
                        <div class="mb-2 text-warning fs-5">
                            <?= stars($existingReview['rating']) ?>
                        </div>
                        <?php if (!empty($existingReview['title'])): ?>
                            <strong class="text-dark d-block mb-1"><?= e($existingReview['title']) ?></strong>
                        <?php endif; ?>
                        <p class="small text-muted mb-0" style="line-height: 1.5">"<?= e($existingReview['comment']) ?>"</p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Cancellation Widget -->
                <?php if (in_array($b['status'], ['pending','confirmed'])): ?>
                <div class="dash-card border border-danger border-opacity-10 p-4 mb-4">
                    <h5 class="fw-bold text-danger mb-1">Cancel Booking</h5>
                    <p class="text-muted small mb-3">Need to change your travel plans? Submit details to cancel this stay.</p>
                    
                    <form method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="cancel">
                        <div class="mb-3">
                            <textarea name="cancellation_reason" class="form-control" rows="2" placeholder="Tell us why you are cancelling..." required></textarea>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm w-100 py-2 btn-confirm" data-confirm="Are you sure you want to cancel this booking?"><i class="fas fa-ban me-2"></i> Cancel Booking</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Dynamic review star select trigger
    var stars = document.querySelectorAll('.star-option');
    var ratingInput = document.getElementById('review_rating_input');
    
    stars.forEach(function (star) {
        star.addEventListener('click', function () {
            var val = parseInt(this.getAttribute('data-value') || 0);
            ratingInput.value = val;
            
            // Toggle star solid/regular class formats
            stars.forEach(function (s, idx) {
                if (idx < val) {
                    s.className = 'fas fa-star cursor-pointer star-option text-warning';
                } else {
                    s.className = 'far fa-star cursor-pointer star-option text-warning';
                }
            });
        });
    });
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
