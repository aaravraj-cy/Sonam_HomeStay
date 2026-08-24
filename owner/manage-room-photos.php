<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('owner');
$ownerId = get_owner_id();

$roomId = (int)($_GET['room_id'] ?? 0);

// Validate room details and ownership
$roomQuery = $conn->prepare("
    SELECT r.*, h.title AS homestay_title, h.owner_id 
    FROM rooms r 
    JOIN homestays h ON h.id = r.homestay_id 
    WHERE r.id = ? AND h.owner_id = ?
");
$roomQuery->execute([$roomId, $ownerId]);
$room = $roomQuery->fetch();

if (!$room) {
    set_flash('error', 'Room not found or permission denied.');
    redirect(BASE_URL . 'owner/manage-rooms.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    
    // 1. UPLOAD IMAGES
    if ($action === 'upload') {
        if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
            $error = 'Please select at least one photo to upload.';
        } else {
            $uploadedCount = 0;
            $files = $_FILES['images'];
            $fileCount = count($files['name']);
            
            // Get current max sort_order
            $maxSortQuery = $conn->prepare("SELECT MAX(sort_order) FROM room_images WHERE room_id = ?");
            $maxSortQuery->execute([$roomId]);
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
                    $up = upload_image($singleFile, UPLOAD_ROOMS);
                    if ($up) {
                        $currentMaxSort++;
                        $conn->prepare("INSERT INTO room_images (room_id, image_path, sort_order) VALUES (?, ?, ?)")
                             ->execute([$roomId, $up, $currentMaxSort]);
                        
                        // If room has no cover image, set the first uploaded one as cover
                        if (empty($room['cover_image']) && $uploadedCount === 0) {
                            $conn->prepare("UPDATE rooms SET cover_image = ? WHERE id = ?")->execute([$up, $roomId]);
                            $room['cover_image'] = $up; // Update local state
                        }
                        $uploadedCount++;
                    }
                }
            }
            
            if ($uploadedCount > 0) {
                set_flash('success', "Successfully uploaded $uploadedCount room photo(s).");
            } else {
                set_flash('error', 'Failed to upload room photos. Validate files and sizes.');
            }
            redirect(BASE_URL . "owner/manage-room-photos.php?room_id=" . $roomId);
        }
    }
    
    // 2. MAKE COVER
    if ($action === 'make_cover') {
        $imageId = (int)($_POST['image_id'] ?? 0);
        
        // Verify image ownership
        $imgCheck = $conn->prepare("
            SELECT ri.* FROM room_images ri 
            JOIN rooms r ON r.id = ri.room_id 
            JOIN homestays h ON h.id = r.homestay_id 
            WHERE ri.id = ? AND h.owner_id = ?
        ");
        $imgCheck->execute([$imageId, $ownerId]);
        $targetImage = $imgCheck->fetch();
        
        if ($targetImage) {
            $conn->prepare("UPDATE rooms SET cover_image = ? WHERE id = ?")->execute([$targetImage['image_path'], $roomId]);
            set_flash('success', 'Room cover photo updated.');
        } else {
            set_flash('error', 'Image not found.');
        }
        redirect(BASE_URL . "owner/manage-room-photos.php?room_id=" . $roomId);
    }
    
    // 3. DELETE IMAGE
    if ($action === 'delete') {
        $imageId = (int)($_POST['image_id'] ?? 0);
        
        // Verify image ownership
        $imgCheck = $conn->prepare("
            SELECT ri.* FROM room_images ri 
            JOIN rooms r ON r.id = ri.room_id 
            JOIN homestays h ON h.id = r.homestay_id 
            WHERE ri.id = ? AND h.owner_id = ?
        ");
        $imgCheck->execute([$imageId, $ownerId]);
        $targetImage = $imgCheck->fetch();
        
        if ($targetImage) {
            // Delete file
            $filePath = UPLOAD_ROOMS . $targetImage['image_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Delete record
            $conn->prepare("DELETE FROM room_images WHERE id = ?")->execute([$imageId]);
            
            // If deleted image was the cover, update room's cover image
            if ($room['cover_image'] === $targetImage['image_path']) {
                $nextImgQuery = $conn->prepare("SELECT image_path FROM room_images WHERE room_id = ? ORDER BY sort_order LIMIT 1");
                $nextImgQuery->execute([$roomId]);
                $nextImg = $nextImgQuery->fetchColumn();
                
                $conn->prepare("UPDATE rooms SET cover_image = ? WHERE id = ?")->execute([$nextImg ?: null, $roomId]);
            }
            
            set_flash('success', 'Room photo deleted.');
        } else {
            set_flash('error', 'Image not found.');
        }
        redirect(BASE_URL . "owner/manage-room-photos.php?room_id=" . $roomId);
    }
    
    // 4. SORTING / REARRANGING
    if ($action === 'move_left' || $action === 'move_right') {
        $imageId = (int)($_POST['image_id'] ?? 0);
        
        // Fetch all room images sorted
        $imgsQuery = $conn->prepare("SELECT id, sort_order FROM room_images WHERE room_id = ? ORDER BY sort_order ASC, id ASC");
        $imgsQuery->execute([$roomId]);
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
                $tId = $imgs[$targetIndex]['id'];
                $tOrder = $imgs[$targetIndex]['sort_order'];
                $sId = $imgs[$swapIndex]['id'];
                $sOrder = $imgs[$swapIndex]['sort_order'];
                
                if ($tOrder == $sOrder) {
                    $tOrder = $targetIndex;
                    $sOrder = $swapIndex;
                }
                
                $conn->prepare("UPDATE room_images SET sort_order = ? WHERE id = ?")->execute([$sOrder, $tId]);
                $conn->prepare("UPDATE room_images SET sort_order = ? WHERE id = ?")->execute([$tOrder, $sId]);
                
                set_flash('success', 'Room photo arrangement updated.');
            }
        }
        redirect(BASE_URL . "owner/manage-room-photos.php?room_id=" . $roomId);
    }
}

