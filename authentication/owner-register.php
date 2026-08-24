<?php
// Owner registration
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    redirect(BASE_URL . (is_owner() ? 'owner' : 'user') . '/dashboard.php');
}
if (defined('MAX_OWNERS') && MAX_OWNERS >= 0 && owner_count() >= MAX_OWNERS) {
    set_flash('error', 'Owner registration is currently closed.');
    redirect(BASE_URL . 'authentication/login.php');
}

$error = '';
$name = $email = $phone = $business = $city = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $business = trim($_POST['business_name'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if (strlen($name) < 2 || strlen($business) < 2) {
        $error = 'Name and business name are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $check = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'Email already used.';
        } else {
            try {
                $conn->beginTransaction();
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('INSERT INTO users (full_name, email, phone, password, role, city, email_verified) VALUES (?, ?, ?, ?, ?, ?, 1)');
                $stmt->execute([$name, $email, $phone ?: null, $hash, 'owner', $city ?: null]);
                $uid = $conn->lastInsertId();

                $stmt2 = $conn->prepare('INSERT INTO owners (user_id, business_name) VALUES (?, ?)');
                $stmt2->execute([$uid, $business]);
                $conn->commit();

                refresh_login_session();
                $_SESSION['user_id'] = $uid;
                $_SESSION['full_name'] = $name;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'owner';
                $_SESSION['profile_image'] = null;

                add_notification($uid, 'Welcome Host!', 'Add your first homestay to get started.', BASE_URL . 'owner/add-homestay.php');
                set_flash('success', 'Owner account created!');
                redirect(BASE_URL . 'owner/dashboard.php');
            } catch (Exception $e) {
                $conn->rollBack();
                $error = 'Registration failed. Try again.';
            }
        }
    }
}

$pageTitle = 'Owner Register';
$hideNav = true;
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-page animate__animated animate__fadeIn">
    <div class="auth-visual d-flex flex-column justify-content-between py-5 px-5">
        <a href="<?= BASE_URL ?>" class="sn-brand text-white mb-4 d-inline-flex align-items-center gap-2">
            <span class="brand-mark"><i class="fas fa-home"></i></span>
            <span class="text-white fw-bold h4 m-0">Sonam Homestay</span>
        </a>
        <div>
            <h2 class="display-font text-white h1 mb-3 fw-bold" style="line-height: 1.3">Host guests with confidence.</h2>
            <p class="text-white-50 mb-0 fs-5">Manage rooms, bookings, photos, and guest communication from one clean dashboard.</p>
        </div>
        <div class="d-flex align-items-center gap-3 bg-white bg-opacity-10 p-3 rounded-3 mt-4" style="backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1)">
            <i class="fas fa-house-user text-teal fs-3"></i>
            <div class="text-white">
                <p class="small mb-1 text-white-50">Built for family-run homestays, simple rooms, local food, and direct guest bookings.</p>
                <small class="fw-bold text-white">Owner tools for Sonam Homestay</small>
            </div>
        </div>
    </div>
    <div class="auth-form-wrap py-5">
        <div class="auth-card border">
            <h1 class="h3 display-font fw-bold mb-1">Host registration</h1>
            <p class="text-muted small mb-4">Create an owner account to list and manage homestay rooms.</p>
            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 small py-2 px-3 rounded-3" role="alert">
                    <i class="fas fa-triangle-exclamation"></i>
                    <div><?= e($error) ?></div>
                </div>
            <?php endif; ?>
            <form method="POST">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Full name</label>
                    <input type="text" name="full_name" class="form-control" value="<?= e($name) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Business name</label>
                    <input type="text" name="business_name" class="form-control" value="<?= e($business) ?>" placeholder="e.g. Sonam Homestay" required>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= e($email) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= e($phone) ?>">
                    </div>
                </div>
                <div class="mb-3 mt-3">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">City</label>
                    <input type="text" name="city" class="form-control" value="<?= e($city) ?>" placeholder="Khecheopalri / Pelling">
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">Confirm password</label>
                        <input type="password" name="password_confirm" class="form-control" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold"><i class="fas fa-user-plus"></i>Create owner account</button>
            </form>
            <p class="mt-4 text-center mb-0 small text-muted">Already registered? <a href="<?= BASE_URL ?>authentication/login.php" class="fw-semibold text-teal text-decoration-none">Back to login</a></p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
