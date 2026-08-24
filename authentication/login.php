<?php
// Login page - simple PHP
require_once __DIR__ . '/../includes/functions.php';

function safe_login_redirect($target)
{
    $target = trim((string)$target);
    if ($target === '') {
        return '';
    }
    if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $target) && strpos($target, BASE_URL) !== 0) {
        return '';
    }
    if (strpos($target, BASE_URL) === 0) {
        return $target;
    }
    if (strpos($target, '/') === 0 && strpos($target, '//') !== 0 && strpos($target, '/\\') !== 0) {
        return $target;
    }
    return BASE_URL . ltrim($target, '/');
}

$redirectTo = trim($_POST['redirect'] ?? $_GET['redirect'] ?? ($_SESSION['redirect_after_login'] ?? ''));
$safeRedirectTo = safe_login_redirect($redirectTo);

if (is_logged_in()) {
    if (is_owner()) {
        if ($safeRedirectTo !== '') {
            redirect($safeRedirectTo);
        }
        redirect(BASE_URL . 'owner/dashboard.php');
    } elseif ($safeRedirectTo !== '') {
        redirect($safeRedirectTo);
    } else {
        redirect(BASE_URL . 'user/dashboard.php');
    }
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (!rate_limit('login', 8, 300)) {
        $error = 'Too many login attempts. Please wait a few minutes and try again.';
    }
    $email = input_string($_POST['email'] ?? '', 150);
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    if (!in_array($role, ['user', 'owner'], true)) {
        $role = 'user';
    }

    if ($error !== '') {
        // Rate limit message already set.
    } elseif ($email == '' || $password == '') {
        $error = 'Email and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } else {
        $stmt = $conn->prepare('SELECT * FROM users WHERE email = ? AND role = ?');
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['is_active'] == 0) {
                $error = 'Account is deactivated.';
            } else {
                refresh_login_session();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['profile_image'] = $user['profile_image'];

                // Remember me
                if (!empty($_POST['remember'])) {
                    $token = bin2hex(random_bytes(16));
                    $conn->prepare('UPDATE users SET remember_token = ? WHERE id = ?')->execute([$token, $user['id']]);
                    set_remember_cookie($user['id'] . ':' . $token, time() + (30 * 86400));
                }

                set_flash('success', 'Welcome, ' . $user['full_name'] . '!');
                if ($user['role'] === 'owner') {
                    unset($_SESSION['redirect_after_login']);
                    if ($safeRedirectTo !== '') {
                        redirect($safeRedirectTo);
                    }
                    redirect(BASE_URL . 'owner/dashboard.php');
                } else {
                    unset($_SESSION['redirect_after_login']);
                    if ($safeRedirectTo !== '') {
                        redirect($safeRedirectTo);
                    }
                    redirect(BASE_URL . 'user/dashboard.php');
                }
            }
        } else {
            $error = 'Invalid email, password or role.';
        }
    }
}

$pageTitle = 'Login';
$hideNav = true;
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-page animate__animated animate__fadeIn">
    <!-- Visual Panel -->
    <div class="auth-visual d-flex flex-column justify-content-between py-5 px-5">
        <a href="<?= BASE_URL ?>" class="sn-brand text-white mb-4 d-inline-flex align-items-center gap-2">
            <span class="brand-mark"><i class="fas fa-home"></i></span>
            <span class="text-white fw-bold h4 m-0">Sonam Homestay</span>
        </a>
        <div>
            <h2 class="display-font text-white h1 mb-3 fw-bold" style="line-height: 1.3">Discover unique, cozy stays hosted by locals.</h2>
            <p class="text-white-50 mb-0 fs-5">Join our community of hosts and travelers sharing authentic experiences and comfortable living spaces.</p>
        </div>
        <div class="d-flex align-items-center gap-3 bg-white bg-opacity-10 p-3 rounded-3 mt-4" style="backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1)">
            <i class="fas fa-quote-left text-teal fs-3"></i>
            <div class="text-white">
                <p class="small mb-1 text-white-50 font-italic">"The most memorable trip of my life. Sonam Homestay connects you with host hospitality that you can't get at hotels."</p>
                <small class="fw-bold text-white">— Rohit S., Guest traveler</small>
            </div>
        </div>
    </div>
    
    <!-- Form Wrapper -->
    <div class="auth-form-wrap">
        <div class="auth-card border">
            <!-- Mobile Brand Header -->
            <div class="text-center mb-4 d-lg-none">
                <a href="<?= BASE_URL ?>" class="sn-brand mb-2">
                    <span class="brand-mark"><i class="fas fa-mountain"></i></span>
                    <span class="brand-text"><?= e(APP_NAME) ?></span>
                </a>
            </div>
            
            <h1 class="h3 display-font fw-bold mb-1">Welcome back</h1>
            <p class="text-muted small mb-4">Enter your credentials to access your account.</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 small py-2 px-3 rounded-3" role="alert">
                    <i class="fas fa-triangle-exclamation"></i>
                    <div><?= e($error) ?></div>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <?= csrf_field() ?>
                <?php if ($safeRedirectTo !== ''): ?>
                <input type="hidden" name="redirect" value="<?= e($safeRedirectTo) ?>">
                <?php endif; ?>
                
                <!-- Segmented Role Selector -->
                <div class="mb-3">
                    <label class="form-label d-block text-muted small fw-bold text-uppercase mb-2">Login as</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="role" id="role_user" value="user" checked>
                        <label class="btn btn-outline-primary py-2 fw-semibold" for="role_user">
                            <i class="fas fa-user-circle me-1"></i> Guest
                        </label>
                        <input type="radio" class="btn-check" name="role" id="role_owner" value="owner" <?= (isset($_POST['role']) && $_POST['role'] === 'owner') ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary py-2 fw-semibold" for="role_owner">
                            <i class="fas fa-house-user me-1"></i> Owner / Host
                        </label>
                    </div>
                </div>
                
                <!-- Email Input -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Email address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="far fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control border-start-0 ps-0" value="<?= e($email) ?>" required>
                    </div>
                </div>
                
                <!-- Password Input -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Password</label>
                    <div class="input-group position-relative">
                        <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" class="form-control border-start-0 border-end-0 ps-0" required>
                        <button type="button" class="password-toggle" style="border: 1px solid var(--sn-border); border-left: 0; background: transparent; border-radius: 0 var(--sn-radius) var(--sn-radius) 0; height: 100%; padding: 0 0.9rem;" tabindex="-1"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
                
                <!-- Remember & Forgot Password -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check m-0">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label text-muted small" for="remember">Remember me</label>
                    </div>
                    <a href="<?= BASE_URL ?>authentication/forgot-password.php" class="small text-decoration-none fw-semibold">Forgot password?</a>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 py-2.5 fw-semibold"><i class="fas fa-sign-in-alt me-2"></i>Sign In</button>
            </form>
            

            
            <p class="mt-4 mb-0 text-center text-muted small">
                Don't have an account? <a href="<?= BASE_URL ?>authentication/register.php" class="fw-semibold text-teal text-decoration-none">Register here</a>
                <br><span class="text-muted-50">Want to host?</span> <a href="<?= BASE_URL ?>authentication/owner-register.php" class="fw-semibold text-teal text-decoration-none">Register as Host</a>
            </p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
