<?php
// Room details page
require_once __DIR__ . '/../includes/functions.php';

$roomId = (int)($_GET['id'] ?? 0);
$fallbackRoomIndex = max(0, (int)($_GET['fallback_room'] ?? 0));
$isFallbackRoom = false;
$room = null;

if ($roomId > 0) {
    // Fetch Room details
    $stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ? AND is_active = 1");
    $stmt->execute([$roomId]);
    $room = $stmt->fetch();
} elseif ($fallbackRoomIndex > 0) {
    $fallbackRooms = fallback_public_rooms();
    if (isset($fallbackRooms[$fallbackRoomIndex - 1])) {
        $room = $fallbackRooms[$fallbackRoomIndex - 1];
        $room['id'] = 0;
        $isFallbackRoom = true;
    }
}

if (!$room) {
    set_flash('error', 'Room not found.');
    redirect(BASE_URL . 'pages/rooms.php');
}

// Fetch parent Homestay details
$h = $conn->query("SELECT * FROM homestays WHERE id = 1")->fetch();
if (!$h) {
    $h = [
        'address' => 'Near Khecheopalri Lake, West Sikkim',
        'house_rules' => 'Quiet hours after 10 PM. No shoes inside rooms. Vegetarian meals only.',
    ];
}

// Fetch all room images sorted by sort_order
$rImages = [];
if (!$isFallbackRoom) {
    $roomImagesQuery = $conn->prepare("SELECT image_path FROM room_images WHERE room_id = ? ORDER BY sort_order ASC, id ASC");
    $roomImagesQuery->execute([$roomId]);
    $rImages = $roomImagesQuery->fetchAll(PDO::FETCH_COLUMN);
}

// Combine cover image with other images
$allRoomImages = [];
if (!empty($room['cover_image'])) {
    $allRoomImages[] = $room['cover_image'];
}
foreach ($rImages as $img) {
    if ($img !== $room['cover_image']) {
        $allRoomImages[] = $img;
    }
}
if (empty($allRoomImages)) {
    $allRoomImages[] = '';
}
$roomSeed = $isFallbackRoom ? $fallbackRoomIndex : $roomId;
$roomPhotoUrls = room_photo_urls($allRoomImages, $roomSeed);

// Get dates and guests from parameters
$checkIn = trim($_GET['check_in'] ?? '');
$checkOut = trim($_GET['check_out'] ?? '');
$guests = min((int)$room['max_guests'], max(1, (int)($_GET['guests'] ?? 1)));

// Calculate nights and price subtotal if dates are valid
$nights = ($checkIn && $checkOut && $checkOut > $checkIn) ? nights_between($checkIn, $checkOut) : 1;
$subtotal = $room['price_per_night'] * $nights;
$cleaning = $room['cleaning_fee'];
$service = round($subtotal * 0.05, 2);
$totalAmount = $subtotal + $cleaning + $service;

