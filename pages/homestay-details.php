<?php
// Homestay details
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT h.*, o.business_name, u.full_name AS owner_name, u.profile_image AS owner_image
    FROM homestays h
    JOIN owners o ON o.id = h.owner_id
    JOIN users u ON u.id = o.user_id
    WHERE h.id = ? AND h.is_active = 1");
$stmt->execute([$id]);
$h = $stmt->fetch();

if (!$h) {
    set_flash('error', 'Homestay not found.');
    redirect(BASE_URL . 'pages/search.php');
}

$rooms = $conn->prepare('SELECT * FROM rooms WHERE homestay_id = ? AND is_active = 1 ORDER BY price_per_night');
$rooms->execute([$id]);
$rooms = $rooms->fetchAll();
$hasRealRooms = !empty($rooms);
if (!$hasRealRooms) {
    $rooms = fallback_public_rooms();
}

$ams = $conn->prepare('SELECT a.* FROM amenities a JOIN homestay_amenities ha ON ha.amenity_id = a.id WHERE ha.homestay_id = ?');
$ams->execute([$id]);
$ams = $ams->fetchAll();

$reviews = $conn->prepare('SELECT r.*, u.full_name FROM reviews r JOIN users u ON u.id = r.user_id WHERE r.homestay_id = ? AND r.is_approved = 1 ORDER BY r.created_at DESC');
$reviews->execute([$id]);
$reviews = $reviews->fetchAll();
$avgStmt = $conn->prepare('SELECT AVG(rating) FROM reviews WHERE homestay_id = ? AND is_approved = 1');
$avgStmt->execute([$id]);
$avgRating = (float)$avgStmt->fetchColumn();

$minPrice = 0;
if ($rooms) {
    $minPrice = min(array_column($rooms, 'price_per_night'));
}

// Fetch all homestay images
$images = [];
if (!empty($h['cover_image'])) {
    $images[] = $h['cover_image'];
}
$imgQuery = $conn->prepare("SELECT image_path FROM homestay_images WHERE homestay_id = ? ORDER BY sort_order");
$imgQuery->execute([$id]);
$extraImages = $imgQuery->fetchAll(PDO::FETCH_COLUMN);
foreach ($extraImages as $img) {
    if ($img !== $h['cover_image']) {
        $images[] = $img;
    }
}
if (empty($images)) {
    $images[] = '';
}

