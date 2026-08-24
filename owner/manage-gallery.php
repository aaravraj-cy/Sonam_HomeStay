<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('owner');
$ownerId = get_owner_id();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    
    // 1. UPLOAD MULTIPLE IMAGES
    if ($action === 'upload') {
        if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
            $error = 'Please select at least one image to upload.';
        } else {
            $title = trim($_POST['title'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $uploadedCount = 0;
            
            $files = $_FILES['images'];
            $fileCount = count($files['name']);
            
            // Get current maximum sort order for the owner to append
            $maxSortQuery = $conn->prepare("SELECT MAX(sort_order) FROM gallery_images WHERE owner_id = ?");
            $maxSortQuery->execute([$ownerId]);
            $currentMaxSort = (int)$maxSortQuery->fetchColumn();

            for ($i = 0; $i < $fileCount; $i++) {
                $singleFile = [
                    'name'     => $files['name'][$i],
                    'type'     => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error'    => $files['error'][$i],
                    'size'     => $files['size'][$i]
                ];
                
                if ($singleFile['error'] === UPLOAD_ERR_OK) {
                    // Upload to general gallery uploads folder
                    $up = upload_image($singleFile, UPLOAD_GALLERY);
                    if ($up) {
                        $currentMaxSort++;
                        // Insert new gallery image
                        $conn->prepare("INSERT INTO gallery_images (owner_id, image_path, title, city, sort_order) VALUES (?, ?, ?, ?, ?)")
                             ->execute([$ownerId, $up, $title ?: null, $city ?: null, $currentMaxSort]);
                        $uploadedCount++;
                    }
                }
            }
            
            if ($uploadedCount > 0) {
                set_flash('success', "Successfully uploaded $uploadedCount image(s) to your gallery.");
            } else {
                set_flash('error', 'Failed to upload any images. Ensure files are valid images and under 5MB.');
            }
            redirect(BASE_URL . "owner/manage-gallery.php");
        }
    }
    
    // 2. DELETE IMAGE
    if ($action === 'delete') {
        $imageId = (int)($_POST['image_id'] ?? 0);
        
        // Verify target image ownership
        $imgCheck = $conn->prepare("SELECT * FROM gallery_images WHERE id = ? AND owner_id = ?");
        $imgCheck->execute([$imageId, $ownerId]);
        $targetImage = $imgCheck->fetch();
        
        if ($targetImage) {
            // Delete actual file
            $filePath = UPLOAD_GALLERY . $targetImage['image_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Delete record
            $conn->prepare("DELETE FROM gallery_images WHERE id = ?")->execute([$imageId]);
            set_flash('success', 'Gallery image deleted successfully.');
        } else {
            set_flash('error', 'Image not found or permission denied.');
        }
        redirect(BASE_URL . "owner/manage-gallery.php");
    }

    // 3. SORTING / REARRANGING (Move Left/Right)
    if ($action === 'move_left' || $action === 'move_right') {
        $imageId = (int)($_POST['image_id'] ?? 0);
        
        // Fetch all gallery images for this owner
        $imgsQuery = $conn->prepare("SELECT id, sort_order FROM gallery_images WHERE owner_id = ? ORDER BY sort_order ASC, id ASC");
        $imgsQuery->execute([$ownerId]);
        $imgs = $imgsQuery->fetchAll();
        
        $targetIndex = -1;
        foreach ($imgs as $index => $img) {
            if ((int)$img['id'] === $imageId) {
                $targetIndex = $index;
                break;
            }
        }
        
        if ($targetIndex !== -1) {
            $swapIndex = -1;
            if ($action === 'move_left' && $targetIndex > 0) {
                $swapIndex = $targetIndex - 1;
            } elseif ($action === 'move_right' && $targetIndex < count($imgs) - 1) {
                $swapIndex = $targetIndex + 1;
            }
            
            if ($swapIndex !== -1) {
                // Swap sort_order values in DB
                $tId = $imgs[$targetIndex]['id'];
                $tOrder = $imgs[$targetIndex]['sort_order'];
                $sId = $imgs[$swapIndex]['id'];
                $sOrder = $imgs[$swapIndex]['sort_order'];
                
                if ($tOrder == $sOrder) {
                    $tOrder = $targetIndex;
                    $sOrder = $swapIndex;
                }
                
                $conn->prepare("UPDATE gallery_images SET sort_order = ? WHERE id = ?")->execute([$sOrder, $tId]);
                $conn->prepare("UPDATE gallery_images SET sort_order = ? WHERE id = ?")->execute([$tOrder, $sId]);
                
                set_flash('success', 'Image arrangement updated.');
            }
        }
        redirect(BASE_URL . "owner/manage-gallery.php");
    }
}

// Fetch all gallery images owned by this host
$galleryImages = [];
$galleryStmt = $conn->prepare("SELECT * FROM gallery_images WHERE owner_id = ? ORDER BY sort_order ASC, id ASC");
$galleryStmt->execute([$ownerId]);
$galleryImages = $galleryStmt->fetchAll();
if (empty($galleryImages)) {
    $galleryImages = fallback_gallery_items(10);
}

$pageTitle = 'Manage Photo Gallery';
$sidebarRole = 'owner';
$sidebarActive = 'gallery';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="dashboard-main animate__animated animate__fadeIn">
        
        <!-- Header Bar -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 pb-3 border-bottom">
            <div>
                <h1 class="h3 fw-bold text-dark m-0">Direct Gallery Manager</h1>
                <p class="small text-muted mb-0">Upload and arrange your custom gallery photos directly, separate from properties.</p>
            </div>
        </div>

        <div class="row g-4">
            <!-- Upload photos form -->
            <div class="col-lg-4">
                <div class="dash-card border p-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="fas fa-cloud-upload-alt text-teal me-2"></i>Upload Photos</h5>
                    <form method="POST" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="upload">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-1">Select Images</label>
                            <input type="file" name="images[]" class="form-control" accept="image/*" multiple required>
                            <span class="d-block small text-muted mt-2">You can select multiple photos at once. Supported extensions: JPEG, PNG, WEBP. Max size: 5MB per file.</span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-1">Title / Caption (Optional)</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Scenic mountain sunrise">
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-1">City / Location (Optional)</label>
                            <input type="text" name="city" class="form-control" placeholder="e.g. Khechuperi">
                        </div>
                        
                        <button class="btn btn-teal w-100 py-2.5 fw-bold"><i class="fas fa-upload me-2"></i>Upload to Gallery</button>
                    </form>
                </div>
            </div>

            <!-- Gallery view -->
            <div class="col-lg-8">
                <div class="dash-card border p-4">
                    <h5 class="fw-bold text-dark mb-3">
                        <i class="far fa-images text-teal me-2"></i>
                        Uploaded Gallery Images (<?= count($galleryImages) ?>)
                    </h5>
                    
                    <?php if (empty($galleryImages)): ?>
                        <div class="text-center py-5 border rounded-3 bg-light bg-opacity-25">
                            <i class="far fa-image text-muted fs-2 mb-3"></i>
                            <p class="text-muted small mb-0">No photos uploaded to your gallery yet. Upload photos using the form on the left.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($galleryImages as $index => $img): 
                                $isFallbackImage = !empty($img['is_fallback']);
                                $imgUrl = $isFallbackImage ? $img['image_url'] : BASE_URL . 'assets/uploads/gallery/' . $img['image_path'];
                            ?>
                            <div class="col-md-6 col-xl-4">
                                <div class="card h-100 border overflow-hidden position-relative hover-shadow transition">
                                    <!-- Photo Thumbnail -->
                                    <img src="<?= e($imgUrl) ?>" class="card-img-top object-fit-cover" style="height: 160px;" alt="Gallery">
                                    
                                    <!-- Image Metadata Badge -->
                                    <div class="position-absolute top-0 start-0 m-2">
                                        <span class="badge bg-dark bg-opacity-70 text-white shadow-sm">img<?= $index + 1 ?></span>
                                    </div>
                                    
                                    <!-- Body with Title/Location -->
                                    <div class="card-body p-2 border-top">
                                        <h6 class="fw-bold text-dark text-truncate mb-0.5 mt-0" style="font-size: 0.85rem;"><?= e($img['title'] ?: 'Untitled Image') ?></h6>
                                        <span class="text-muted small d-block"><i class="fas fa-map-marker-alt text-teal me-1"></i><?= e($img['city'] ?: 'Sikkim') ?></span>
                                    </div>

                                    <!-- Arrange Actions & Details -->
                                    <div class="card-footer p-2 bg-light border-top d-flex justify-content-between align-items-center">
                                        <?php if ($isFallbackImage): ?>
                                            <span class="badge bg-teal-light text-teal-deep border border-teal border-opacity-25">Public site image</span>
                                            <a href="<?= e($imgUrl) ?>" target="_blank" class="btn btn-sm btn-outline-teal px-2" title="Open image">
                                                <i class="fas fa-up-right-from-square"></i>
                                            </a>
                                        <?php else: ?>
                                        <!-- Rearranging Arrows -->
                                        <div class="d-flex gap-1">
                                            <!-- Move Left -->
                                            <form method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="move_left">
                                                <input type="hidden" name="image_id" value="<?= (int)$img['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary px-2" title="Move Left/Up" <?= $index === 0 ? 'disabled' : '' ?>>
                                                    <i class="fas fa-arrow-left"></i>
                                                </button>
                                            </form>
                                            
                                            <!-- Move Right -->
                                            <form method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="move_right">
                                                <input type="hidden" name="image_id" value="<?= (int)$img['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary px-2" title="Move Right/Down" <?= $index === count($galleryImages) - 1 ? 'disabled' : '' ?>>
                                                    <i class="fas fa-arrow-right"></i>
                                                </button>
                                            </form>
                                        </div>

                                        <!-- Delete Button -->
                                        <form method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="image_id" value="<?= (int)$img['id'] ?>">
                                            <button type="button" class="btn btn-sm btn-outline-danger px-2 btn-confirm" data-confirm="Are you sure you want to delete this photo from the gallery?" title="Delete image">
                                                <i class="far fa-trash-can"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
