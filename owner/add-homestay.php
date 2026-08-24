<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('owner');
require_verification();
$ownerId = get_owner_id();
$amenities = $conn->query('SELECT * FROM amenities ORDER BY name')->fetchAll();
$error = '';
$title = $desc = $address = $city = $state = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $type = trim($_POST['property_type'] ?? 'Homestay');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? 'Khechuperi');
    $state = trim($_POST['state'] ?? 'West Sikkim');
    $pincode = trim($_POST['pincode'] ?? '737113');
    $rules = trim($_POST['house_rules'] ?? '');
    $selected = array_map('intval', $_POST['amenities'] ?? []);

    if (strlen($title) < 5 || strlen($desc) < 20) {
        $error = 'Title must be at least 5 chars and description at least 20 chars.';
    } elseif ($city == '' || $state == '' || $address == '') {
        $error = 'Address, city and state are required fields.';
    } else {
        $cover = null;
        if (!empty($_FILES['cover_image']['name'])) {
            $cover = upload_image($_FILES['cover_image'], UPLOAD_HOMESTAYS);
        }
        $slug = make_slug($title);
        $chk = $conn->prepare('SELECT id FROM homestays WHERE slug = ?');
        $chk->execute([$slug]);
        if ($chk->fetch()) $slug .= '-' . time();

        $stmt = $conn->prepare('INSERT INTO homestays (owner_id, title, slug, description, property_type, address, city, state, pincode, cover_image, house_rules) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$ownerId, $title, $slug, $desc, $type, $address, $city, $state, $pincode ?: null, $cover, $rules ?: null]);
        $hid = $conn->lastInsertId();

        if ($cover) {
            $conn->prepare('INSERT INTO homestay_images (homestay_id, image_path, is_cover) VALUES (?,?,1)')->execute([$hid, $cover]);
        }
        $ins = $conn->prepare('INSERT INTO homestay_amenities (homestay_id, amenity_id) VALUES (?,?)');
        foreach ($selected as $aid) {
            $ins->execute([$hid, $aid]);
        }

        set_flash('success', 'Homestay added successfully! Now set up room inventories.');
        redirect(BASE_URL . 'owner/manage-rooms.php?homestay_id=' . $hid);
    }
}

$pageTitle = 'Add Homestay';
$sidebarRole = 'owner';
$sidebarActive = 'add';
require __DIR__ . '/../includes/header.php';
?>
<style>
/* ==========================================================================
   Wizard Stepper UI/UX Overrides
   ========================================================================== */
