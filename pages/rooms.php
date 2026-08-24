<?php
// Rooms listing and availability search
require_once __DIR__ . '/../includes/functions.php';

$checkIn = trim($_GET['check_in'] ?? '');
$checkOut = trim($_GET['check_out'] ?? '');
$guests = min(4, max(1, (int)($_GET['guests'] ?? 1)));
$roomType = trim($_GET['room_type'] ?? '');
$roomTypes = [
    '' => 'Any Room',
    'Single' => 'Single Room',
    'Double' => 'Double Room',
    'Family' => 'Family Room',
];

// Fetch all active rooms
$allRooms = $conn->query("SELECT * FROM rooms WHERE is_active = 1 ORDER BY price_per_night ASC")->fetchAll();
if (empty($allRooms)) {
    $allRooms = fallback_public_rooms();
}
$rooms = [];

// Filter by availability and capacity
foreach ($allRooms as $r) {
    $isFallbackRoom = !empty($r['is_fallback']);
    if (!$isFallbackRoom && $checkIn && $checkOut) {
        if (!is_room_available($r['id'], $checkIn, $checkOut)) {
            continue;
        }
    }
    if ($guests > 0 && $r['max_guests'] < $guests) {
        continue;
    }
    if ($roomType !== '') {
        $haystack = strtolower(($r['room_type'] ?? '') . ' ' . ($r['name'] ?? '') . ' ' . ($r['description'] ?? ''));
        if (strpos($haystack, strtolower($roomType)) === false) {
            continue;
        }
    }
    $rooms[] = $r;
}

$pageTitle = 'Available Rooms';
require __DIR__ . '/../includes/header.php';
?>

<section class="rooms-hero animate__animated animate__fadeIn">
    <div class="container">
        <div class="rooms-hero-content">
            <span class="rooms-kicker js-text-reveal"><i class="fas fa-mountain-sun"></i> Sonam Homestay Rooms</span>
            <h1 class="display-font text-white mb-3 js-word-reveal">Stay close to mountain views, warm rooms, and local Sikkim hospitality.</h1>
            <p class="mb-0 js-text-reveal">Choose simple, comfortable rooms designed for couples, solo travelers, and families visiting Khecheopalri and West Sikkim.</p>
            <a href="#roomsList" class="rooms-scroll-cue js-text-reveal">
                <span>Scroll down to explore rooms</span>
                <i class="fas fa-arrow-down"></i>
            </a>
        </div>
        <div class="rooms-hero-stats">
            <span class="js-text-reveal"><strong><?= count($rooms) ?></strong> Room<?= count($rooms) === 1 ? '' : 's' ?> Listed</span>
            <span class="js-text-reveal"><strong>4</strong> Max Guests</span>
            <span class="js-text-reveal"><strong>Local</strong> Homestay</span>
        </div>
    </div>
</section>