// Fetch all room images
$roomImages = [];
$roomImagesStmt = $conn->prepare("SELECT * FROM room_images WHERE room_id = ? ORDER BY sort_order ASC, id ASC");
$roomImagesStmt->execute([$roomId]);
$roomImages = $roomImagesStmt->fetchAll();
if (empty($roomImages)) {
    $roomImages = fallback_gallery_items(10);
}

$pageTitle = 'Manage Room Photos';
$sidebarRole = 'owner';
$sidebarActive = 'rooms';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="dashboard-main animate__animated animate__fadeIn">
        
        <!-- Header Bar -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 pb-3 border-bottom">
            <div>
                <h1 class="h3 fw-bold text-dark m-0">Room Photos Manager</h1>
                <p class="small text-muted mb-0">Upload and arrange photos of individual rooms.</p>
            </div>
            <div>
                <a href="<?= BASE_URL ?>owner/manage-rooms.php?homestay_id=<?= (int)$room['homestay_id'] ?>" class="btn btn-light border btn-sm fw-bold">
                    <i class="fas fa-arrow-left me-2"></i> Back to Rooms
                </a>
            </div>
        </div>

        <!-- Room Meta Details Card -->
        <div class="card p-3 bg-light border-0 mb-4 rounded-3 d-flex flex-row align-items-center justify-content-between">
            <div>
                <span class="text-uppercase text-muted fw-bold font-monospace" style="font-size:0.7rem; letter-spacing:0.04em"><?= e($room['homestay_title']) ?></span>
                <h4 class="fw-bold text-dark mb-0 mt-0.5"><?= e($room['name']) ?></h4>
                <span class="small text-muted"><?= e($room['room_type']) ?> &middot; <?= money($room['price_per_night']) ?> / night</span>
            </div>
            <span class="badge bg-teal-light text-teal-deep px-3 py-2 rounded-pill fw-semibold"><i class="fas fa-bed me-1.5"></i>Room Photos</span>
        </div>

        <div class="row g-4">
            <!-- Upload Box -->
            <div class="col-lg-4">
                <div class="dash-card border p-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="fas fa-camera text-teal me-2"></i>Upload Photos</h5>
                    <form method="POST" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="upload">
                        
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-1">Select Images</label>
                            <input type="file" name="images[]" class="form-control" accept="image/*" multiple required>
                            <span class="d-block small text-muted mt-2">Upload one or multiple photos. Sequential naming (`img1`, `img2`, `img3`) is automatic based on layout arrangement.</span>
                        </div>
                        
                        <button class="btn btn-teal w-100 py-2.5 fw-bold"><i class="fas fa-upload me-2"></i>Upload Photos</button>
                    </form>
                </div>
            </div>

            <!-- Room Images Grid -->
            <div class="col-lg-8">
                <div class="dash-card border p-4">
                    <h5 class="fw-bold text-dark mb-3">
                        <i class="far fa-images text-teal me-2"></i>
                        Arranged Room Photos (<?= count($roomImages) ?>)
                    </h5>
                    
                    <?php if (empty($roomImages)): ?>
                        <div class="text-center py-5 border rounded-3 bg-light bg-opacity-25">
                            <i class="far fa-image text-muted fs-2 mb-3"></i>
                            <p class="text-muted small mb-0">No photos uploaded for this room yet. Add photos on the left.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($roomImages as $index => $img): 
                                $isFallbackImage = !empty($img['is_fallback']);
                                $isCover = (!$isFallbackImage && $room['cover_image'] === $img['image_path']);
                                $imgUrl = $isFallbackImage ? $img['image_url'] : BASE_URL . 'assets/uploads/rooms/' . $img['image_path'];
                            ?>
                            <div class="col-md-6 col-xl-4">
                                <div class="card h-100 border overflow-hidden position-relative hover-shadow transition">
                                    <!-- Photo Thumbnail -->
                                    <img src="<?= e($imgUrl) ?>" class="card-img-top object-fit-cover" style="height: 160px;" alt="Room Image">
                                    
                                    <!-- Sequence Label: img1, img2, img3... -->
                                    <div class="position-absolute top-0 start-0 m-2">
                                        <?php if ($isCover): ?>
                                            <span class="badge bg-teal text-white shadow-sm"><i class="fas fa-star me-1"></i>img<?= $index + 1 ?> (Cover)</span>
                                        <?php else: ?>
                                            <span class="badge bg-dark bg-opacity-70 text-white shadow-sm">img<?= $index + 1 ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Rearrange Actions -->
                                    <div class="card-body p-2 bg-light border-top d-flex justify-content-between align-items-center">
                                        <?php if ($isFallbackImage): ?>
                                            <span class="badge bg-teal-light text-teal-deep border border-teal border-opacity-25">Public site image</span>
                                            <a href="<?= e($imgUrl) ?>" target="_blank" class="btn btn-sm btn-outline-teal px-2" title="Open image">
                                                <i class="fas fa-up-right-from-square"></i>
                                            </a>
                                        <?php else: ?>
                                        <!-- Reorder Buttons -->
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
                                                <button type="submit" class="btn btn-sm btn-outline-secondary px-2" title="Move Right/Down" <?= $index === count($roomImages) - 1 ? 'disabled' : '' ?>>
                                                    <i class="fas fa-arrow-right"></i>
                                                </button>
                                            </form>
                                        </div>

                                        <div class="d-flex gap-1.5 align-items-center">
                                            <!-- Set Cover Option -->
                                            <?php if (!$isCover): ?>
                                            <form method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="make_cover">
                                                <input type="hidden" name="image_id" value="<?= (int)$img['id'] ?>">
                                                <button type="submit" class="btn btn-xs btn-outline-teal fw-bold" style="font-size:0.75rem" title="Set as room cover photo">
                                                    <i class="far fa-star"></i> Cover
                                                </button>
                                            </form>
                                            <?php endif; ?>

                                            <!-- Delete Button -->
                                            <form method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="image_id" value="<?= (int)$img['id'] ?>">
                                                <button type="button" class="btn btn-sm btn-outline-danger px-2 btn-confirm" data-confirm="Are you sure you want to delete this room photo?" title="Delete room photo">
                                                    <i class="far fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </div>
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
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
