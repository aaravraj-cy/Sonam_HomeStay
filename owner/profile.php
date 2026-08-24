<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('owner');

$uid = $_SESSION['user_id'];
$user = $conn->prepare('SELECT * FROM users WHERE id=?');
$user->execute([$uid]);
$user = $user->fetch();

$ownerId = get_owner_id();
$owner = $conn->prepare('SELECT * FROM owners WHERE id=?');
$owner->execute([$ownerId]);
$owner = $owner->fetch();
$success = '';
$error = '';

$activeTab = 'profile';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'profile') {
        $activeTab = 'profile';
        $name = input_string($_POST['full_name'] ?? '', 120);
        $phone = input_string($_POST['phone'] ?? '', 20);
        $business = input_string($_POST['business_name'] ?? '', 150);
        $bio = input_string($_POST['bio'] ?? '', 1000);
        $img = $user['profile_image'];
        
        if (!empty($_FILES['profile_image']['name'])) {
            $up = upload_image($_FILES['profile_image'], UPLOAD_PROFILES);
            if ($up) $img = $up;
        }
        
        if (!valid_phone($phone)) {
            $error = 'Enter a valid phone number.';
        } elseif (valid_name($name)) {
            $conn->prepare('UPDATE users SET full_name=?, phone=?, profile_image=? WHERE id=?')->execute([$name, $phone ?: null, $img, $uid]);
            $conn->prepare('UPDATE owners SET business_name=?, bio=? WHERE id=?')->execute([$business ?: null, $bio ?: null, $ownerId]);
            $_SESSION['full_name'] = $name;
            $_SESSION['profile_image'] = $img;
            $success = 'Host profile details updated successfully.';
            
            // Reload user records
            $user = $conn->prepare('SELECT * FROM users WHERE id=?');
            $user->execute([$uid]);
            $user = $user->fetch();
            
            $owner = $conn->prepare('SELECT * FROM owners WHERE id=?');
            $owner->execute([$ownerId]);
            $owner = $owner->fetch();
        } else {
            $error = 'Please enter your full name.';
        }
    } elseif ($action === 'password') {
        $activeTab = 'security';
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
            $error = 'All password fields are required.';
        } elseif ($newPass !== $confirmPass) {
            $error = 'New password and confirm password do not match.';
        } elseif (!valid_password($newPass)) {
            $error = 'New password must be at least 8 characters long.';
        } elseif (!password_verify($currentPass, $user['password'])) {
            $error = 'Incorrect current password.';
        } else {
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $conn->prepare('UPDATE users SET password=? WHERE id=?')->execute([$hashed, $uid]);
            $success = 'Your password has been changed successfully.';
        }
    }
}

