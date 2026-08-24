<?php
// Dedicated Single-Homestay Homepage for Sonam Homestay
require_once __DIR__ . '/includes/functions.php';

// Fetch Sonam Homestay details (ID: 1)
$h = $conn->query("
    SELECT h.*, u.full_name AS owner_name, o.business_name, u.phone AS owner_phone
    FROM homestays h
    JOIN owners o ON h.owner_id = o.id
    JOIN users u ON o.user_id = u.id
    WHERE h.id = 1 AND h.is_active = 1
")->fetch();

if (!$h) {
    // If not found, use defaults
    $h = [
        'id' => 1,
        'title' => 'Sonam Homestay',
        'description' => 'Close and yet far from the overcrowded tourist destinations of West Sikkim, Sonam Homestay is a patch of heaven, conveniently placed on top of a hill, right next to the famous Khecheopalri lake. Owned and run by Mr. Sonam Bhutia and his family, we share our lives with our guests and offer an authentic experience of Sikkimese life.',
        'address' => 'Near Khecheopalri Lake, West Sikkim',
        'city' => 'Khechuperi',
        'state' => 'Sikkim',
        'pincode' => '737113',
        'house_rules' => 'Quiet hours after 10 PM. No shoes inside rooms. Vegetarian meals only.',
        'cover_image' => null,
        'owner_name' => 'Sonam Bhutia',
        'business_name' => 'Sonam Homestay Sikkim'
    ];
}

// Fetch check-in/check-out parameters if redirected from search
$checkInParam = trim($_GET['check_in'] ?? '');
$checkOutParam = trim($_GET['check_out'] ?? '');
$guestsParam = max(1, (int)($_GET['guests'] ?? 1));

// Fetch all available rooms for Sonam Homestay
$rooms = $conn->query("SELECT * FROM rooms WHERE homestay_id = 1 AND is_active = 1 ORDER BY price_per_night ASC")->fetchAll();
if (empty($rooms)) {
    $rooms = fallback_public_rooms();
}

// Fetch amenities
$ams = $conn->query("
    SELECT a.*
    FROM homestay_amenities ha
    JOIN amenities a ON ha.amenity_id = a.id
    WHERE ha.homestay_id = 1
    ORDER BY a.name
")->fetchAll();

// Fetch reviews for Sonam Homestay
$reviews = $conn->query("
    SELECT r.*, u.full_name
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.homestay_id = 1 AND r.is_approved = 1
    ORDER BY r.created_at DESC
    LIMIT 6
")->fetchAll();

// Fetch homestay slider images
$homestayImages = $conn->query("SELECT image_path FROM homestay_images WHERE homestay_id = 1")->fetchAll(PDO::FETCH_COLUMN);
if (empty($homestayImages) && !empty($h['cover_image'])) {
    $homestayImages = [$h['cover_image']];
}

$heroImage = homepage_hero_image();
$aboutImage = file_exists(UPLOAD_HOMESTAYS . 'hs_cover_1787497039_6210.jpg')
    ? asset('uploads/homestays/hs_cover_1787497039_6210.jpg')
    : $heroImage;

$pageTitle = 'Home';
require __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero home-hero position-relative overflow-hidden" id="top" style="--hero-bg-img: url('<?= e($heroImage) ?>');">
    <div class="container py-5">
        <div class="hero-layout">
            <div class="hero-copy text-white" data-aos="fade-right">
                <span class="hero-kicker"><i class="fas fa-map-marker-alt"></i>Khecheopalri Lake, West Sikkim</span>
                <h1 class="hero-title display-font"><?= e($h['title']) ?></h1>
                <p class="hero-lead">A peaceful family-run hilltop stay for lake walks, monastery visits, organic home-cooked meals, and quiet Himalayan mornings.</p>
                <div class="hero-actions">
                    <a href="#rooms" class="btn btn-primary btn-lg fw-bold shadow-sm"><i class="fas fa-bed"></i>View Rooms</a>
                    <a href="#about" class="btn btn-outline-light btn-lg fw-bold"><i class="fas fa-compass"></i>Explore Stay</a>
                </div>
            </div>

            <div class="hero-stay-card" data-aos="fade-left">
                <span class="small text-uppercase fw-bold text-teal">Hosted Experience</span>
                <strong>Family care, fresh meals, local guidance.</strong>
                <div class="hero-stay-icons">
                    <span><i class="fas fa-mountain-sun"></i>Views</span>
                    <span><i class="fas fa-seedling"></i>Organic Food</span>
                    <span><i class="fas fa-person-hiking"></i>Walks</span>
                </div>
            </div>
        </div>

        <form action="<?= BASE_URL ?>pages/search.php" method="GET" class="availability-panel" id="availability" data-aos="fade-up">
            <div class="availability-head">
                <span>Check Availability</span>
                <small>Choose your dates and see rooms instantly</small>
            </div>
            <div class="availability-field">
                <label for="checkInDate">Check-in</label>
                <input type="date" name="check_in" id="checkInDate" min="<?= date('Y-m-d') ?>" value="<?= e($checkInParam) ?>" required>
            </div>
            <div class="availability-field">
                <label for="checkOutDate">Check-out</label>
                <input type="date" name="check_out" id="checkOutDate" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= e($checkOutParam) ?>" required>
            </div>
            <div class="availability-field">
                <label for="guestsCountSelect">Guests</label>
                <select name="guests" id="guestsCountSelect" required>
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                    <option value="<?= $i ?>" <?= $guestsParam == $i ? 'selected' : '' ?>><?= $i ?> Guest<?= $i > 1 ? 's' : '' ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary availability-submit"><i class="fas fa-search"></i>Search Rooms</button>
        </form>
    </div>
</section>

<section class="experience-strip border-bottom bg-white">
    <div class="container">
        <div class="row g-0 text-center text-md-start">
            <div class="col-md-3 experience-item">
                <strong>Family hosted</strong>
                <span>Run by Sonam Bhutia and family</span>
            </div>
            <div class="col-md-3 experience-item">
                <strong>Near the lake</strong>
                <span>Close to Khecheopalri Lake and monastery</span>
            </div>
            <div class="col-md-3 experience-item">
                <strong>Village comfort</strong>
                <span>Quiet rooms, fresh air, and hilltop views</span>
            </div>
            <div class="col-md-3 experience-item">
                <strong>Local food</strong>
                <span>Home-cooked meals from seasonal produce</span>
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="section py-5 border-bottom" id="about">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6" data-aos="fade-right">
                <div class="position-relative">
                    <img src="<?= e($aboutImage) ?>" class="img-fluid rounded-3 shadow-md w-100 about-photo" alt="Khecheopalri Lake view near Sonam Homestay">
                    <div class="position-absolute bottom-0 start-0 m-4 p-3 bg-white rounded-3 shadow border d-flex align-items-center gap-3 about-badge" style="max-width: 280px">
                        <i class="fas fa-heart text-teal fs-3"></i>
                        <div>
                            <strong class="text-dark small d-block">Family Managed</strong>
                            <span class="text-muted small">Personal care, local guidance, home meals</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <span class="text-teal fw-bold text-uppercase small" style="letter-spacing: 0.05em">Authentic West Sikkim Stay</span>
                <h2 class="display-font fw-bold text-teal-deep h1 mt-2 mb-3">A quiet home above Khecheopalri Lake</h2>
                <p class="text-secondary lh-lg mb-4"><?= nl2br(e($h['description'])) ?></p>
                <div class="row g-3">
                    <div class="col-6">
                        <h6 class="fw-bold mb-1"><i class="fas fa-circle-check text-teal me-2"></i>Best For</h6>
                        <p class="text-muted small mb-0">Nature lovers, families, trekkers, slow travelers</p>
                    </div>
                    <div class="col-6">
                        <h6 class="fw-bold mb-1"><i class="fas fa-circle-check text-teal me-2"></i>Experience</h6>
                        <p class="text-muted small mb-0">Lake walks, monasteries, sunrise views, local food</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Amenities Section -->
<?php if (!empty($ams)): ?>
<section class="section py-5 bg-light border-bottom" id="amenities">
    <div class="container">
        <div class="section-header text-center mb-5" data-aos="fade-up">
            <h2 class="display-font fw-bold text-dark mb-2">Our Amenities</h2>
            <p class="text-muted mb-0">Comfort and services provided during your stay at Sonam Homestay</p>
        </div>
        <div class="row g-4 justify-content-center">
            <?php foreach ($ams as $a): ?>
            <div class="col-6 col-md-4 col-lg-3" data-aos="fade-up">
                <div class="card p-3 border rounded-3 bg-white text-center shadow-xs h-100 hover-shadow transition">
                    <div class="fs-3 text-teal mb-2"><i class="fas <?= e($a['icon']) ?>"></i></div>
                    <h6 class="fw-semibold text-dark mb-0"><?= e($a['name']) ?></h6>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Our Rooms Section -->
<section class="section py-5" id="rooms">
    <div class="container">
        <div class="section-header text-center mb-5" data-aos="fade-up">
            <h2 class="display-font fw-bold text-dark mb-2">Available Rooms</h2>
            <p class="text-muted mb-0">Choose your perfect accommodation option in Sonam Homestay</p>
        </div>

        <div class="d-flex flex-column gap-4">
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
                    $allRoomImages[] = '';
                }

                $roomSeed = $isFallbackRoom ? ($fallbackIndex + 1) : (int)$r['id'];
                $roomPhotoUrls = room_photo_urls($allRoomImages, $roomSeed);
                $mainRoomImgUrl = $roomPhotoUrls[0];
                $roomDomId = $isFallbackRoom ? 'fallback-' . ($fallbackIndex + 1) : (string)(int)$r['id'];
                $roomDetailUrl = $isFallbackRoom ? BASE_URL . 'pages/room-details.php?fallback_room=' . ($fallbackIndex + 1) : BASE_URL . 'pages/room-details.php?id=' . (int)$r['id'];
                $roomHomestayId = (int)($r['homestay_id'] ?? $h['id'] ?? 1);
                $roomBookUrl = $isFallbackRoom ? BASE_URL . 'pages/book.php?homestay_id=' . $roomHomestayId . '&fallback_room=' . ($fallbackIndex + 1) : BASE_URL . 'pages/book.php?homestay_id=' . $roomHomestayId . '&room_id=' . (int)$r['id'];
                $roomBookActionUrl = is_logged_in() ? $roomBookUrl : login_url($roomBookUrl);
            ?>
            <div class="card border rounded-4 overflow-hidden shadow-sm room-detail-card transition hover-shadow" data-aos="fade-up">
                <div class="row g-0">
                    <div class="col-md-5 bg-light position-relative room-photo-shell">
                        <img src="<?= e($mainRoomImgUrl) ?>" id="room-main-img-<?= e($roomDomId) ?>" class="w-100 h-100 object-fit-cover room-main-img cursor-pointer" alt="<?= e($r['name']) ?>" data-src-list="<?= e(json_encode($roomPhotoUrls)) ?>" data-index="0">

                        <button type="button" class="room-photo-preview" data-room-preview="<?= e($roomDomId) ?>" aria-label="View <?= e($r['name']) ?> photos">
                            <i class="fas fa-eye"></i>
                            <span>View Photos</span>
                        </button>

                        <!-- Photos Count Badge -->
                        <?php if (count($roomPhotoUrls) > 1): ?>
                        <span class="room-photo-count"><i class="far fa-images"></i><?= count($roomPhotoUrls) ?> Photos</span>
                        <?php endif; ?>

                        <!-- Thumbnail Strip if multiple images -->
                        <?php if (count($roomPhotoUrls) > 1): ?>
                        <div class="room-thumb-strip">
                            <?php foreach ($roomPhotoUrls as $realIndex => $thumbUrl): ?>
                            <img src="<?= e($thumbUrl) ?>" class="rounded border border-white border-opacity-50 cursor-pointer object-fit-cover room-thumb-nav" style="opacity: <?= $realIndex === 0 ? '1' : '0.6' ?>" onclick="swapRoomImage('<?= e($roomDomId) ?>', '<?= e($thumbUrl) ?>', <?= $realIndex ?>)" alt="Room photo thumbnail">
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-7 p-4 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <h4 class="fw-bold text-dark mb-0 mt-0 display-font"><?= e($r['name']) ?></h4>
                                <span class="badge bg-light text-teal border border-teal border-opacity-25 px-2.5 py-1 rounded-pill small"><?= e($r['room_type']) ?></span>
                            </div>
                            <p class="text-secondary small mb-3.5 lh-lg"><?= e($r['description'] ?: 'No description provided for this room.') ?></p>

                            <div class="d-flex flex-wrap gap-4 mb-4 text-muted">
                                <span><i class="fas fa-users text-teal me-2"></i>Max <?= (int)$r['max_guests'] ?> Guest<?= $r['max_guests'] > 1 ? 's' : '' ?></span>
                                <span><i class="fas fa-bed text-teal me-2"></i><?= (int)$r['beds'] ?> Bed<?= $r['beds'] > 1 ? 's' : '' ?></span>
                                <?php if ((float)$r['cleaning_fee'] > 0): ?>
                                <span><i class="fas fa-broom text-teal me-2"></i><?= money($r['cleaning_fee']) ?> cleaning fee</span>
                                <?php endif; ?>
                            </div>
                            <div class="room-feature-chips">
                                <span><i class="fas fa-mug-hot"></i>Breakfast</span>
                                <span><i class="fas fa-shower"></i>Hot Water</span>
                                <span><i class="fas fa-mountain-sun"></i>Mountain Air</span>
                                <span><i class="fas fa-car"></i>Parking</span>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <div>
                                <span class="fs-4 fw-bold text-teal-deep"><?= money($r['price_per_night']) ?></span>
                                <span class="text-muted small">/ night</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="<?= e($roomDetailUrl) ?>" class="btn btn-outline-teal px-3 fw-bold rounded-3 <?= $isFallbackRoom ? '' : 'btn-details-room' ?>" <?= $isFallbackRoom ? '' : 'data-base-url="' . BASE_URL . 'pages/room-details.php?id=' . (int)$r['id'] . '"' ?>>
                                    <i class="fas fa-circle-info me-1.5"></i>View Details
                                </a>
                                <a href="<?= e($roomBookActionUrl) ?>" class="btn btn-primary px-3 fw-bold rounded-3 btn-book-room" data-base-url="<?= e($roomBookUrl) ?>">
                                    <i class="fas fa-calendar-check me-1.5"></i><?= $isFallbackRoom ? 'Book' : 'Book Room' ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Highlights Section -->
<section class="section py-5 bg-light border-top border-bottom" id="why-us">
    <div class="container">
        <div class="section-header text-center mb-5" data-aos="fade-up">
            <h2 class="display-font fw-bold text-dark mb-2">Why Sonam Homestay</h2>
            <p class="text-muted mb-0">Everything here is shaped around place, people, food, and unhurried mountain days.</p>
        </div>
        <div class="row g-4 justify-content-center">
            <!-- Highlight 1 -->
            <div class="col-md-6 col-lg-4">
                <div class="feature-block h-100 p-4 rounded-4 border bg-white shadow-sm transition hover-translate" data-aos="fade-up" data-aos-delay="0">
                    <div class="feature-icon bg-teal-light text-teal fs-4 mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 50px; height: 50px;"><i class="fas fa-map-location-dot"></i></div>
                    <h5 class="fw-bold mb-2">Scenic Hilltop Location</h5>
                    <p class="text-muted mb-0 small" style="line-height: 1.6">Conveniently situated on top of a hill right next to the famous Khecheopalri Lake, offering 360-degree panoramic views of the surrounding landscape and valleys.</p>
                </div>
            </div>
            <!-- Highlight 2 -->
            <div class="col-md-6 col-lg-4">
                <div class="feature-block h-100 p-4 rounded-4 border bg-white shadow-sm transition hover-translate" data-aos="fade-up" data-aos-delay="50">
                    <div class="feature-icon bg-teal-light text-teal fs-4 mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 50px; height: 50px;"><i class="fa-solid fa-mountain-sun"></i></div>
                    <h5 class="fw-bold mb-2">Himalayan Peak Views</h5>
                    <p class="text-muted mb-0 small" style="line-height: 1.6">Enjoy breathtaking, clear views of majestic snow-capped Himalayan peaks, including Mt. Pandim, Mt. Narsingh, and other surrounding ridges.</p>
                </div>
            </div>
            <!-- Highlight 3 -->
            <div class="col-md-6 col-lg-4">
                <div class="feature-block h-100 p-4 rounded-4 border bg-white shadow-sm transition hover-translate" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-icon bg-teal-light text-teal fs-4 mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 50px; height: 50px;"><i class="fas fa-sun"></i></div>
                    <h5 class="fw-bold mb-2">Mesmerizing Sunrise Point</h5>
                    <p class="text-muted mb-0 small" style="line-height: 1.6">Just a one-minute walk to the edge of the hill reveals an incredible sunrise point with panoramic 180-degree vistas over the mountains.</p>
                </div>
            </div>
            <!-- Highlight 4 -->
            <div class="col-md-6 col-lg-4">
                <div class="feature-block h-100 p-4 rounded-4 border bg-white shadow-sm transition hover-translate" data-aos="fade-up" data-aos-delay="150">
                    <div class="feature-icon bg-teal-light text-teal fs-4 mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 50px; height: 50px;"><i class="fas fa-person-hiking"></i></div>
                    <h5 class="fw-bold mb-2">Walk to Khecheopalri Lake</h5>
                    <p class="text-muted mb-0 small" style="line-height: 1.6">Take a short 20-minute downhill walk along a peaceful forest trail that leads directly to the sacred waters of Khecheopalri Lake.</p>
                </div>
            </div>
            <!-- Highlight 5 -->
            <div class="col-md-6 col-lg-4">
                <div class="feature-block h-100 p-4 rounded-4 border bg-white shadow-sm transition hover-translate" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-icon bg-teal-light text-teal fs-4 mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 50px; height: 50px;"><i class="fas fa-dharmachakra"></i></div>
                    <h5 class="fw-bold mb-2">Sacred Buddhist Monasteries</h5>
                    <p class="text-muted mb-0 small" style="line-height: 1.6">Explore the old Buddhist monasteries located right next to the homestay and beside the lake below, admiring traditional Buddhist architecture and art.</p>
                </div>
            </div>
            <!-- Highlight 6 -->
            <div class="col-md-6 col-lg-4">
                <div class="feature-block h-100 p-4 rounded-4 border bg-white shadow-sm transition hover-translate" data-aos="fade-up" data-aos-delay="250">
                    <div class="feature-icon bg-teal-light text-teal fs-4 mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 50px; height: 50px;"><i class="fas fa-utensils"></i></div>
                    <h5 class="fw-bold mb-2">Fresh Organic Local Food</h5>
                    <p class="text-muted mb-0 small" style="line-height: 1.6">Indulge in fresh, healthy, organic meals cooked by the family, prepared with vegetables and ingredients grown directly on the local farm.</p>
                </div>
            </div>
            <!-- Highlight 7 -->
            <div class="col-md-6 col-lg-4">
                <div class="feature-block h-100 p-4 rounded-4 border bg-white shadow-sm transition hover-translate" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-icon bg-teal-light text-teal fs-4 mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 50px; height: 50px;"><i class="fas fa-route"></i></div>
                    <h5 class="fw-bold mb-2">Enchanting Trekking Routes</h5>
                    <p class="text-muted mb-0 small" style="line-height: 1.6">Trek along forest paths to reach breathtaking viewpoints, like Vajare Hill top at 3500m, offering panoramic vistas of the Kanchenjunga range.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Guest Reviews Section -->
<section class="section py-5" id="guest-reviews">
    <div class="container">
        <div class="section-header text-center mb-5" data-aos="fade-up">
            <h2 class="display-font fw-bold text-dark mb-2">Guest Reviews</h2>
            <p class="text-muted mb-0">What travelers say about their experiences staying at Sonam Homestay</p>
        </div>

        <div class="row g-4">
            <?php if (empty($reviews)): ?>
                <div class="col-12 text-center text-muted small py-4">
                    No guest reviews yet. Stay with us and share your feedback!
                </div>
            <?php else: foreach ($reviews as $rv): ?>
                <div class="col-md-6 col-lg-4" data-aos="fade-up">
                    <div class="card p-4 border rounded-4 shadow-xs bg-white h-100 hover-shadow transition">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong class="text-dark small"><i class="far fa-circle-user text-teal me-1.5"></i><?= e($rv['full_name']) ?></strong>
                            <div><?= stars((float)$rv['rating']) ?></div>
                        </div>
                        <?php if (!empty($rv['title'])): ?>
                            <h6 class="fw-bold small mb-2 text-dark"><?= e($rv['title']) ?></h6>
                        <?php endif; ?>
                        <p class="text-muted small mb-0 lh-lg font-italic">"<?= e($rv['comment']) ?>"</p>

                        <?php if (!empty($rv['owner_reply'])): ?>
                            <div class="bg-light p-2.5 mt-3 rounded border-start border-teal border-3 small">
                                <strong class="text-dark d-block mb-1">Host Reply:</strong>
                                <span class="text-muted font-italic">"<?= e($rv['owner_reply']) ?>"</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</section>

<!-- Location Contact / Map Section -->
<section class="section py-5 bg-light border-top" id="contact">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-md-6" data-aos="fade-right">
                <span class="text-teal fw-bold text-uppercase small" style="letter-spacing: 0.05em">Get In Touch</span>
                <h2 class="display-font fw-bold text-teal-deep mt-2 mb-3">Host Contact & Address</h2>
                <div class="d-flex flex-column gap-3 mt-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white border rounded-circle text-teal d-flex align-items-center justify-content-center fs-5 shadow-xs" style="width: 44px; height: 44px;"><i class="fas fa-map-pin"></i></div>
                        <div>
                            <strong class="text-dark small d-block">Homestay Address</strong>
                            <span class="text-muted small"><?= e($h['address']) ?>, <?= e($h['city']) ?>, <?= e($h['state']) ?> - <?= e($h['pincode']) ?></span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white border rounded-circle text-teal d-flex align-items-center justify-content-center fs-5 shadow-xs" style="width: 44px; height: 44px;"><i class="fas fa-phone-alt"></i></div>
                        <div>
                            <strong class="text-dark small d-block">Phone / Mobile</strong>
                            <span class="text-muted small"><?= e($h['owner_phone'] ?: '+91-9735589678') ?></span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white border rounded-circle text-teal d-flex align-items-center justify-content-center fs-5 shadow-xs" style="width: 44px; height: 44px;"><i class="far fa-envelope"></i></div>
                        <div>
                            <strong class="text-dark small d-block">Email Address</strong>
                            <span class="text-muted small">sonamhomestay01@gmail.com</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6" data-aos="fade-left">
                <!-- Google Maps Frame for Sonam Homestay -->
                <div class="ratio ratio-16x9 rounded-4 overflow-hidden border shadow-sm">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3536.0381488107936!2d88.19077757626998!3d27.345225776391483!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39e687d969f0543f%3A0x81200b1d6e36c123!2sSonam%20Homestay!5e0!3m2!1sen!2sin!4v1724434220000!5m2!1sen!2sin" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Lightbox Modal for Room Photos -->
<div class="modal fade" id="roomLightboxModal" tabindex="-1" aria-hidden="true" style="z-index: 1060">
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
// Room image swap thumbnails navigations
function swapRoomImage(roomId, url, idx) {
    const mainImg = document.getElementById('room-main-img-' + roomId);
    if (mainImg) {
        mainImg.src = url;
        mainImg.setAttribute('data-index', idx);
    }
    const container = mainImg.closest('.position-relative');
    if (container) {
        const thumbs = container.querySelectorAll('.room-thumb-nav');
        thumbs.forEach((t, i) => {
            t.style.opacity = (i === idx) ? '1' : '0.6';
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Dynamic updates for Booking Links
    const checkInInput = document.getElementById('checkInDate');
    const checkOutInput = document.getElementById('checkOutDate');
    const guestsInput = document.getElementById('guestsCountSelect');
    const userIsLoggedIn = <?= is_logged_in() ? 'true' : 'false' ?>;
    const loginBaseUrl = '<?= BASE_URL ?>authentication/login.php?redirect=';

    function updateBookingLinks() {
        const checkIn = checkInInput ? checkInInput.value : '';
        const checkOut = checkOutInput ? checkOutInput.value : '';
        const guests = guestsInput ? guestsInput.value : '1';

        document.querySelectorAll('.btn-book-room').forEach(btn => {
            const baseUrl = btn.getAttribute('data-base-url');
            let url = baseUrl + '&check_in=' + encodeURIComponent(checkIn) + '&check_out=' + encodeURIComponent(checkOut) + '&guests=' + encodeURIComponent(guests);
            btn.setAttribute('href', userIsLoggedIn ? url : loginBaseUrl + encodeURIComponent(url));
        });

        document.querySelectorAll('.btn-details-room').forEach(btn => {
            const baseUrl = btn.getAttribute('data-base-url');
            let url = baseUrl + '&check_in=' + encodeURIComponent(checkIn) + '&check_out=' + encodeURIComponent(checkOut) + '&guests=' + encodeURIComponent(guests);
            btn.setAttribute('href', url);
        });
    }

    if (checkInInput) checkInInput.addEventListener('change', updateBookingLinks);
    if (checkOutInput) checkOutInput.addEventListener('change', updateBookingLinks);
    if (guestsInput) guestsInput.addEventListener('change', updateBookingLinks);
    document.querySelectorAll('.btn-book-room').forEach(btn => {
        btn.addEventListener('click', function() {
            updateBookingLinks();
        });
    });

    // Initial trigger
    updateBookingLinks();

    // Setup room lightbox zoom
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

    document.querySelectorAll('[data-room-preview]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const mainImg = document.getElementById('room-main-img-' + this.getAttribute('data-room-preview'));
            if (mainImg) mainImg.click();
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

<?php require __DIR__ . '/includes/footer.php'; ?>
