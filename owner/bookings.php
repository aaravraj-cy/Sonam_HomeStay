<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('owner');
$ownerId = get_owner_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    $bid = (int)($_POST['booking_id'] ?? 0);

    $stmt = $conn->prepare("SELECT b.*, h.title FROM bookings b JOIN homestays h ON h.id=b.homestay_id WHERE b.id=? AND h.owner_id=?");
    $stmt->execute([$bid, $ownerId]);
    $booking = $stmt->fetch();

    if ($booking) {
        if ($booking['status'] === 'pending') {
            if ($action === 'accept') {
                $conn->prepare("UPDATE bookings SET status='confirmed' WHERE id=?")->execute([$bid]);
                add_notification($booking['user_id'], 'Booking confirmed', $booking['booking_ref'] . ' confirmed.', BASE_URL . 'user/booking-details.php?id=' . $bid);
                set_flash('success', 'Booking accepted successfully.');
            }
            if ($action === 'reject') {
                $reason = trim($_POST['reason'] ?? '');
                $conn->prepare("UPDATE bookings SET status='rejected', cancellation_reason=? WHERE id=?")->execute([$reason ?: null, $bid]);
                add_notification($booking['user_id'], 'Booking rejected', $booking['booking_ref'] . ' was rejected.', BASE_URL . 'user/booking-details.php?id=' . $bid);
                set_flash('success', 'Booking rejected successfully.');
            }
        } elseif ($booking['status'] === 'confirmed') {
            if ($action === 'check_in') {
                $conn->prepare("UPDATE bookings SET status='checked_in' WHERE id=?")->execute([$bid]);
                add_notification($booking['user_id'], 'Checked In', $booking['booking_ref'] . ' checked in.', BASE_URL . 'user/booking-details.php?id=' . $bid);
                set_flash('success', 'Guest checked in successfully.');
            }
        } elseif ($booking['status'] === 'checked_in') {
            if ($action === 'check_out') {
                $conn->prepare("UPDATE bookings SET status='completed' WHERE id=?")->execute([$bid]);
                add_notification($booking['user_id'], 'Booking Completed', $booking['booking_ref'] . ' completed.', BASE_URL . 'user/booking-details.php?id=' . $bid);
                set_flash('success', 'Guest checked out. Booking completed.');
            }
        }
    }
    redirect(BASE_URL . 'owner/bookings.php');
}

$status = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

$sql = "SELECT b.*, h.title, u.full_name AS guest, u.email AS guest_email, u.phone AS guest_phone, u.profile_image, 
        p.transaction_id, p.payment_method, p.status AS payment_status 
        FROM bookings b
        JOIN homestays h ON h.id=b.homestay_id 
        JOIN users u ON u.id=b.user_id 
        LEFT JOIN payments p ON p.booking_id=b.id
        WHERE h.owner_id=?";
$params = [$ownerId];

if (in_array($status, ['pending','confirmed','checked_in','rejected','cancelled','completed'])) {
    $sql .= ' AND b.status=?';
    $params[] = $status;
}