$pageTitle = $h['title'];
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>pages/search.php">Explore</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($h['title']) ?></li>
        </ol>
    </nav>

    <h1 class="display-font h2 mb-2"><?= e($h['title']) ?></h1>
    <p class="text-muted"><i class="fas fa-map-marker-alt text-danger"></i> <?= e($h['city']) ?>, <?= e($h['state']) ?> · <?= stars($avgRating) ?></p>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <!-- Swiper Image Slider -->
            <div class="swiper detail-swiper rounded-4 overflow-hidden shadow-sm" style="background: var(--sn-sand-dark)">
                <div class="swiper-wrapper">
                    <?php foreach ($images as $img):
                        $imgUrl = $img
                            ? homestay_image_url($img)
                            : display_image(['id' => $id, 'city' => $h['city'], 'cover_image' => $img]);
                        if ($imgUrl === asset('images/placeholder-homestay.svg')) {
                            $imgUrl = display_image(['id' => $id, 'city' => $h['city'], 'cover_image' => null]);
                        }
                    ?>
                    <div class="swiper-slide">
                        <img src="<?= e($imgUrl) ?>" class="w-100" style="height:480px; object-fit:cover" alt="<?= e($h['title']) ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($images) > 1): ?>
                <div class="swiper-button-prev detail-prev"></div>
                <div class="swiper-button-next detail-next"></div>
                <div class="swiper-pagination"></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="booking-widget border">
                <div class="d-flex align-items-baseline gap-2 mb-3">
                    <span class="fs-3 fw-bold text-teal"><?= money($minPrice) ?></span>
                    <span class="text-muted">/ night</span>
                </div>
                <form action="<?= BASE_URL ?>pages/book.php" method="GET">
                    <input type="hidden" name="homestay_id" value="<?= $id ?>">
                    <div class="mb-2">
                        <label class="form-label">Room</label>
                        <select name="room_id" class="form-select" <?= $hasRealRooms ? 'required' : 'disabled' ?>>
                            <option value=""><?= $hasRealRooms ? 'Select' : 'Contact host for room availability' ?></option>
                            <?php foreach ($rooms as $r): if (!empty($r['is_fallback'])) continue; ?>
                            <option value="<?= (int)$r['id'] ?>"><?= e($r['name']) ?> - <?= money($r['price_per_night']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="form-label">Check-in</label><input type="date" name="check_in" class="form-control" required></div>
                        <div class="col-6"><label class="form-label">Check-out</label><input type="date" name="check_out" class="form-control" required></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Guests</label>
                        <select name="guests" class="form-select">
                            <?php for ($i = 1; $i <= 8; $i++): ?><option value="<?= $i ?>"><?= $i ?></option><?php endfor; ?>
                        </select>
                    </div>
                    <?php if ($hasRealRooms): ?>
                    <button class="btn btn-primary w-100">Book now</button>
                    <?php else: ?>
                    <a href="<?= BASE_URL ?>#contact" class="btn btn-primary w-100">Contact host</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <h4>About</h4>
            <p class="text-muted"><?= nl2br(e($h['description'])) ?></p>

            <?php if ($ams): ?>
            <h4 class="mt-4">Amenities</h4>
            <div class="amenity-grid mb-4">
                <?php foreach ($ams as $a): ?>
                <div class="amenity-item"><i class="fas <?= e($a['icon']) ?>"></i> <?= e($a['name']) ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <h4 class="fw-bold text-dark mt-4 mb-3"><i class="fas fa-bed text-teal me-2"></i>Available Rooms</h4>
            <div class="d-flex flex-column gap-3 mb-4">
                <?php foreach ($rooms as $fallbackIndex => $r):
                    $isFallbackRoom = !empty($r['is_fallback']);
                    // Fetch room images sorted by sort_order
                    $rImages = [];
                    if (!$isFallbackRoom) {
                        $roomImagesQuery = $conn->prepare("SELECT image_path FROM room_images WHERE room_id = ? ORDER BY sort_order ASC, id ASC");
                        $roomImagesQuery->execute([$r['id']]);
                        $rImages = $roomImagesQuery->fetchAll(PDO::FETCH_COLUMN);
                    }

                    // Combine cover image with other images
                    $allRoomImages = [];
                    if (!empty($r['cover_image'])) {
                        $allRoomImages[] = $r['cover_image'];
                    }
                    foreach ($rImages as $img) {
                        if ($img !== $r['cover_image']) {
                            $allRoomImages[] = $img;
                        }
                    }
                    if (empty($allRoomImages)) {
                        $allRoomImages[] = ''; // default placeholder
                    }
                    $roomPhotoUrls = room_photo_urls($allRoomImages, $isFallbackRoom ? ($fallbackIndex + 1) : (int)$r['id']);
                    $roomDomId = $isFallbackRoom ? 'fallback-' . ($fallbackIndex + 1) : (string)(int)$r['id'];
                ?>
                <div class="card border rounded-4 overflow-hidden shadow-xs room-detail-card transition hover-shadow-sm">
                    <div class="row g-0">
                        <div class="col-md-4 bg-light position-relative" style="min-height: 160px;">
                            <!-- Main Room Image -->
                            <img src="<?= e($roomPhotoUrls[0]) ?>" id="room-main-img-<?= e($roomDomId) ?>" class="w-100 h-100 object-fit-cover room-main-img cursor-pointer" style="min-height: 160px; max-height: 220px;" alt="<?= e($r['name']) ?>" data-src-list="<?= e(json_encode($roomPhotoUrls)) ?>" data-index="0">

                            <!-- Photos Count Badge -->
                            <?php if (count($roomPhotoUrls) > 1): ?>
                            <span class="position-absolute top-0 end-0 bg-dark bg-opacity-70 text-white px-2 py-0.5 rounded-3 m-2 small fw-semibold" style="font-size: 0.7rem; z-index: 6;"><i class="far fa-images me-1"></i><?= count($roomPhotoUrls) ?> Photos</span>
                            <?php endif; ?>

                            <!-- Thumbnail Strip if multiple images -->
                            <?php if (count($roomPhotoUrls) > 1): ?>
                            <div class="position-absolute bottom-0 start-0 w-100 p-1.5 bg-dark bg-opacity-40 d-flex gap-1 overflow-x-auto" style="scrollbar-width:none; z-index: 5;">
                                <?php foreach ($roomPhotoUrls as $realIndex => $thumbUrl): ?>
                                <img src="<?= e($thumbUrl) ?>" class="rounded border border-white border-opacity-50 cursor-pointer object-fit-cover room-thumb-nav" style="width: 36px; height: 26px; opacity: <?= $realIndex === 0 ? '1' : '0.6' ?>" onclick="swapRoomImage('<?= e($roomDomId) ?>', '<?= e($thumbUrl) ?>', <?= $realIndex ?>)" alt="Thumb">
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-8 p-3.5 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                    <h5 class="fw-bold text-dark mb-0 mt-0"><?= e($r['name']) ?></h5>
                                    <span class="badge bg-light text-muted border"><?= e($r['room_type']) ?></span>
                                </div>
                                <p class="text-muted small mb-2.5" style="line-height: 1.4"><?= e($r['description'] ?: 'No description provided for this room.') ?></p>

                                <div class="d-flex flex-wrap gap-3 mb-3 small text-muted">
                                    <span><i class="fas fa-users text-teal me-1.5"></i>Max <?= (int)$r['max_guests'] ?> Guest<?= $r['max_guests'] > 1 ? 's' : '' ?></span>
                                    <span><i class="fas fa-bed text-teal me-1.5"></i><?= (int)$r['beds'] ?> Bed<?= $r['beds'] > 1 ? 's' : '' ?></span>
                                    <?php if ((float)$r['cleaning_fee'] > 0): ?>
                                    <span><i class="fas fa-broom text-teal me-1.5"></i><?= money($r['cleaning_fee']) ?> cleaning fee</span>
                                    <?php endif; ?>
                                </div>
                                <div class="room-feature-chips room-feature-chips-sm">
                                    <span><i class="fas fa-shower"></i>Hot Water</span>
                                    <span><i class="fas fa-mug-hot"></i>Breakfast</span>
                                    <span><i class="fas fa-car"></i>Parking</span>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pt-2.5 border-top">
                                <div>
                                    <span class="fs-5 fw-bold text-teal-deep"><?= money($r['price_per_night']) ?></span>
                                    <span class="text-muted small">/ night</span>
                                </div>
                                <?php if ($isFallbackRoom): ?>
                                <?php $fallbackBookUrl = BASE_URL . 'pages/book.php?homestay_id=1&fallback_room=' . ($fallbackIndex + 1); ?>
                                <a href="<?= e(is_logged_in() ? $fallbackBookUrl : login_url($fallbackBookUrl)) ?>" class="btn btn-sm btn-outline-teal px-3 fw-bold rounded-pill">Book</a>
                                <?php else: ?>
                                <button type="button" class="btn btn-sm btn-outline-teal px-3 fw-bold rounded-pill" onclick="selectRoomForBooking(<?= (int)$r['id'] ?>)">
                                    Choose Room
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <h4 class="fw-bold text-dark mt-4 mb-3"><i class="fas fa-star text-warning me-2"></i>Guest Reviews (<?= count($reviews) ?>)</h4>

            <?php if (empty($reviews)): ?>
                <div class="card p-3 text-center bg-light border">
                    <p class="text-muted small mb-0">No guest reviews yet. Be the first traveler to stay here and leave a review!</p>
                </div>
            <?php else: foreach ($reviews as $rv): ?>
                <div class="card p-3 mb-2 border">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong class="text-dark small"><?= e($rv['full_name']) ?></strong>
                        <div><?= stars((float)$rv['rating']) ?></div>
                    </div>
                    <?php if (!empty($rv['title'])): ?>
                        <h6 class="fw-bold small mb-1"><?= e($rv['title']) ?></h6>
                    <?php endif; ?>
                    <p class="text-muted small mb-0"><?= e($rv['comment']) ?></p>

                    <?php if (!empty($rv['owner_reply'])): ?>
                        <div class="bg-light p-2 mt-2 rounded border-start border-primary border-3 small">
                            <strong class="text-dark d-block mb-0.5">Host Reply:</strong>
                            <span class="text-muted"><?= e($rv['owner_reply']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; endif; ?>

            <div class="dash-card mt-4">
                <h5>Hosted by <?= e($h['owner_name']) ?></h5>
                <?php if ($h['business_name']): ?><p class="text-muted mb-0"><?= e($h['business_name']) ?></p><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Room Lightbox Modal -->
<div class="modal fade" id="roomLightboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-transparent border-0 position-relative">
            <button type="button" class="btn-close btn-close-white ms-auto mb-2" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="position-relative bg-black rounded-4 overflow-hidden d-flex align-items-center justify-content-center" style="min-height: 300px">
                <img src="" id="roomLightboxImg" class="w-100 object-fit-contain" style="max-height: 70vh; transition: opacity 0.2s ease-in-out;" alt="Room Preview">
                <button type="button" id="roomLightboxPrev" class="btn btn-dark rounded-circle opacity-75 position-absolute start-0 top-50 translate-middle-y ms-3" style="width:40px; height:40px; display:none; align-items:center; justify-content:center; z-index:10" title="Previous">
                    <i class="fas fa-chevron-left text-white"></i>
                </button>
                <button type="button" id="roomLightboxNext" class="btn btn-dark rounded-circle opacity-75 position-absolute end-0 top-50 translate-middle-y me-3" style="width:40px; height:40px; display:none; align-items:center; justify-content:center; z-index:10" title="Next">
                    <i class="fas fa-chevron-right text-white"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function swapRoomImage(roomId, url, idx) {
    const mainImg = document.getElementById('room-main-img-' + roomId);
    if (mainImg) {
        mainImg.src = url;
        mainImg.setAttribute('data-index', idx);
    }

    // Update thumbnail opacities
    const container = mainImg.closest('.position-relative');
    if (container) {
        const thumbs = container.querySelectorAll('.room-thumb-nav');
        thumbs.forEach((t, i) => {
            t.style.opacity = (i === idx) ? '1' : '0.6';
        });
    }
}

function selectRoomForBooking(roomId) {
    const select = document.querySelector('select[name="room_id"]');
    if (select) {
        select.value = roomId;
        select.dispatchEvent(new Event('change'));

        // Scroll to booking widget
        const widget = document.querySelector('.booking-widget');
        if (widget) {
            widget.scrollIntoView({ behavior: 'smooth', block: 'center' });
            widget.classList.add('animate__animated', 'animate__flash');
            setTimeout(() => {
                widget.classList.remove('animate__animated', 'animate__flash');
            }, 1000);
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const roomLightboxModal = document.getElementById('roomLightboxModal');
    if (!roomLightboxModal || typeof bootstrap === 'undefined') return;

    const bsModal = new bootstrap.Modal(roomLightboxModal);
    const lightboxImg = document.getElementById('roomLightboxImg');
    const prevBtn = document.getElementById('roomLightboxPrev');
    const nextBtn = document.getElementById('roomLightboxNext');
    let currentList = [];
    let currentIndex = 0;

    document.querySelectorAll('.room-main-img').forEach(img => {
        img.addEventListener('click', function() {
            const listData = this.getAttribute('data-src-list');
            currentIndex = parseInt(this.getAttribute('data-index') || '0');
            try {
                currentList = JSON.parse(listData).filter(x => x !== '');
            } catch(e) {
                currentList = [this.src];
                currentIndex = 0;
            }

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

    document.addEventListener('keydown', function (e) {
        if (!roomLightboxModal.classList.contains('show')) return;
        if (e.key === 'ArrowLeft') {
            prevBtn.click();
        } else if (e.key === 'ArrowRight') {
            nextBtn.click();
        }
    });
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
