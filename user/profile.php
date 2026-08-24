<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('user');

$uid = $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$uid]);
$user = $stmt->fetch();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? 'profile';

    if ($action === 'profile') {
        $name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $img = $user['profile_image'];
        if (!empty($_FILES['profile_image']['name'])) {
            $up = upload_image($_FILES['profile_image'], UPLOAD_PROFILES);
            if ($up) $img = $up;
            else $error = 'Invalid image.';
        }
        if ($name && !$error) {
            $conn->prepare('UPDATE users SET full_name=?, phone=?, city=?, profile_image=? WHERE id=?')->execute([$name, $phone ?: null, $city ?: null, $img, $uid]);
            $_SESSION['full_name'] = $name;
            $_SESSION['profile_image'] = $img;
            $stmt->execute([$uid]);
            $user = $stmt->fetch();
            $success = 'Profile updated successfully.';
        }
    }

    if ($action === 'password') {
        $cur = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $c2 = $_POST['confirm_password'] ?? '';
        if (!password_verify($cur, $user['password'])) $error = 'Wrong current password.';
        elseif (strlen($new) < 6) $error = 'Password must be at least 6 characters.';
        elseif ($new !== $c2) $error = 'Passwords do not match.';
        else {
            $conn->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
            $success = 'Password changed successfully.';
        }
    }
}

$pageTitle = 'Profile';
$sidebarRole = 'user';
$sidebarActive = 'profile';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout animate__animated animate__fadeIn">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="dashboard-main">
        <!-- Dashboard Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <button class="btn btn-outline-primary mobile-sidebar-toggle d-lg-none"><i class="fas fa-bars"></i> Menu</button>
            <div>
                <h1 class="h3 display-font fw-bold mb-1"><i class="fas fa-user-circle text-teal me-2"></i>My Profile</h1>
                <p class="text-muted small mb-0">Manage your account settings, contact information, and security.</p>
            </div>
        </div>

        <!-- Alert messages -->
        <?php if ($success): ?>
            <div class="alert alert-success d-flex align-items-center gap-2 py-2.5 px-3 rounded-3 mb-4" role="alert">
                <i class="fas fa-circle-check"></i>
                <div><?= e($success) ?></div>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 py-2.5 px-3 rounded-3 mb-4" role="alert">
                <i class="fas fa-triangle-exclamation"></i>
                <div><?= e($error) ?></div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Profile Details Card -->
            <div class="col-lg-7">
                <div class="dash-card border">
                    <h5 class="fw-bold mb-4"><i class="far fa-id-card text-teal me-2"></i>Profile Information</h5>
                    <form method="POST" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="profile">
                        
                        <!-- Profile Image Section -->
                        <div class="d-flex align-items-center gap-4 mb-4 p-3 bg-light bg-opacity-20 border rounded-3">
                            <div class="position-relative">
                                <img src="<?= profile_img($user['profile_image']) ?>" width="90" height="90" class="rounded-circle border border-2 border-white shadow-sm object-fit-cover" alt="Avatar">
                            </div>
                            <div class="flex-grow-1">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Upload Profile Photo</label>
                                <input type="file" name="profile_image" class="form-control form-control-sm" accept="image/*">
                                <small class="text-muted d-block mt-1" style="font-size:0.75rem">Supports JPG, PNG or WEBP. Max 5MB.</small>
                            </div>
                        </div>

                        <!-- Name Input -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-1"><i class="fas fa-signature text-muted me-2"></i>Full Name</label>
                            <input type="text" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required>
                        </div>

                        <!-- Email Input -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-1"><i class="far fa-envelope text-muted me-2"></i>Email Address (Disabled)</label>
                            <input type="email" class="form-control bg-light" value="<?= e($user['email']) ?>" disabled>
                            <small class="text-muted d-block mt-1" style="font-size:0.75rem">Email cannot be changed directly for account security.</small>
                        </div>

                        <!-- Phone Input -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-1"><i class="fas fa-phone text-muted me-2"></i>Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="9876543210" value="<?= e($user['phone'] ?? '') ?>">
                        </div>

                        <!-- City Input -->
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-1"><i class="fas fa-map-location-dot text-muted me-2"></i>City</label>
                            <input type="text" name="city" class="form-control" placeholder="e.g. Manali" value="<?= e($user['city'] ?? '') ?>">
                        </div>

                        <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i>Save Changes</button>
                    </form>
                </div>
            </div>

            <!-- Password Card -->
            <div class="col-lg-5">
                <div class="dash-card border bg-white">
                    <h5 class="fw-bold mb-4"><i class="fas fa-shield-halved text-teal me-2"></i>Security</h5>
                    <form method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="password">
                        
                        <!-- Current Password -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-1"><i class="fas fa-key text-muted me-2"></i>Current Password</label>
                            <div class="input-group position-relative">
                                <input type="password" name="current_password" class="form-control border-end-0" placeholder="••••••••" required>
                                <button type="button" class="password-toggle" style="border: 1px solid var(--sn-border); border-left: 0; background: transparent; border-radius: 0 var(--sn-radius) var(--sn-radius) 0; padding: 0 0.9rem;" tabindex="-1"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>

                        <!-- New Password -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-1"><i class="fas fa-lock text-muted me-2"></i>New Password</label>
                            <div class="input-group position-relative">
                                <input type="password" name="new_password" class="form-control border-end-0" placeholder="At least 6 characters" required>
                                <button type="button" class="password-toggle" style="border: 1px solid var(--sn-border); border-left: 0; background: transparent; border-radius: 0 var(--sn-radius) var(--sn-radius) 0; padding: 0 0.9rem;" tabindex="-1"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>

                        <!-- Confirm New Password -->
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-1"><i class="fas fa-check-double text-muted me-2"></i>Confirm New Password</label>
                            <div class="input-group position-relative">
                                <input type="password" name="confirm_password" class="form-control border-end-0" placeholder="Confirm new password" required>
                                <button type="button" class="password-toggle" style="border: 1px solid var(--sn-border); border-left: 0; background: transparent; border-radius: 0 var(--sn-radius) var(--sn-radius) 0; padding: 0 0.9rem;" tabindex="-1"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-outline-primary w-100 py-2 fw-semibold"><i class="fas fa-lock-open me-2"></i>Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
