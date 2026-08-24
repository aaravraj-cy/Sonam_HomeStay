<?php
// Photo Gallery page
require_once __DIR__ . '/../includes/functions.php';

// Fetch general gallery images from uploads
$galleryImages = [];
try {
    $galleryImages = $conn->query("SELECT gi.image_path, gi.title, gi.city
        FROM gallery_images gi
        ORDER BY gi.sort_order ASC, gi.id DESC
        LIMIT 48")->fetchAll();
} catch (Exception $e) {
    $galleryImages = [];
}

if (empty($galleryImages) && is_dir(UPLOAD_GALLERY)) {
    $files = glob(UPLOAD_GALLERY . '*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE) ?: [];
    usort($files, function ($a, $b) {
        return filemtime($b) <=> filemtime($a);
    });

    foreach (array_slice($files, 0, 48) as $file) {
        $name = basename($file);
        $galleryImages[] = [
            'image_path' => $name,
            'title' => ucwords(str_replace(['-', '_'], ' ', pathinfo($name, PATHINFO_FILENAME))),
            'city' => 'Sikkim',
        ];
    }
}

$resolvedGalleryImages = [];
foreach ($galleryImages as $ex) {
    $imgUrl = gallery_image_url($ex['image_path'] ?? '');
    if ($imgUrl === asset('images/placeholder-homestay.svg')) {
        continue;
    }
    $ex['img_url'] = $imgUrl;
    $resolvedGalleryImages[] = $ex;
}
$featuredGalleryImages = array_slice($resolvedGalleryImages, 0, min(6, count($resolvedGalleryImages)));
$heroGalleryImage = $featuredGalleryImages[0]['img_url'] ?? homepage_hero_image();
$galleryLocations = array_filter(array_unique(array_map(function ($image) {
    return trim($image['city'] ?? '');
}, $resolvedGalleryImages)));

$pageTitle = 'Gallery';
require __DIR__ . '/../includes/header.php';
?>
<!-- Gallery Hero -->
<section class="gallery-page-hero animate__animated animate__fadeIn" style="--gallery-hero-image: url('<?= e($heroGalleryImage) ?>')">
    <div class="container">
        <div class="gallery-page-hero-inner">
            <span class="gallery-eyebrow"><i class="far fa-images"></i> Sonam Homestay Gallery</span>
            <h1 class="display-font text-white mb-3">Rooms, mountain views, and quiet Sikkim moments.</h1>
            <p class="mb-4">A polished photo tour of the stay experience, from room details to the landscape guests come here for.</p>
            <div class="gallery-hero-actions">
                <a href="#galleryShowcase" class="btn btn-primary"><i class="fas fa-play"></i> View Highlights</a>
                <a href="#galleryGrid" class="btn btn-light"><i class="fas fa-border-all"></i> Browse Photos</a>
            </div>
        </div>
        <div class="gallery-hero-stats" aria-label="Gallery summary">
            <span><strong><?= count($resolvedGalleryImages) ?></strong> Photos</span>
            <span><strong><?= count($galleryLocations) ?></strong> Location<?= count($galleryLocations) === 1 ? '' : 's' ?></span>
            <span><strong>Full</strong> Preview</span>
        </div>
    </div>
</section>

<section class="gallery-page-section animate__animated animate__fadeIn animate__delay-1s">
    <div class="container">
        <?php if (empty($resolvedGalleryImages)): ?>
            <div class="empty-state gallery-empty-state" data-aos="fade-up">
                <i class="far fa-image fs-1 mb-3"></i>
                <h2 class="display-font text-teal-deep mb-2">Gallery photos are coming soon.</h2>
                <p class="text-muted mb-0">Upload photos into the gallery assets or database and they will appear here automatically.</p>
            </div>
        <?php else: ?>

            <div class="gallery-showcase mb-5" id="galleryShowcase" data-aos="fade-up">
                <div class="gallery-showcase-copy">
                    <span class="section-kicker">Sonam Homestay Gallery</span>
                    <h2 class="display-font text-white mb-2">A closer look at the stay, views, and local moments.</h2>
                    <p class="mb-0">Selected highlights rotate automatically. Use the thumbnails or arrows to move through the collection.</p>
                </div>
                <div class="gallery-slider" id="gallerySlider" aria-label="Featured gallery photos">
                    <?php foreach ($featuredGalleryImages as $i => $ex):
                        $imgUrl = $ex['img_url'];
                        $title = $ex['title'] ?: 'Gallery Photo';
                        $city = $ex['city'] ?: 'Sikkim';
                    ?>
                    <button type="button" class="gallery-slide <?= $i === 0 ? 'is-active' : '' ?>" data-slide-index="<?= $i ?>" data-lightbox-trigger data-src="<?= e($imgUrl) ?>" data-title="<?= e($title) ?>" data-city="<?= e($city) ?>" aria-label="View <?= e($title) ?>">
                        <img src="<?= e($imgUrl) ?>" alt="<?= e($title) ?>" loading="<?= $i === 0 ? 'eager' : 'lazy' ?>">
                        <span class="gallery-slide-caption">
                            <small><i class="fas fa-map-marker-alt"></i> <?= e($city) ?></small>
                            <strong><?= e($title) ?></strong>
                        </span>
                    </button>
                    <?php endforeach; ?>

                    <div class="gallery-slider-controls">
                        <button type="button" class="gallery-slider-btn" id="gallerySliderPrev" aria-label="Previous featured photo"><i class="fas fa-chevron-left"></i></button>
                        <button type="button" class="gallery-slider-btn" id="gallerySliderNext" aria-label="Next featured photo"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>

                <div class="gallery-slider-thumbs" id="gallerySliderThumbs" aria-label="Featured gallery thumbnails">
                    <?php foreach ($featuredGalleryImages as $i => $ex):
                        $imgUrl = $ex['img_url'];
                        $title = $ex['title'] ?: 'Gallery Photo';
                    ?>
                    <button type="button" class="gallery-slider-thumb <?= $i === 0 ? 'is-active' : '' ?>" data-slide-index="<?= $i ?>" aria-label="Show <?= e($title) ?>">
                        <img src="<?= e($imgUrl) ?>" alt="" loading="lazy">
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="gallery-collection-head" data-aos="fade-up">
                <div>
                    <span class="section-kicker">Photo Collection</span>
                    <h2 class="display-font text-teal-deep mb-1">Explore the full gallery</h2>
                    <p class="text-muted mb-0">Hover for details, click any image for the animated preview.</p>
                </div>
                <span class="gallery-count-badge"><i class="far fa-images"></i> <?= count($resolvedGalleryImages) ?> Photos</span>
            </div>

            <!-- Gallery Grid -->
            <div class="gallery-grid" id="galleryGrid">
                <?php foreach ($resolvedGalleryImages as $i => $ex):
                    $imgUrl = $ex['img_url'];
                    $citySlug = strtolower(trim($ex['city'] ?? ''));
                    $title = $ex['title'] ?: 'Gallery Photo';
                    $city = $ex['city'] ?: 'Sikkim';
                ?>
                <div class="gallery-card-item position-relative cursor-pointer" data-location="<?= e($citySlug) ?>" data-aos="zoom-in" data-aos-delay="<?= min($i * 30, 150) ?>" data-lightbox-trigger data-src="<?= e($imgUrl) ?>" data-title="<?= e($title) ?>" data-city="<?= e($city) ?>">
                    <div class="gallery-item">
                        <img src="<?= e($imgUrl) ?>" alt="<?= e($title) ?>" loading="lazy">
                        <div class="gallery-overlay">
                            <span class="gallery-city"><i class="fas fa-map-marker-alt"></i> <?= e($city) ?></span>
                            <strong><?= e($title) ?></strong>
                            <span class="gallery-price"><i class="fas fa-up-right-and-down-left-from-center"></i> Click to view</span>
                        </div>
                    </div>
                    <!-- Lightbox Trigger Button -->
                    <button type="button" class="gallery-preview-btn d-flex align-items-center justify-content-center" title="Quick preview">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Upgraded Lightbox Modal with Slider Arrows & Filmstrip Thumbnails -->
<div class="modal fade" id="lightboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content gallery-lightbox-content border-0 position-relative">
            <!-- Close Button -->
            <button type="button" class="btn-close btn-close-white gallery-lightbox-close" data-bs-dismiss="modal" aria-label="Close"></button>

            <!-- Main Photo Wrapper -->
            <div class="gallery-lightbox-frame">
                <img src="" id="lightboxImg" class="gallery-lightbox-img" alt="Preview">

                <!-- Slide Controls -->
                <button type="button" id="lightboxPrev" class="gallery-lightbox-nav gallery-lightbox-prev" title="Previous Image" aria-label="Previous image">
                    <i class="fas fa-chevron-left text-white"></i>
                </button>
                <button type="button" id="lightboxNext" class="gallery-lightbox-nav gallery-lightbox-next" title="Next Image" aria-label="Next image">
                    <i class="fas fa-chevron-right text-white"></i>
                </button>
            </div>

            <!-- Description Overlay -->
            <div class="gallery-lightbox-meta">
                <h5 id="lightboxTitle" class="mb-1 fw-bold display-font"></h5>
                <span class="small text-teal-light text-uppercase fw-semibold" id="lightboxCity"><i class="fas fa-map-marker-alt me-1"></i></span>
            </div>

            <!-- Horizontal Filmstrip Thumbnails -->
            <div class="gallery-lightbox-filmstrip" id="lightboxFilmstrip">
                <!-- Dynamically populated in JS -->
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('lightboxModal');
    if (!modalEl || typeof bootstrap === 'undefined') return;
    var modal = new bootstrap.Modal(modalEl);


    // Featured gallery slider
    var slides = Array.prototype.slice.call(document.querySelectorAll('.gallery-slide'));
    var sliderThumbs = Array.prototype.slice.call(document.querySelectorAll('.gallery-slider-thumb'));
    var sliderIndex = 0;
    var sliderTimer = null;

    function setSlider(index) {
        if (!slides.length) return;
        sliderIndex = (index + slides.length) % slides.length;
        slides.forEach(function (slide, idx) {
            slide.classList.toggle('is-active', idx === sliderIndex);
        });
        sliderThumbs.forEach(function (thumb, idx) {
            thumb.classList.toggle('is-active', idx === sliderIndex);
        });
    }

    function startSlider() {
        if (slides.length < 2) return;
        window.clearInterval(sliderTimer);
        sliderTimer = window.setInterval(function () {
            setSlider(sliderIndex + 1);
        }, 4500);
    }

    var sliderPrev = document.getElementById('gallerySliderPrev');
    var sliderNext = document.getElementById('gallerySliderNext');
    if (sliderPrev) {
        sliderPrev.addEventListener('click', function () {
            setSlider(sliderIndex - 1);
            startSlider();
        });
    }
    if (sliderNext) {
        sliderNext.addEventListener('click', function () {
            setSlider(sliderIndex + 1);
            startSlider();
        });
    }
    sliderThumbs.forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            setSlider(parseInt(thumb.getAttribute('data-slide-index'), 10) || 0);
            startSlider();
        });
    });
    startSlider();

    // Lightbox Slider Logic
    var activeTriggers = [];
    var currentIndex = 0;
    var lightboxImg = document.getElementById('lightboxImg');

    function updateActiveTriggers() {
        activeTriggers = [];
        document.querySelectorAll('[data-lightbox-trigger]').forEach(function (trigger) {
            if (trigger.style.display !== 'none') {
                activeTriggers.push(trigger);
            }
        });

        // Rebuild filmstrip thumbnails
        var filmstrip = document.getElementById('lightboxFilmstrip');
        filmstrip.innerHTML = '';
        activeTriggers.forEach(function (trigger, idx) {
            var thumb = document.createElement('img');
            thumb.src = trigger.getAttribute('data-src');
            thumb.className = 'rounded border border-2 border-transparent cursor-pointer object-fit-cover';
            thumb.style.width = '60px';
            thumb.style.height = '45px';
            thumb.style.transition = 'all 0.2s ease';
            thumb.style.opacity = '0.5';

            thumb.addEventListener('click', function () {
                showImage(idx);
            });

            filmstrip.appendChild(thumb);
        });
    }

    function showImage(index) {
        if (index < 0 || index >= activeTriggers.length) return;
        currentIndex = index;
        var trigger = activeTriggers[index];

        // Smooth Fade Transition
        lightboxImg.style.opacity = '0';
        setTimeout(function() {
            lightboxImg.src = trigger.getAttribute('data-src');
            document.getElementById('lightboxTitle').textContent = trigger.getAttribute('data-title') || '';
            document.getElementById('lightboxCity').innerHTML = '<i class="fas fa-map-marker-alt me-1"></i>' + (trigger.getAttribute('data-city') || '');
            lightboxImg.style.opacity = '1';

            // Highlight active filmstrip thumbnail
            var thumbs = document.querySelectorAll('#lightboxFilmstrip img');
            thumbs.forEach(function (t, idx) {
                if (idx === index) {
                    t.style.opacity = '1';
                    t.style.borderColor = '#2dd4bf'; // Active teal color
                    t.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                } else {
                    t.style.opacity = '0.5';
                    t.style.borderColor = 'transparent';
                }
            });
        }, 150);
    }

    // Initial triggers list setup
    updateActiveTriggers();

    // Register click handlers on trigger buttons
    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[data-lightbox-trigger]');
        if (trigger) {
            e.preventDefault();
            e.stopPropagation();

            updateActiveTriggers();

            var idx = activeTriggers.indexOf(trigger);
            if (idx !== -1) {
                showImage(idx);
                modal.show();
            }
        }
    });

    // Previous & Next bindings
    document.getElementById('lightboxPrev').addEventListener('click', function () {
        var nextIdx = currentIndex - 1;
        if (nextIdx < 0) nextIdx = activeTriggers.length - 1;
        showImage(nextIdx);
    });

    document.getElementById('lightboxNext').addEventListener('click', function () {
        var nextIdx = currentIndex + 1;
        if (nextIdx >= activeTriggers.length) nextIdx = 0;
        showImage(nextIdx);
    });

    // Keyboard navigation
    document.addEventListener('keydown', function (e) {
        if (!modalEl.classList.contains('show')) return;
        if (e.key === 'ArrowLeft') {
            document.getElementById('lightboxPrev').click();
        } else if (e.key === 'ArrowRight') {
            document.getElementById('lightboxNext').click();
        }
    });
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