<div class="rooms-page container animate__animated animate__fadeIn animate__delay-1s">
    <!-- Filter Dates Row -->
    <div class="rooms-filter-panel mb-5">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
            <div>
                <span class="section-kicker js-text-reveal">Availability</span>
                <h2 class="display-font text-teal-deep mb-1 js-word-reveal">Find the right room</h2>
                <p class="text-muted mb-0 small js-text-reveal">Filter by dates, guests, and room type. Guest count is capped at 4.</p>
            </div>
            <a href="<?= BASE_URL ?>pages/gallery.php" class="btn btn-light border fw-bold align-self-start"><i class="far fa-images"></i> View Gallery</a>
        </div>
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted text-uppercase mb-1"><i class="far fa-calendar"></i> Check-in Date</label>
                <input type="date" name="check_in" id="roomsCheckIn" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= e($checkIn) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted text-uppercase mb-1"><i class="far fa-calendar-check"></i> Check-out Date</label>
                <input type="date" name="check_out" id="roomsCheckOut" class="form-control" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= e($checkOut) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted text-uppercase mb-1"><i class="fas fa-users"></i> Guests</label>
                <select name="guests" class="form-select fw-semibold text-dark">
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                    <option value="<?= $i ?>" <?= $guests == $i ? 'selected' : '' ?>><?= $i ?> Guest<?= $i > 1 ? 's' : '' ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted text-uppercase mb-1"><i class="fas fa-bed"></i> Room Selection</label>
                <select name="room_type" class="form-select fw-semibold text-dark">
                    <?php foreach ($roomTypes as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $roomType === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 fw-bold rooms-filter-submit"><i class="fas fa-search"></i> Filter Rooms</button>
            </div>
        </form>
    </div>

    <div class="rooms-section-head" id="roomsList">
        <div>
            <span class="section-kicker js-text-reveal">Room Collection</span>
            <h2 class="display-font text-teal-deep mb-1 js-word-reveal">Available stays</h2>
            <p class="text-muted mb-0 js-text-reveal">Comfortable room options with realistic photos, clear capacity, and transparent nightly pricing.</p>
        </div>
        <span class="rooms-count-badge"><i class="fas fa-door-open"></i> <?= count($rooms) ?> Match<?= count($rooms) === 1 ? '' : 'es' ?></span>
    </div>

    <!-- Rooms Grid -->
    <div class="row g-4">
        <?php if (empty($rooms)): ?>
            <div class="col-12 text-center py-5 border rounded-4 bg-light bg-opacity-25">
                <i class="fas fa-bed text-muted fs-1 mb-3"></i>
                <h5 class="fw-bold text-dark">No Rooms Available</h5>
                <p class="text-muted small">No rooms match your search dates or guest count. Try adjusting filters.</p>
            </div>
        <?php else: foreach ($rooms as $fallbackIndex => $r):
            $isFallbackRoom = !empty($r['is_fallback']);
            // Fetch room images count
            if ($isFallbackRoom) {
                $imgCount = count(uploaded_asset_urls(UPLOAD_ROOMS, 'assets/uploads/rooms', 6));
            } else {
                $countStmt = $conn->prepare("SELECT COUNT(*) FROM room_images WHERE room_id = ?");
                $countStmt->execute([$r['id']]);
                $imgCount = (int)$countStmt->fetchColumn() + (!empty($r['cover_image']) ? 1 : 0);
            }

            $roomImgUrl = room_image_url($r['cover_image'] ?? '', $isFallbackRoom ? ($fallbackIndex + 1) : (int)$r['id']);
            $roomDetailUrl = $isFallbackRoom
                ? BASE_URL . 'pages/room-details.php?fallback_room=' . ($fallbackIndex + 1) . '&check_in=' . urlencode($checkIn) . '&check_out=' . urlencode($checkOut) . '&guests=' . $guests
                : BASE_URL . 'pages/room-details.php?id=' . (int)$r['id'] . '&check_in=' . urlencode($checkIn) . '&check_out=' . urlencode($checkOut) . '&guests=' . $guests . '&room_type=' . urlencode($roomType);
            $roomHomestayId = (int)($r['homestay_id'] ?? 1);
            $roomBookBaseUrl = $isFallbackRoom
                ? BASE_URL . 'pages/book.php?homestay_id=1&fallback_room=' . ($fallbackIndex + 1)
                : BASE_URL . 'pages/book.php?homestay_id=' . $roomHomestayId . '&room_id=' . (int)$r['id'];
            $roomBookUrl = $roomBookBaseUrl . '&check_in=' . urlencode($checkIn) . '&check_out=' . urlencode($checkOut) . '&guests=' . $guests;
            $roomActionUrl = is_logged_in() ? $roomBookUrl : login_url($roomBookUrl);
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="rooms-card h-100" data-aos="fade-up" data-aos-delay="<?= min($fallbackIndex * 35, 180) ?>">
                <div>
                    <!-- Room Cover Photo -->
                    <div class="rooms-card-photo">
                        <img src="<?= e($roomImgUrl) ?>" alt="<?= e($r['name']) ?>">
                        <span class="rooms-type-badge"><?= e($r['room_type']) ?></span>

                        <?php if ($imgCount > 1): ?>
                        <span class="rooms-photo-badge"><i class="far fa-images"></i><?= $imgCount ?> Photos</span>
                        <?php endif; ?>
                    </div>

                    <!-- Room Body -->
                    <div class="rooms-card-body">
                        <h4 class="display-font"><?= e($r['name']) ?></h4>
                        <p><?= e($r['description'] ?: 'Enjoy comfortable lodgings with stunning mountain views.') ?></p>

                        <div class="rooms-meta">
                            <span><i class="fas fa-users"></i>Max <?= (int)$r['max_guests'] ?> Guest<?= $r['max_guests'] > 1 ? 's' : '' ?></span>
                            <span><i class="fas fa-bed"></i><?= (int)$r['beds'] ?> Bed<?= $r['beds'] > 1 ? 's' : '' ?></span>
                        </div>
                        <div class="room-feature-chips room-feature-chips-sm">
                            <span><i class="fas fa-shower"></i>Hot Water</span>
                            <span><i class="fas fa-mug-hot"></i>Breakfast</span>
                            <span><i class="fas fa-mountain-sun"></i>Views</span>
                        </div>
                    </div>
                </div>

                <!-- Room Footer -->
                <div class="rooms-card-footer">
                    <div>
                        <span><?= money($r['price_per_night']) ?></span>
                        <small>/ night</small>
                    </div>
                    <a href="<?= e($roomActionUrl) ?>" class="btn btn-sm btn-primary fw-bold btn-room-book-now" data-base-url="<?= e($roomBookBaseUrl) ?>">
                        <i class="fas fa-calendar-check"></i>Book
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var checkInInput = document.getElementById('roomsCheckIn');
    var checkOutInput = document.getElementById('roomsCheckOut');
    var guestsInput = document.querySelector('select[name="guests"]');
    var bookButtons = document.querySelectorAll('.btn-room-book-now');
    var userIsLoggedIn = <?= is_logged_in() ? 'true' : 'false' ?>;
    var loginBaseUrl = '<?= BASE_URL ?>authentication/login.php?redirect=';

    function addDays(dateString, days) {
        var date = new Date(dateString + 'T00:00:00');
        date.setDate(date.getDate() + days);
        return date.toISOString().split('T')[0];
    }

    function syncCheckoutMin() {
        if (!checkInInput || !checkOutInput || !checkInInput.value) return;
        var nextDay = addDays(checkInInput.value, 1);
        checkOutInput.setAttribute('min', nextDay);
        if (checkOutInput.value && checkOutInput.value <= checkInInput.value) {
            checkOutInput.value = nextDay;
        }
    }

    function bookingUrl(baseUrl) {
        var checkIn = checkInInput ? checkInInput.value : '';
        var checkOut = checkOutInput ? checkOutInput.value : '';
        var guests = guestsInput ? guestsInput.value : '1';
        return baseUrl + '&check_in=' + encodeURIComponent(checkIn) + '&check_out=' + encodeURIComponent(checkOut) + '&guests=' + encodeURIComponent(guests);
    }

    function refreshBookLinks() {
        syncCheckoutMin();
        bookButtons.forEach(function (btn) {
            var url = bookingUrl(btn.getAttribute('data-base-url'));
            btn.setAttribute('href', userIsLoggedIn ? url : loginBaseUrl + encodeURIComponent(url));
        });
    }

    bookButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            refreshBookLinks();
        });
    });

    checkInInput?.addEventListener('change', refreshBookLinks);
    checkOutInput?.addEventListener('change', refreshBookLinks);
    guestsInput?.addEventListener('change', refreshBookLinks);
    refreshBookLinks();
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
