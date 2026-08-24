<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('owner');
$ownerId = get_owner_id();
$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare('SELECT * FROM homestays WHERE id = ? AND owner_id = ?');
$stmt->execute([$id, $ownerId]);
$h = $stmt->fetch();
if (!$h) {
    set_flash('error', 'Not found.');
    redirect(BASE_URL . 'owner/manage-homestays.php');
}

$amenities = $conn->query('SELECT * FROM amenities ORDER BY name')->fetchAll();
$sel = $conn->prepare('SELECT amenity_id FROM homestay_amenities WHERE homestay_id = ?');
$sel->execute([$id]);
$selectedIds = array_column($sel->fetchAll(), 'amenity_id');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $type = trim($_POST['property_type'] ?? 'Homestay');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $rules = trim($_POST['house_rules'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $amenityIds = array_map('intval', $_POST['amenities'] ?? []);
    $cover = $h['cover_image'];

    if (!empty($_FILES['cover_image']['name'])) {
        $up = upload_image($_FILES['cover_image'], UPLOAD_HOMESTAYS);
        if ($up) $cover = $up;
    }

    if (strlen($title) < 5) {
        $error = 'Title too short.';
    } else {
        $conn->prepare('UPDATE homestays SET title=?, description=?, property_type=?, address=?, city=?, state=?, pincode=?, cover_image=?, house_rules=?, is_active=? WHERE id=? AND owner_id=?')
            ->execute([$title, $desc, $type, $address, $city, $state, $pincode ?: null, $cover, $rules ?: null, $isActive, $id, $ownerId]);
        $conn->prepare('DELETE FROM homestay_amenities WHERE homestay_id=?')->execute([$id]);
        $ins = $conn->prepare('INSERT INTO homestay_amenities (homestay_id, amenity_id) VALUES (?,?)');
        foreach ($amenityIds as $aid) $ins->execute([$id, $aid]);
        set_flash('success', 'Updated.');
        redirect(BASE_URL . 'owner/edit-homestay.php?id=' . $id);
    }
}

$pageTitle = 'Edit Homestay';
$sidebarRole = 'owner';
$sidebarActive = 'homestays';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="dashboard-main">
        <h1 class="h3 mb-3">Edit: <?= e($h['title']) ?></h1>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="dash-card">
            <?= csrf_field() ?>
            <div class="mb-3"><label class="form-label">Title</label><input name="title" class="form-control" value="<?= e($h['title']) ?>" required></div>
            <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="4" required><?= e($h['description']) ?></textarea></div>
            <div class="mb-3"><label class="form-label">Type</label>
                <select name="property_type" class="form-select">
                    <?php foreach (['Homestay','Cottage','Villa','Apartment'] as $t): ?>
                    <option <?= $h['property_type']===$t?'selected':'' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3"><label class="form-label">Address</label><input name="address" class="form-control" value="<?= e($h['address']) ?>" required></div>
            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <label class="form-label small text-muted">City</label>
                    <input name="city" class="form-control bg-light" value="Khechuperi" readonly required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted">State / Region</label>
                    <input name="state" class="form-control bg-light" value="West Sikkim" readonly required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted">Pincode</label>
                    <input name="pincode" class="form-control" value="<?= e($h['pincode'] ?? '') ?>">
                </div>
            </div>
            <div class="mb-3"><label class="form-label">New cover image</label><input type="file" name="cover_image" class="form-control" accept="image/*"></div>
            <div class="mb-3"><label class="form-label">House rules</label><textarea name="house_rules" class="form-control" rows="2"><?= e($h['house_rules'] ?? '') ?></textarea></div>
            <div class="mb-3">
                <label class="form-label">Amenities</label>
                <div class="row">
                    <?php foreach ($amenities as $a): ?>
                    <div class="col-md-4"><div class="form-check">
                        <input class="form-check-input" type="checkbox" name="amenities[]" value="<?= (int)$a['id'] ?>" <?= in_array($a['id'], $selectedIds) ? 'checked' : '' ?>>
                        <label class="form-check-label"><?= e($a['name']) ?></label>
                    </div></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_active" id="act" <?= $h['is_active']?'checked':'' ?>><label for="act" class="form-check-label">Active</label></div>
            <button class="btn btn-primary">Save</button>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