$pageTitle = 'Host Settings';
$sidebarRole = 'owner';
$sidebarActive = 'profile';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="dashboard-main animate__animated animate__fadeIn">
        <h1 class="h3 fw-bold text-dark mb-4">Host Profile & Settings</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success d-flex align-items-center gap-2 small py-2.5 px-3 rounded-3 mb-4 animate__animated animate__fadeIn" style="max-width: 650px">
                <i class="fas fa-check-circle fs-6"></i>
                <div><?= e($success) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 small py-2.5 px-3 rounded-3 mb-4 animate__animated animate__shakeX" style="max-width: 650px">
                <i class="fas fa-triangle-exclamation"></i>
                <div><?= e($error) ?></div>
            </div>
        <?php endif; ?>

        <!-- Tabbed Navigation Header -->
        <div class="d-flex border-bottom gap-4 mb-4" style="max-width: 650px">
            <button class="btn btn-link nav-link pb-2.5 fw-bold px-0 border-bottom border-2 <?= $activeTab === 'profile' ? 'border-teal text-teal' : 'border-transparent text-muted' ?>" id="tabProfile" type="button">
                <i class="far fa-user me-2"></i> Profile Details
            </button>
            <button class="btn btn-link nav-link pb-2.5 fw-bold px-0 border-bottom border-2 <?= $activeTab === 'security' ? 'border-teal text-teal' : 'border-transparent text-muted' ?>" id="tabSecurity" type="button">
                <i class="fas fa-lock me-2"></i> Security
            </button>
        </div>

        <!-- PROFILE TAB -->
        <div class="tab-pane <?= $activeTab === 'profile' ? '' : 'd-none' ?>" id="paneProfile">
            <form method="POST" enctype="multipart/form-data" class="dash-card border p-4" style="max-width: 650px">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="profile">
                
                <!-- Profile Avatar Block -->
                <div class="d-flex align-items-center gap-4 mb-4 pb-3 border-bottom">
                    <div class="position-relative">
                        <img src="<?= profile_img($user['profile_image']) ?>" class="rounded-circle border border-3 border-teal shadow-sm" style="width: 90px; height: 90px; object-fit: cover;" id="avatarPreview" alt="Avatar">
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-1">Profile Photo</h6>
                        <p class="small text-muted mb-2">Accepted formats: JPG, PNG, GIF. Max size 2MB.</p>
                        <input type="file" name="profile_image" id="avatarInput" class="form-control form-control-sm" accept="image/*">
                    </div>
                </div>

                <!-- Full Name -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1"><i class="far fa-user text-teal me-2"></i>Full name</label>
                    <input name="full_name" class="form-control fw-semibold" value="<?= e($user['full_name']) ?>" required>
                </div>
                
                <!-- Email (Disabled) -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1"><i class="far fa-envelope text-teal me-2"></i>Email Address</label>
                    <input class="form-control text-muted" value="<?= e($user['email']) ?>" disabled>
                    <span class="small text-muted" style="font-size: 0.7rem">To change email, please contact platform administrators.</span>
                </div>
                
                <!-- Phone -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1"><i class="fas fa-phone text-teal me-2"></i>Phone number</label>
                    <input name="phone" class="form-control" placeholder="Enter contact phone" value="<?= e($user['phone'] ?? '') ?>">
                </div>
                
                <!-- Business Name -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1"><i class="fas fa-briefcase text-teal me-2"></i>Business / Homestay name</label>
                    <input name="business_name" class="form-control" placeholder="e.g. Sikkim Mountain Homestays" value="<?= e($owner['business_name'] ?? '') ?>">
                </div>
                
                <!-- Bio -->
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1"><i class="far fa-note-sticky text-teal me-2"></i>Host Bio</label>
                    <textarea name="bio" class="form-control" rows="3" placeholder="Tell guests about your background and local hosting style..."><?= e($owner['bio'] ?? '') ?></textarea>
                </div>
                
                <button class="btn btn-teal py-2 px-4 fw-bold"><i class="fas fa-save me-2"></i>Save Details</button>
            </form>
        </div>

        <!-- SECURITY TAB -->
        <div class="tab-pane <?= $activeTab === 'security' ? '' : 'd-none' ?>" id="paneSecurity">
            <form method="POST" class="dash-card border p-4" style="max-width: 650px">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="password">
                
                <!-- Current Password -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Current Password</label>
                    <div class="position-relative">
                        <input type="password" name="current_password" class="form-control pe-5" placeholder="Enter current password" required>
                        <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y text-muted text-decoration-none pe-3 password-toggle">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- New Password -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">New Password</label>
                    <div class="position-relative">
                        <input type="password" name="new_password" class="form-control pe-5" placeholder="Enter new password (min. 6 characters)" required>
                        <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y text-muted text-decoration-none pe-3 password-toggle">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Confirm New Password</label>
                    <div class="position-relative">
                        <input type="password" name="confirm_password" class="form-control pe-5" placeholder="Confirm your new password" required>
                        <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y text-muted text-decoration-none pe-3 password-toggle">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button class="btn btn-teal py-2 px-4 fw-bold"><i class="fas fa-lock me-2"></i>Change Password</button>
            </form>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching controls
    var btnProfile = document.getElementById('tabProfile');
    var btnSecurity = document.getElementById('tabSecurity');
    var paneProfile = document.getElementById('paneProfile');
    var paneSecurity = document.getElementById('paneSecurity');
    
    btnProfile.addEventListener('click', function() {
        btnProfile.className = 'btn btn-link nav-link pb-2.5 fw-bold px-0 border-bottom border-2 border-teal text-teal';
        btnSecurity.className = 'btn btn-link nav-link pb-2.5 fw-bold px-0 border-bottom border-2 border-transparent text-muted';
        paneProfile.classList.remove('d-none');
        paneSecurity.classList.add('d-none');
    });
    
    btnSecurity.addEventListener('click', function() {
        btnSecurity.className = 'btn btn-link nav-link pb-2.5 fw-bold px-0 border-bottom border-2 border-teal text-teal';
        btnProfile.className = 'btn btn-link nav-link pb-2.5 fw-bold px-0 border-bottom border-2 border-transparent text-muted';
        paneSecurity.classList.remove('d-none');
        paneProfile.classList.add('d-none');
    });

    // Avatar previewer
    var avatarInput = document.getElementById('avatarInput');
    var avatarPreview = document.getElementById('avatarPreview');
    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function() {
            var file = this.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    avatarPreview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    }

    // Password visibility toggles
    document.querySelectorAll('.password-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var input = this.previousElementSibling;
            var icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'far fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'far fa-eye';
            }
        });
    });
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