if ($search !== '') {
    $sql .= ' AND (b.booking_ref LIKE ? OR u.full_name LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= ' ORDER BY b.created_at DESC';
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$pageTitle = 'Bookings Manager';
$sidebarRole = 'owner';
$sidebarActive = 'bookings';
require __DIR__ . '/../includes/header.php';

function get_status_bg($status) {
    switch ($status) {
        case 'pending': return 'bg-warning';
        case 'confirmed': return 'bg-primary';
        case 'checked_in': return 'bg-info';
        case 'completed': return 'bg-success';
        case 'rejected':
        case 'cancelled': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

function get_status_border($status) {
    switch ($status) {
        case 'pending': return 'border-warning';
        case 'confirmed': return 'border-primary';
        case 'checked_in': return 'border-info';
        case 'completed': return 'border-success';
        case 'rejected':
        case 'cancelled': return 'border-danger';
        default: return 'border-secondary';
    }
}
?>
<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="dashboard-main animate__animated animate__fadeIn">
        
        <!-- Header & Search Bar -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 pb-3 border-bottom">
            <div>
                <h1 class="h3 fw-bold text-dark m-0">Bookings Manager</h1>
                <p class="small text-muted mb-0">Track and respond to guest reservations.</p>
            </div>
            
            <form method="GET" class="d-flex align-items-center gap-2">
                <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Search ref or guest..." value="<?= e($search) ?>" style="max-width: 200px">
                    <button class="btn btn-teal"><i class="fas fa-search"></i></button>
                    <?php if ($search !== ''): ?>
                        <a href="?<?= $status ? 'status='.urlencode($status) : '' ?>" class="btn btn-light border" title="Clear search"><i class="fas fa-xmark"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Status Filter Tabs -->
        <div class="d-flex flex-wrap gap-2 mb-4">
            <a href="?<?= $search ? 'search='.urlencode($search) : '' ?>" class="btn btn-sm <?= $status==='' ? 'btn-teal' : 'btn-light border text-muted' ?> px-3 py-2 rounded-pill fw-semibold">
                <i class="fas fa-list me-1"></i> All
            </a>
            <a href="?status=pending<?= $search ? '&search='.urlencode($search) : '' ?>" class="btn btn-sm <?= $status==='pending' ? 'btn-teal' : 'btn-warning border ' ?> px-3 py-2 rounded-pill fw-semibold">
                <i class="fas fa-hourglass-half me-1"></i> Pending
            </a>
            <a href="?status=confirmed<?= $search ? '&search='.urlencode($search) : '' ?>" class="btn btn-sm <?= $status==='confirmed' ? 'btn-teal' : 'btn-success border ' ?> px-3 py-2 rounded-pill fw-semibold">
                <i class="fas fa-check-circle me-1"></i> Confirmed
            </a>
            <a href="?status=checked_in<?= $search ? '&search='.urlencode($search) : '' ?>" class="btn btn-sm <?= $status==='checked_in' ? 'btn-teal' : 'btn-info border ' ?> px-3 py-2 rounded-pill fw-semibold">
                <i class="fas fa-door-open me-1"></i> Checked In
            </a>
            <a href="?status=completed<?= $search ? '&search='.urlencode($search) : '' ?>" class="btn btn-sm <?= $status==='completed' ? 'btn-teal' : 'btn-primary border ' ?> px-3 py-2 rounded-pill fw-semibold">
                <i class="fas fa-calendar-check me-1"></i> Completed
            </a>
            <a href="?status=cancelled<?= $search ? '&search='.urlencode($search) : '' ?>" class="btn btn-sm <?= $status==='cancelled' ? 'btn-teal' : 'btn-danger border ' ?> px-3 py-2 rounded-pill fw-semibold">
                <i class="fas fa-ban me-1"></i> Cancelled
            </a>
            <a href="?status=rejected<?= $search ? '&search='.urlencode($search) : '' ?>" class="btn btn-sm <?= $status==='rejected' ? 'btn-teal' : 'btn-outline-danger border ' ?> px-3 py-2 rounded-pill fw-semibold">
                <i class="fas fa-circle-xmark me-1"></i> Rejected
            </a>
        </div>

        <!-- Desktop Table View (tablets & large screens) -->
        <div class="dash-card border p-0 overflow-hidden d-none d-md-block">
            <div class="table-responsive table-sn mb-0">
                <table class="table align-middle mb-0 small">
                    <thead class="table-light text-uppercase font-monospace" style="font-size: 0.75rem">
                        <tr>
                            <th class="ps-4">Reference</th>
                            <th>Guest details</th>
                            <th>Property Title</th>
                            <th>Stay Calendar</th>
                            <th>Subtotal</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Action controls</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td class="ps-4 font-monospace fw-bold text-dark position-relative">
                                <!-- Left-aligned status color indicator strip -->
                                <span class="position-absolute start-0 top-0 bottom-0 <?= get_status_bg($b['status']) ?>" style="width: 4px;"></span>
                                #<?= e($b['booking_ref']) ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= profile_img($b['profile_image'] ?? '') ?>" class="rounded-circle object-fit-cover shadow-xs border" style="width: 32px; height: 32px" alt="">
                                    <div>
                                        <strong class="text-dark d-block"><?= e($b['guest']) ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td><span class="text-dark fw-semibold"><?= e($b['title']) ?></span></td>
                            <td>
                                <div class="d-flex flex-column small text-muted">
                                    <span><i class="far fa-calendar-plus text-teal me-2"></i>In: <?= format_date($b['check_in']) ?></span>
                                    <span><i class="far fa-calendar-minus text-teal me-2"></i>Out: <?= format_date($b['check_out']) ?></span>
                                </div>
                            </td>
                            <td class="fw-bold text-teal-deep"><?= money($b['total_amount']) ?></td>
                            <td><?= status_badge($b['status']) ?></td>
                            <td class="pe-4 text-end">
                                <div class="d-inline-flex align-items-center gap-2">
                                    <button class="btn btn-sm btn-light border fw-semibold py-1 px-2.5" type="button" data-bs-toggle="collapse" data-bs-target="#details-<?= $b['id'] ?>" aria-expanded="false" aria-controls="details-<?= $b['id'] ?>">
                                        <i class="far fa-eye me-1"></i> Details
                                    </button>

                                    <?php if ($b['status'] === 'pending'): ?>
                                        <!-- Accept Form -->
                                        <form method="POST">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="accept">
                                            <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                                            <button type="button" class="btn btn-sm btn-teal py-1 px-2.5 fw-bold btn-confirm" data-confirm="Accept this booking request?" title="Accept Booking"><i class="fas fa-check"></i></button>
                                        </form>
                                        
                                        <!-- Reject Form Toggle trigger -->
                                        <button class="btn btn-sm btn-outline-danger py-1 px-2 fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#rejectForm-<?= $b['id'] ?>" title="Reject Booking"><i class="fas fa-ban"></i></button>
                                    <?php elseif ($b['status'] === 'confirmed'): ?>
                                        <!-- Check-in Form -->
                                        <form method="POST">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="check_in">
                                            <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                                            <button type="button" class="btn btn-sm btn-info text-white py-1 px-2.5 fw-bold btn-confirm" data-confirm="Check-in this guest?" title="Check-in Guest"><i class="fas fa-sign-in-alt me-1"></i> Check-in</button>
                                        </form>
                                        <a href="<?= BASE_URL ?>pages/invoice.php?id=<?= (int)$b['id'] ?>" class="btn btn-sm btn-outline-teal fw-semibold py-1 px-2.5">
                                            <i class="fas fa-file-invoice me-1"></i> Invoice
                                        </a>
                                    <?php elseif ($b['status'] === 'checked_in'): ?>
                                        <!-- Check-out Form -->
                                        <form method="POST">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="check_out">
                                            <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                                            <button type="button" class="btn btn-sm btn-success py-1 px-2.5 fw-bold btn-confirm" data-confirm="Check-out this guest?" title="Check-out Guest"><i class="fas fa-sign-out-alt me-1"></i> Check-out</button>
                                        </form>
                                        <a href="<?= BASE_URL ?>pages/invoice.php?id=<?= (int)$b['id'] ?>" class="btn btn-sm btn-outline-teal fw-semibold py-1 px-2.5">
                                            <i class="fas fa-file-invoice me-1"></i> Invoice
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= BASE_URL ?>pages/invoice.php?id=<?= (int)$b['id'] ?>" class="btn btn-sm btn-outline-teal fw-semibold py-1 px-2.5">
                                            <i class="fas fa-file-invoice me-1"></i> Invoice
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Collapsible Reject Form Row -->
                        <?php if ($b['status'] === 'pending'): ?>
                        <tr class="collapse border-0 bg-light bg-opacity-25" id="rejectForm-<?= $b['id'] ?>">
                            <td colspan="7" class="px-4 py-3 border-0">
                                <div class="p-3 border rounded-3 bg-white" style="max-width: 500px">
                                    <strong class="text-danger small d-block mb-2"><i class="fas fa-circle-exclamation me-2"></i>Reject Reservation Request</strong>
                                    <form method="POST" class="d-flex gap-2">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                                        <input type="text" name="reason" class="form-control form-control-sm" placeholder="Provide cancellation reason..." required>
                                        <button type="button" class="btn btn-sm btn-danger fw-bold text-nowrap btn-confirm" data-confirm="Reject this booking request?">Confirm Reject</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>

                        <!-- Collapsible Details Row -->
                        <tr class="collapse border-0 bg-light bg-opacity-25" id="details-<?= $b['id'] ?>">
                            <td colspan="7" class="px-4 py-3 border-0">
                                <div class="row g-3">
                                    <!-- Guest Details -->
                                    <div class="col-md-4">
                                        <div class="bg-white p-3 rounded-3 border h-100">
                                            <strong class="text-dark small d-block mb-2 font-monospace text-uppercase text-muted" style="font-size: 0.65rem">Guest Contact Details</strong>
                                            <span class="small d-block text-dark fw-semibold mb-1"><i class="far fa-user text-teal me-2"></i><?= e($b['guest']) ?></span>
                                            <span class="small d-block text-muted mb-1"><i class="far fa-envelope text-teal me-2"></i><?= e($b['guest_email']) ?></span>
                                            <span class="small d-block text-muted"><i class="fas fa-phone text-teal me-2"></i><?= e($b['guest_phone'] ?: 'N/A') ?></span>
                                        </div>
                                    </div>
                                    
                                    <!-- Booking Stats -->
                                    <div class="col-md-4">
                                        <div class="bg-white p-3 rounded-3 border h-100">
                                            <strong class="text-dark small d-block mb-2 font-monospace text-uppercase text-muted" style="font-size: 0.65rem">Reservation Specs</strong>
                                            <span class="small d-block text-muted mb-1">Guests: <strong class="text-dark"><?= (int)$b['guests'] ?> occupant<?= $b['guests'] > 1 ? 's' : '' ?></strong></span>
                                            <span class="small d-block text-muted mb-1">Stay Duration: <strong class="text-dark"><?= round((strtotime($b['check_out']) - strtotime($b['check_in'])) / 86400) ?> nights</strong></span>
                                            <span class="small d-block text-muted">Special Requests: <strong class="text-dark"><?= e($b['special_requests'] ?: 'None') ?></strong></span>
                                            <?php if (in_array($b['status'], ['cancelled', 'rejected']) && !empty($b['cancellation_reason'])): ?>
                                                <span class="small d-block text-danger mt-2">Reason: <strong><?= e($b['cancellation_reason']) ?></strong></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Payment Info -->
                                    <div class="col-md-4">
                                        <div class="bg-white p-3 rounded-3 border h-100">
                                            <strong class="text-dark small d-block mb-2 font-monospace text-uppercase text-muted" style="font-size: 0.65rem">Payment Details</strong>
                                            <span class="small d-block text-muted mb-1">Method: <strong class="text-dark text-uppercase"><?= e($b['payment_method'] ?: 'N/A') ?></strong></span>
                                            <span class="small d-block text-muted mb-1">Transaction Ref: <strong class="text-dark font-monospace"><?= e($b['transaction_id'] ?: 'N/A') ?></strong></span>
                                            <span class="small d-block text-muted">Payment status: <strong class="text-teal-deep"><?= e($b['payment_status'] ?: 'unpaid') ?></strong></span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-center">
                                    <i class="far fa-folder-open fs-2 text-muted mb-2"></i>
                                    <p class="text-muted small mb-0">No bookings matching search criteria.</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile Card View (smartphones & small screens) -->
        <div class="d-block d-md-none ">
            <?php foreach ($bookings as $b): ?>
            <div class="card p-3.5 mb-3.5 border rounded-4 shadow-sm bg-white border-start border-4 <?= get_status_border($b['status']) ?> animate__animated animate__fadeIn p-2 m-2">
                <!-- Mobile Header -->
                <div class="d-flex justify-content-between align-items-center mb-2.5">
                    <span class="font-monospace fw-bold text-dark fs-7">#<?= e($b['booking_ref']) ?></span>
                    <?= status_badge($b['status']) ?>
                </div>
                
                <!-- Guest details -->
                <div class="d-flex align-items-center gap-2 mb-3">
                    <img src="<?= profile_img($b['profile_image'] ?? '') ?>" class="rounded-circle object-fit-cover shadow-xs border" style="width: 34px; height: 34px" alt="">
                    <div>
                        <strong class="text-dark small d-block" style="line-height: 1.2"><?= e($b['guest']) ?></strong>
                        <span class="small text-muted d-block mt-0.5" style="font-size: 0.7rem;"><i class="far fa-calendar text-teal me-1"></i><?= format_date($b['check_in']) ?> to <?= format_date($b['check_out']) ?></span>
                    </div>
                </div>

                <!-- Stay Details -->
                <div class="d-flex justify-content-between align-items-center pt-2.5 border-top mb-3">
                    <span class="small text-muted fw-semibold"><?= e($b['title']) ?></span>
                    <strong class="text-teal-deep small"><?= money($b['total_amount']) ?></strong>
                </div>

                <!-- Mobile Action Toolbar -->
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <button class="btn btn-xs btn-light border fw-semibold py-1 px-2.5" type="button" data-bs-toggle="collapse" data-bs-target="#mob-details-<?= $b['id'] ?>" aria-expanded="false" aria-controls="mob-details-<?= $b['id'] ?>">
                        <i class="far fa-eye me-1"></i> Details
                    </button>

                    <div class="d-inline-flex align-items-center gap-1.5 ms-auto">
                        <?php if ($b['status'] === 'pending'): ?>
                            <!-- Accept Form -->
                            <form method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="accept">
                                <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                                <button type="button" class="btn btn-xs btn-teal py-1 px-2.5 fw-bold btn-confirm" style="margin-top: 10px;" data-confirm="Accept this booking request?" title="Accept Booking"><i class="fas fa-check"></i> Accept</button>
                            </form>
                            
                            <!-- Reject Form trigger -->
                            <button class="btn btn-xs btn-outline-danger py-1 px-2.5 fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#mob-reject-<?= $b['id'] ?>" title="Reject Booking"><i class="fas fa-ban"></i> Reject</button>
                        <?php elseif ($b['status'] === 'confirmed'): ?>
                            <!-- Check-in Form -->
                            <form method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="check_in">
                                <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                                <button type="button" class="btn btn-xs btn-info text-white py-1 px-2.5 fw-bold btn-confirm" data-confirm="Check-in this guest?" title="Check-in Guest"><i class="fas fa-sign-in-alt me-1"></i> Check-in</button>
                            </form>
                            <a href="<?= BASE_URL ?>pages/invoice.php?id=<?= (int)$b['id'] ?>" class="btn btn-xs btn-outline-teal fw-semibold py-1 px-2.5">
                                <i class="fas fa-file-invoice me-1"></i> Invoice
                            </a>
                        <?php elseif ($b['status'] === 'checked_in'): ?>
                            <!-- Check-out Form -->
                            <form method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="check_out">
                                <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                                <button type="button" class="btn btn-xs btn-success py-1 px-2.5 fw-bold btn-confirm" data-confirm="Check-out this guest?" title="Check-out Guest"><i class="fas fa-sign-out-alt me-1"></i> Check-out</button>
                            </form>
                            <a href="<?= BASE_URL ?>pages/invoice.php?id=<?= (int)$b['id'] ?>" class="btn btn-xs btn-outline-teal fw-semibold py-1 px-2.5">
                                <i class="fas fa-file-invoice me-1"></i> Invoice
                            </a>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>pages/invoice.php?id=<?= (int)$b['id'] ?>" class="btn btn-xs btn-outline-teal fw-semibold py-1 px-2.5">
                                <i class="fas fa-file-invoice me-1"></i> Invoice
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Collapsible Mobile Reject Block -->
                <?php if ($b['status'] === 'pending'): ?>
                <div class="collapse pt-3 mt-3 border-top" id="mob-reject-<?= $b['id'] ?>">
                    <strong class="text-danger small d-block mb-2"><i class="fas fa-circle-exclamation me-1.5"></i>Provide cancellation reason</strong>
                    <form method="POST" class="d-flex gap-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                        <input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason..." required>
                        <button type="button" class="btn btn-sm btn-danger fw-bold text-nowrap btn-confirm" data-confirm="Reject this booking request?">Confirm</button>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Collapsible Mobile Details Block -->
                <div class="collapse pt-3 mt-3 border-top small text-muted" id="mob-details-<?= $b['id'] ?>">
                    <div class="bg-light p-2.5 rounded-3 border">
                        <div class="mb-2"><strong class="text-dark">Email:</strong> <?= e($b['guest_email']) ?></div>
                        <div class="mb-2"><strong class="text-dark">Phone:</strong> <?= e($b['guest_phone'] ?: 'N/A') ?></div>
                        <div class="mb-2"><strong class="text-dark">Guests:</strong> <?= (int)$b['guests'] ?> occupant(s)</div>
                        <div class="mb-2"><strong class="text-dark">Special Requests:</strong> <?= e($b['special_requests'] ?: 'None') ?></div>
                        <?php if (in_array($b['status'], ['cancelled', 'rejected']) && !empty($b['cancellation_reason'])): ?>
                        <div class="mb-2 text-danger"><strong>Reason:</strong> <?= e($b['cancellation_reason']) ?></div>
                        <?php endif; ?>
                        <div class="mb-2"><strong class="text-dark">Method:</strong> <span class="text-uppercase"><?= e($b['payment_method'] ?: 'N/A') ?></span></div>
                        <div class="mb-2"><strong class="text-dark">Transaction ID:</strong> <span class="font-monospace"><?= e($b['transaction_id'] ?: 'N/A') ?></span></div>
                        <div><strong class="text-dark">Payment Status:</strong> <span class="badge bg-light text-teal-deep border border-teal border-opacity-20"><?= e($b['payment_status'] ?: 'unpaid') ?></span></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($bookings)): ?>
                <div class="text-center py-5 border rounded-4 bg-light bg-opacity-25">
                    <i class="far fa-folder-open fs-2 text-muted mb-2"></i>
                    <p class="text-muted small mb-0">No bookings matching criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