$pageTitle = $room['name'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container py-5 animate__animated animate__fadeIn">
    <!-- Breadcrumbs / Back button -->
    <div class="mb-4">
        <a href="<?= BASE_URL ?>pages/rooms.php?check_in=<?= urlencode($checkIn) ?>&check_out=<?= urlencode($checkOut) ?>&guests=<?= $guests ?>" class="text-teal text-decoration-none fw-bold small"><i class="fas fa-arrow-left me-2"></i>Back to Stays & Rooms</a>
    </div>

    <div class="row g-4">
        <!-- Room Photos & Details (Left Column) -->
        <div class="col-lg-8">
            <!-- Room Image Bootstrap Carousel with sliding transitions -->
            <div class="position-relative overflow-hidden rounded-4 border bg-light mb-4 shadow-sm">
                <div id="roomDetailsCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
                    <div class="carousel-inner" style="height: 400px;">
                        <?php
                        $realIndex = 0;
                        foreach ($roomPhotoUrls as $roomImgUrl):
                        ?>
                        <div class="carousel-item <?= $realIndex === 0 ? 'active' : '' ?> h-100">
                            <img src="<?= e($roomImgUrl) ?>" class="d-block w-100 h-100 object-fit-cover cursor-pointer room-carousel-img" alt="Room Photo" data-index="<?= $realIndex ?>">
                        </div>
                        <?php
                            $realIndex++;
                        endforeach;
                        ?>
                    </div>

                    <!-- Carousel Controls -->
                    <?php if (count($roomPhotoUrls) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#roomDetailsCarousel" data-bs-slide="prev" style="z-index: 6;">
                        <span class="carousel-control-prev-icon bg-dark rounded-circle p-2" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#roomDetailsCarousel" data-bs-slide="next" style="z-index: 6;">
                        <span class="carousel-control-next-icon bg-dark rounded-circle p-2" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Thumbnails Nav Strip -->
                <?php if (count($roomPhotoUrls) > 1): ?>
                <div class="w-100 p-2.5 bg-dark bg-opacity-40 d-flex gap-2 overflow-x-auto justify-content-center" style="scrollbar-width:none; position: relative; z-index: 5;">
                    <?php foreach ($roomPhotoUrls as $realIndex => $thumbUrl): ?>
                    <img src="<?= e($thumbUrl) ?>" class="rounded border border-white border-opacity-50 cursor-pointer object-fit-cover room-thumb-nav" style="width: 56px; height: 42px; opacity: <?= $realIndex === 0 ? '1' : '0.6' ?>" data-bs-target="#roomDetailsCarousel" data-bs-slide-to="<?= $realIndex ?>" alt="Thumb">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Title & Room Type -->
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h1 class="fw-bold text-dark display-font mb-1"><?= e($room['name']) ?></h1>
                    <span class="text-muted small"><i class="fas fa-house me-1.5"></i>Part of Sonam Homestay &bull; <?= e($h['address']) ?></span>
                </div>
                <span class="badge bg-teal bg-opacity-10 text-teal border border-teal border-opacity-25 px-3 py-1.5 rounded-pill fs-7 fw-bold"><?= e($room['room_type']) ?></span>
            </div>

            <!-- Parameters Grid -->
            <div class="row g-2 mb-4 text-center">
                <div class="col-4">
                    <div class="p-3 border rounded-3 bg-light bg-opacity-40">
                        <i class="fas fa-users text-teal fs-4 mb-2"></i>
                        <span class="d-block small text-muted">Max Capacity</span>
                        <strong class="text-dark"><?= (int)$room['max_guests'] ?> Guest<?= $room['max_guests'] > 1 ? 's' : '' ?></strong>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-3 border rounded-3 bg-light bg-opacity-40">
                        <i class="fas fa-bed text-teal fs-4 mb-2"></i>
                        <span class="d-block small text-muted">Beds Available</span>
                        <strong class="text-dark"><?= (int)$room['beds'] ?> Bed<?= $room['beds'] > 1 ? 's' : '' ?></strong>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-3 border rounded-3 bg-light bg-opacity-40">
                        <i class="fas fa-coins text-teal fs-4 mb-2"></i>
                        <span class="d-block small text-muted">Nightly Price</span>
                        <strong class="text-dark"><?= money($room['price_per_night']) ?></strong>
                    </div>
                </div>
            </div>

            <!-- Room Description -->
            <h4 class="fw-bold text-dark mb-2.5 display-font"><i class="fas fa-align-left text-teal me-2"></i>Room Description</h4>
            <p class="text-secondary lh-lg mb-4"><?= nl2br(e($room['description'] ?: 'Enjoy cozy interiors, warm wooden paneling, and breathtaking views of the surrounding Sikkim ridges in this professionally serviced room.')) ?></p>

            <hr class="opacity-25 my-4">

            <!-- House Policies / Rules -->
            <h4 class="fw-bold text-dark mb-2.5 display-font"><i class="fas fa-shield-halved text-teal me-2"></i>Sonam Homestay Policies</h4>
            <p class="text-muted small lh-lg"><?= nl2br(e($h['house_rules'] ?: 'Quiet hours after 10 PM. No shoes inside rooms. Vegetarian meals only.')) ?></p>
        </div>

        <!-- Room Reservation Box (Right Column) -->
        <div class="col-lg-4">
            <div class="booking-widget border rounded-4 p-4 shadow-sm bg-white sticky-lg-top" id="bookingWidget" style="top: 100px;z-index: 1;">
                <div class="d-flex align-items-baseline gap-2 mb-4">
                    <span class="fs-2 fw-bold text-teal"><?= money($room['price_per_night']) ?></span>
                    <span class="text-muted">/ night</span>
                </div>

                <form action="<?= $isFallbackRoom ? BASE_URL : BASE_URL . 'pages/book.php' ?>" method="GET">
                    <input type="hidden" name="homestay_id" value="1">
                    <input type="hidden" name="room_id" value="<?= $roomId ?>">
                    <?php if ($isFallbackRoom): ?>
                    <input type="hidden" name="room_interest" value="<?= e($room['name']) ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">Check-in</label>
                        <input type="date" name="check_in" id="widgetCheckIn" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= e($checkIn) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">Check-out</label>
                        <input type="date" name="check_out" id="widgetCheckOut" class="form-control" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= e($checkOut) ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">Guests</label>
                        <select name="guests" class="form-select fw-semibold text-dark">
                            <?php for ($i = 1; $i <= (int)$room['max_guests']; $i++): ?>
                            <option value="<?= $i ?>" <?= $guests == $i ? 'selected' : '' ?>><?= $i ?> Guest<?= $i > 1 ? 's' : '' ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <!-- Pricing calculator panel -->
                    <div class="d-flex flex-column gap-2 mb-4 small text-muted border-top border-bottom py-3 d-none" id="pricingCalcPanel">
                        <div class="d-flex justify-content-between">
                            <span><?= money($room['price_per_night']) ?> &times; <span id="spanNights">1</span> night(s)</span>
                            <span class="fw-semibold text-dark" id="spanSubtotal">₹0</span>
                        </div>
                        <?php if ((float)$room['cleaning_fee'] > 0): ?>
                        <div class="d-flex justify-content-between">
                            <span>Cleaning fee</span>
                            <span class="fw-semibold text-dark">+= <?= money($room['cleaning_fee']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between">
                            <span>Service fee (5%)</span>
                            <span class="fw-semibold text-dark" id="spanService">₹0</span>
                        </div>
                        <hr class="opacity-25 my-1.5">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong class="text-dark">Total Amount</strong>
                            <strong class="text-teal fs-6" id="spanTotal">₹0</strong>
                        </div>
                    </div>

                    <?php if ($isFallbackRoom): ?>
                    <?php $fallbackBookUrl = BASE_URL . 'pages/book.php?homestay_id=1&fallback_room=' . $fallbackRoomIndex . '&check_in=' . urlencode($checkIn) . '&check_out=' . urlencode($checkOut) . '&guests=' . $guests; ?>
                    <a href="<?= e(is_logged_in() ? $fallbackBookUrl : login_url($fallbackBookUrl)) ?>" class="btn btn-primary w-100 py-2.5 fw-bold"><i class="fas fa-calendar-check me-2"></i>Book This Room</a>
                    <?php else: ?>
                    <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold"><i class="fas fa-calendar-check me-2"></i>Reserve Room</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Lightbox Modal for Room Photos Preview -->
<div class="modal fade" id="detailsRoomLightboxModal" tabindex="-1" aria-hidden="true" style="z-index: 1060">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-transparent border-0 position-relative">
            <button type="button" class="btn-close btn-close-white ms-auto mb-2" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="position-relative bg-black rounded-4 overflow-hidden d-flex align-items-center justify-content-center" style="min-height: 300px">
                <img src="" id="detailsLightboxImg" class="w-100 object-fit-contain" style="max-height: 70vh; transition: opacity 0.2s ease-in-out;" alt="Room Preview">
                <button type="button" id="detailsLightboxPrev" class="btn btn-dark rounded-circle opacity-75 position-absolute start-0 top-50 translate-middle-y ms-3" style="width:40px; height:40px; display:none; align-items:center; justify-content:center; z-index:10" title="Previous">
                    <i class="fas fa-chevron-left text-white"></i>
                </button>
                <button type="button" id="detailsLightboxNext" class="btn btn-dark rounded-circle opacity-75 position-absolute end-0 top-50 translate-middle-y me-3" style="width:40px; height:40px; display:none; align-items:center; justify-content:center; z-index:10" title="Next">
                    <i class="fas fa-chevron-right text-white"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const carouselEl = document.getElementById('roomDetailsCarousel');
    if (carouselEl) {
        carouselEl.addEventListener('slide.bs.carousel', function (event) {
            const index = event.to;
            const thumbs = carouselEl.closest('.position-relative').querySelectorAll('.room-thumb-nav');
            thumbs.forEach((t, i) => {
                t.style.opacity = (i === index) ? '1' : '0.6';
            });
        });
    }

    // Dynamic Pricing Calculations
    const checkInInput = document.getElementById('widgetCheckIn');
    const checkOutInput = document.getElementById('widgetCheckOut');
    const calcPanel = document.getElementById('pricingCalcPanel');

    const nightPrice = <?= (float)$room['price_per_night'] ?>;
    const cleaningFee = <?= (float)$room['cleaning_fee'] ?>;

    function recalculate() {
        const checkInVal = checkInInput.value;
        const checkOutVal = checkOutInput.value;

        if (!checkInVal || !checkOutVal || checkOutVal <= checkInVal) {
            calcPanel.classList.add('d-none');
            return;
        }

        const date1 = new Date(checkInVal);
        const date2 = new Date(checkOutVal);
        const diffTime = Math.abs(date2 - date1);
        const diffNights = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        const subtotal = nightPrice * diffNights;
        const service = Math.round(subtotal * 0.05 * 100) / 100;
        const total = subtotal + cleaningFee + service;

        document.getElementById('spanNights').textContent = diffNights;
        document.getElementById('spanSubtotal').textContent = '₹' + subtotal.toLocaleString('en-IN', {minimumFractionDigits: 2});
        document.getElementById('spanService').textContent = '₹' + service.toLocaleString('en-IN', {minimumFractionDigits: 2});
        document.getElementById('spanTotal').textContent = '₹' + total.toLocaleString('en-IN', {minimumFractionDigits: 2});

        calcPanel.classList.remove('d-none');
    }

    if (checkInInput) checkInInput.addEventListener('change', recalculate);
    if (checkOutInput) checkOutInput.addEventListener('change', recalculate);
    recalculate(); // initial run

    // Lightbox slider preview initialization
    const lightboxModal = document.getElementById('detailsRoomLightboxModal');
    if (!lightboxModal || typeof bootstrap === 'undefined') return;

    const bsModal = new bootstrap.Modal(lightboxModal);
    const lightboxImg = document.getElementById('detailsLightboxImg');
    const prevBtn = document.getElementById('detailsLightboxPrev');
    const nextBtn = document.getElementById('detailsLightboxNext');
    let currentList = [];
    let currentIndex = 0;

    document.querySelectorAll('.room-carousel-img').forEach(img => {
        img.addEventListener('click', function() {
            const listData = <?= json_encode($roomPhotoUrls) ?>;
            currentIndex = parseInt(this.getAttribute('data-index') || '0');
            currentList = listData;
            if (currentList.length > 0) {
                showImage();
                bsModal.show();
            }
        });
    });

    function showImage() {
        if (currentList.length > 0) {
            lightboxImg.style.opacity = '0';
            setTimeout(() => {
                lightboxImg.src = currentList[currentIndex];
                lightboxImg.style.opacity = '1';

                if (currentList.length > 1) {
                    prevBtn.style.display = 'flex';
                    nextBtn.style.display = 'flex';
                } else {
                    prevBtn.style.display = 'none';
                    nextBtn.style.display = 'none';
                }
            }, 100);
        }
    }

    prevBtn.addEventListener('click', function() {
        currentIndex = (currentIndex - 1 + currentList.length) % currentList.length;
        showImage();
    });

    nextBtn.addEventListener('click', function() {
        currentIndex = (currentIndex + 1) % currentList.length;
        showImage();
    });

    document.addEventListener('keydown', function(e) {
        if (!lightboxModal.classList.contains('show')) return;
        if (e.key === 'ArrowLeft') {
            prevBtn.click();
        } else if (e.key === 'ArrowRight') {
            nextBtn.click();
        }
    });
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