.stepper-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    margin-bottom: 2.5rem;
}
.stepper-line {
    position: absolute;
    top: 16px;
    left: 0;
    width: 100%;
    height: 3px;
    background: #e2e8f0;
    z-index: 1;
}
.stepper-line-fill {
    position: absolute;
    top: 16px;
    left: 0;
    width: 0%;
    height: 3px;
    background: var(--sn-teal);
    z-index: 2;
    transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.step-header-item {
    position: relative;
    z-index: 3;
    background: var(--sn-bg-card, #fff);
    padding: 0 10px;
    display: flex;
    flex-direction: column;
    align-items: center;
    transition: color 0.3s ease;
}
.step-num {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-weight: bold;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-size: 0.85rem;
}
.step-num.active {
    background: var(--sn-teal) !important;
    color: #fff !important;
    border: 1px solid var(--sn-teal) !important;
    box-shadow: 0 4px 10px rgba(13, 148, 136, 0.25);
}
.step-num.inactive {
    background: #fff;
    color: #94a3b8;
    border: 1px solid #cbd5e1;
}
.step-header-item.active {
    color: var(--sn-teal);
}
.step-header-item.inactive {
    color: #94a3b8;
}

/* Animations */
.step-section {
    animation: slideIn 0.35s ease-out;
}
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Amenity Outline Highlight Box */
.option-box {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 10px;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
}
.option-box:hover {
    border-color: var(--sn-teal);
    background: rgba(13, 148, 136, 0.02) !important;
}
.option-box.selected {
    border-color: var(--sn-teal) !important;
    background: rgba(13, 148, 136, 0.05) !important;
    box-shadow: 0 0 0 1px var(--sn-teal);
}
</style>

<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="dashboard-main animate__animated animate__fadeIn">
        <h1 class="h3 fw-bold text-dark mb-4">List New Homestay</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 small py-2 px-3 rounded-3 mb-4 animate__animated animate__shakeX">
                <i class="fas fa-circle-exclamation"></i>
                <div><?= e($error) ?></div>
            </div>
        <?php endif; ?>

        <div class="dash-card border p-4 mb-4" style="max-width: 800px">
            
            <!-- Dynamic Wizard Stepper Header -->
            <div class="stepper-container mb-4" style="max-width: 550px; margin: 0 auto 2.5rem auto;">
                <!-- <div class="stepper-line">
                    <div class="stepper-line-fill" id="stepperLineFill"></div>
                </div> -->
                <div class="step-header-item active" id="stepHeader1">
                    <span class="step-num active">1</span>
                    <span class="small mt-1.5 fw-bold">Basic Info</span>
                </div>
                <div class="step-header-item inactive" id="stepHeader2">
                    <span class="step-num inactive">2</span>
                    <span class="small mt-1.5 fw-bold">Location</span>
                </div>
                <div class="step-header-item inactive" id="stepHeader3">
                    <span class="step-num inactive">3</span>
                    <span class="small mt-1.5 fw-bold">Amenities & Rules</span>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data" id="addHomestayForm">
                <?= csrf_field() ?>

                <!-- STEP 1: BASIC INFO -->
                <div class="step-section" id="step1">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1"><i class="fas fa-hotel text-teal me-2"></i>Homestay Title</label>
                        <input name="title" id="titleInput" class="form-control" placeholder="e.g. Cherry Blossom Homestay" value="<?= e($title) ?>" required autocomplete="off">
                        <span class="small text-muted" style="font-size: 0.72rem">Minimum 5 characters.</span>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1"><i class="fas fa-house-chimney text-teal me-2"></i>Property Type</label>
                        <input type="text" class="form-control bg-light text-muted fw-semibold" value="Homestay" readonly>
                        <input type="hidden" name="property_type" value="Homestay">
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1"><i class="fas fa-align-left text-teal me-2"></i>Description</label>
                        <textarea name="description" id="descInput" class="form-control" rows="5" placeholder="Describe the stay, views, and details of local food..." required><?= e($desc) ?></textarea>
                        <span class="small text-muted" style="font-size: 0.72rem">Minimum 20 characters.</span>
                    </div>

                    <div class="text-end border-top pt-3">
                        <button type="button" class="btn btn-teal px-4" id="btnNext1">Next Step <i class="fas fa-chevron-right ms-1"></i></button>
                    </div>
                </div>

                <!-- STEP 2: LOCATION -->
                <div class="step-section d-none" id="step2">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1"><i class="fas fa-location-dot text-teal me-2"></i>Street Address</label>
                        <input name="address" id="addressInput" class="form-control" placeholder="e.g. Near Wishing Lake Trail" value="<?= e($address) ?>" required autocomplete="off">
                    </div>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-1">City / Village</label>
                            <input name="city" class="form-control bg-light text-muted" value="Khechuperi" readonly required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-1">State / Region</label>
                            <input name="state" class="form-control bg-light text-muted" value="West Sikkim" readonly required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-1">Pincode</label>
                            <input name="pincode" class="form-control text-center font-monospace" placeholder="737113" value="737113">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between border-top pt-3">
                        <button type="button" class="btn btn-light border px-4" id="btnPrev2"><i class="fas fa-chevron-left me-1"></i> Previous</button>
                        <button type="button" class="btn btn-teal px-4" id="btnNext2">Next Step <i class="fas fa-chevron-right ms-1"></i></button>
                    </div>
                </div>

                <!-- STEP 3: AMENITIES & PHOTOS -->
                <div class="step-section d-none" id="step3">
                    <!-- Photo upload with preview -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1"><i class="far fa-image text-teal me-2"></i>Cover Image</label>
                        <div class="d-flex align-items-center gap-3 border rounded-3 p-3 bg-light bg-opacity-25 mb-2">
                            <div class="bg-light rounded-3 d-flex align-items-center justify-content-center overflow-hidden border" style="width: 80px; height: 60px; min-width: 80px">
                                <i class="far fa-image fs-3 text-muted" id="previewPlaceholder"></i>
                                <img src="" class="w-100 h-100 object-fit-cover d-none" id="coverPreview" alt="Preview">
                            </div>
                            <div class="w-100">
                                <input type="file" name="cover_image" id="coverInput" class="form-control form-control-sm" accept="image/*" required>
                                <span class="small text-muted" style="font-size: 0.7rem">Upload a high-quality landscape photo.</span>
                            </div>
                        </div>
                    </div>

                    <!-- House Rules -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1"><i class="fas fa-circle-info text-teal me-2"></i>House Rules</label>
                        <textarea name="house_rules" class="form-control" rows="2" placeholder="e.g. No shoes inside, quiet hours after 10 PM..."></textarea>
                    </div>

                    <!-- Amenities Checklist -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2"><i class="fas fa-square-check text-teal me-2"></i>Select Amenities</label>
                        <div class="row g-2">
                            <?php foreach ($amenities as $a): ?>
                            <div class="col-6 col-sm-4">
                                <div class="border rounded-3 p-2.5 bg-light bg-opacity-20 d-flex align-items-center gap-2 option-box transition">
                                    <input class="form-check-input mt-0 amenity-check" type="checkbox" name="amenities[]" value="<?= (int)$a['id'] ?>" id="a<?= (int)$a['id'] ?>">
                                    <label class="form-check-label small text-dark cursor-pointer m-0 w-100" for="a<?= (int)$a['id'] ?>"><?= e($a['name']) ?></label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between border-top pt-3">
                        <button type="button" class="btn btn-light border px-4" id="btnPrev3"><i class="fas fa-chevron-left me-1"></i> Previous</button>
                        <button type="submit" class="btn btn-teal px-4 fw-bold"><i class="fas fa-save me-1"></i> Save & Add Rooms</button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const s1 = document.getElementById("step1");
    const s2 = document.getElementById("step2");
    const s3 = document.getElementById("step3");

    const h1 = document.getElementById("stepHeader1");
    const h2 = document.getElementById("stepHeader2");
    const h3 = document.getElementById("stepHeader3");
    
    const fillLine = document.getElementById("stepperLineFill");

    function setStep(step){
        // Reset all headers
        [h1,h2,h3].forEach(function(header){
            header.classList.remove("active");
            header.classList.add("inactive");

            const num = header.querySelector(".step-num");
            num.classList.remove("active");
            num.classList.add("inactive");
        });

        // Activate current step
        const current = document.getElementById("stepHeader" + step);
        current.classList.remove("inactive");
        current.classList.add("active");

        const currentNum = current.querySelector(".step-num");
        currentNum.classList.remove("inactive");
        currentNum.classList.add("active");
        
        // Update filled progress line width
        if (fillLine) {
            if (step === 1) fillLine.style.width = "0%";
            if (step === 2) fillLine.style.width = "50%";
            if (step === 3) fillLine.style.width = "100%";
        }
    }

    // STEP 1 → STEP 2
    document.getElementById("btnNext1").onclick = function(){
        const title = document.getElementById("titleInput").value.trim();
        const desc = document.getElementById("descInput").value.trim();

        if (title.length < 5){
            Swal.fire({
                title: 'Validation Error',
                text: 'Title must be at least 5 characters.',
                icon: 'error',
                confirmButtonColor: '#0f766e',
                customClass: { popup: 'rounded-4 shadow-sm' }
            });
            return;
        }

        if (desc.length < 20){
            Swal.fire({
                title: 'Validation Error',
                text: 'Description must be at least 20 characters.',
                icon: 'error',
                confirmButtonColor: '#0f766e',
                customClass: { popup: 'rounded-4 shadow-sm' }
            });
            return;
        }

        s1.classList.add("d-none");
        s2.classList.remove("d-none");
        setStep(2);
    };

    // STEP 2 → STEP 3
    document.getElementById("btnNext2").onclick = function(){
        const address = document.getElementById("addressInput").value.trim();

        if (address === ""){
            Swal.fire({
                title: 'Validation Error',
                text: 'Street Address is required.',
                icon: 'error',
                confirmButtonColor: '#0f766e',
                customClass: { popup: 'rounded-4 shadow-sm' }
            });
            return;
        }

        s2.classList.add("d-none");
        s3.classList.remove("d-none");
        setStep(3);
    };

    // STEP 2 ← STEP 1
    document.getElementById("btnPrev2").onclick = function(){
        s2.classList.add("d-none");
        s1.classList.remove("d-none");
        setStep(1);
    };

    // STEP 3 ← STEP 2
    document.getElementById("btnPrev3").onclick = function(){
        s3.classList.add("d-none");
        s2.classList.remove("d-none");
        setStep(2);
    };

    // Form final submit validation
    document.getElementById("addHomestayForm").addEventListener("submit", function(e) {
        const coverInput = document.getElementById("coverInput");
        if (coverInput && coverInput.files.length === 0) {
            e.preventDefault();
            Swal.fire({
                title: 'Required Cover Photo',
                text: 'Please select a cover image for the homestay.',
                icon: 'error',
                confirmButtonColor: '#0f766e',
                customClass: { popup: 'rounded-4 shadow-sm' }
            });
            return false;
        }
    });

    // Cover photo live previewer
    const coverInput = document.getElementById('coverInput');
    const coverPreview = document.getElementById('coverPreview');
    const placeholder = document.getElementById('previewPlaceholder');
    
    if (coverInput && coverPreview) {
        coverInput.addEventListener('change', function() {
            var file = this.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    coverPreview.src = e.target.result;
                    coverPreview.classList.remove('d-none');
                    placeholder.classList.add('d-none');
                }
                reader.readAsDataURL(file);
            }
        });
    }

    // Amenities box outline dynamic highlight toggle
    document.querySelectorAll('.amenity-check').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            var box = this.closest('.option-box');
            if (this.checked) {
                box.classList.add('selected');
            } else {
                box.classList.remove('selected');
            }
        });
    });
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
