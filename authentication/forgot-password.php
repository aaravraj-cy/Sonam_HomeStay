<?php
// Forgot password - simple version
require_once __DIR__ . '/../includes/functions.php';

$error = '';
$success = '';
$step = 'request';
$email = trim($_GET['email'] ?? '');
$token = trim($_GET['token'] ?? '');

if ($email && $token) {
    $stmt = $conn->prepare('SELECT * FROM password_resets WHERE email = ? AND token = ? AND expires_at > NOW()');
    $stmt->execute([$email, hash('sha256', $token)]);
    if ($stmt->fetch()) {
        $step = 'reset';
    } else {
        $error = 'Invalid or expired reset link.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'request') {
        $email = trim($_POST['email'] ?? '');
        $stmt = $conn->prepare('SELECT id, full_name FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(16));
            $tokenHash = hash('sha256', $token);
            $conn->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$email]);
            $conn->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))')->execute([$email, $tokenHash]);
            
            // Build real email
            $resetLink = BASE_URL . 'authentication/forgot-password.php?email=' . urlencode($email) . '&token=' . $token;
            
            $to = $email;
            $subject = "Reset Your Password - Sonam Homestay";
            
            // HTML Email Template
            $message = "
            <html>
            <head>
              <title>Reset Your Password - Sonam Homestay</title>
              <style>
                body { font-family: system-ui, sans-serif; background-color: #f3f4f6; color: #1f2937; padding: 20px; }
                .card { background-color: #ffffff; border-radius: 8px; padding: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); max-width: 500px; margin: 0 auto; }
                .brand { color: #0d9488; font-size: 24px; font-weight: bold; margin-bottom: 20px; text-decoration: none; }
                .btn { background-color: #0d9488; color: #ffffff !important; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: bold; margin-top: 15px; }
                .footer { font-size: 11px; color: #6b7280; margin-top: 30px; border-top: 1px solid #e5e7eb; padding-top: 15px; }
              </style>
            </head>
            <body>
              <div class='card'>
                <div class='brand'>Sonam Homestay</div>
                <h3>Hello " . htmlspecialchars($user['full_name']) . ",</h3>
                <p>We received a request to reset the password for your Sonam Homestay account. Click the button below to set a new password. This link is valid for 1 hour:</p>
                <p><a href='{$resetLink}' class='btn'>Reset Password</a></p>
                <p>If the button doesn't work, copy and paste this URL into your browser:</p>
                <p style='font-size:12px; color:#6b7280; word-break:break-all;'>{$resetLink}</p>
                <p>If you did not request a password reset, you can safely ignore this email.</p>
                <div class='footer'>
                  Sonam Homestay, Khechuperi, West Sikkim
                </div>
              </div>
            </body>
            </html>
            ";

            // Headers for HTML Mail
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From: Sonam Homestay <noreply@sonamhomestay.local>' . "\r\n";

            // Attempt email delivery
            @mail($to, $subject, $message, $headers);
        }
        $success = 'If an account exists for that email address, a password reset link has been sent.';
    }

    if ($action === 'reset') {
        $email = trim($_POST['email'] ?? '');
        $token = trim($_POST['token'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirm'] ?? '';
        $step = 'reset';

        if (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $stmt = $conn->prepare('SELECT id FROM password_resets WHERE email = ? AND token = ? AND expires_at > NOW()');
            $stmt->execute([$email, hash('sha256', $token)]);
            if (!$stmt->fetch()) {
                $error = 'Invalid or expired reset link.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $conn->prepare('UPDATE users SET password = ? WHERE email = ?')->execute([$hash, $email]);
                $conn->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$email]);
                set_flash('success', 'Password updated successfully. Please login with your new password.');
                redirect(BASE_URL . 'authentication/login.php');
            }
        }
    }
}

$pageTitle = 'Forgot Password';
$hideNav = true;
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-page animate__animated animate__fadeIn">
    <!-- Visual Panel -->
    <div class="auth-visual d-flex flex-column justify-content-between py-5 px-5">
        <a href="<?= BASE_URL ?>" class="sn-brand text-white mb-4 d-inline-flex align-items-center gap-2">
            <span class="brand-mark"><i class="fas fa-mountain"></i></span>
            <span class="text-white fw-bold h4 m-0">Sonam Homestay</span>
        </a>
        <div>
            <h2 class="display-font text-white h1 mb-3 fw-bold" style="line-height: 1.3">Experience the magic of Sikkim with local hosts.</h2>
            <p class="text-white-50 mb-0 fs-5">Authentic homestays, verified ratings, and lifetime memories await you in West Sikkim.</p>
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
            
            <?php if ($step === 'request'): ?>
                <div class="text-center text-lg-start mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-teal bg-opacity-10 text-teal rounded-circle mb-3" style="width: 56px; height: 56px;">
                        <i class="fas fa-key fs-4"></i>
                    </div>
                    <h1 class="h3 display-font fw-bold mb-1">Forgot password?</h1>
                    <p class="text-muted small">No worries! Enter your email to recover your credentials.</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2 small py-2.5 px-3 rounded-3 mb-3 animate__animated animate__shakeX">
                        <i class="fas fa-circle-exclamation fs-6"></i>
                        <div><?= e($error) ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success d-flex align-items-center gap-2 small py-2.5 px-3 rounded-3 mb-3">
                        <i class="fas fa-circle-check fs-6"></i>
                        <div><?= e($success) ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if (!$success): ?>
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="request">
                    
                    <div class="mb-4">
                        <label class="form-label fw-semibold text-dark">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                            <input type="email" name="email" class="form-control border-start-0" required>
                        </div>
                    </div>
                    
                    <button class="btn btn-primary w-100 py-2.5 fw-semibold d-flex align-items-center justify-content-center gap-2 mb-3">
                        <i class="fas fa-paper-plane"></i> Send Reset Link
                    </button>
                </form>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="text-center text-lg-start mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-teal bg-opacity-10 text-teal rounded-circle mb-3" style="width: 56px; height: 56px;">
                        <i class="fas fa-lock-open fs-4"></i>
                    </div>
                    <h1 class="h3 display-font fw-bold mb-1">Create new password</h1>
                    <p class="text-muted small">Enter your secure credentials to recover access.</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2 small py-2.5 px-3 rounded-3 mb-3 animate__animated animate__shakeX">
                        <i class="fas fa-circle-exclamation fs-6"></i>
                        <div><?= e($error) ?></div>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="reset">
                    <input type="hidden" name="email" value="<?= e($email) ?>">
                    <input type="hidden" name="token" value="<?= e($token) ?>">
                    
                    <div class="mb-3 position-relative">
                        <label class="form-label fw-semibold text-dark">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-key text-muted"></i></span>
                            <input type="password" name="password" id="password" class="form-control border-start-0" required>
                        </div>
                        <button type="button" class="password-toggle" data-target="password"><i class="fas fa-eye"></i></button>
                    </div>

                    <div class="mb-4 position-relative">
                        <label class="form-label fw-semibold text-dark">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" name="password_confirm" id="password_confirm" class="form-control border-start-0" required>
                        </div>
                        <button type="button" class="password-toggle" data-target="password_confirm"><i class="fas fa-eye"></i></button>
                    </div>
                    
                    <button class="btn btn-primary w-100 py-2.5 fw-semibold d-flex align-items-center justify-content-center gap-2 mb-3">
                        <i class="fas fa-circle-check"></i> Update Password
                    </button>
                </form>
            <?php endif; ?>
            
            <p class="mt-2 mb-0 text-center"><a href="<?= BASE_URL ?>authentication/login.php" class="text-teal text-decoration-none fw-semibold small"><i class="fas fa-arrow-left me-1"></i> Back to login</a></p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Password visibility toggle handler
    document.querySelectorAll('.password-toggle').forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            var targetId = this.getAttribute('data-target');
            var input = document.getElementById(targetId);
            var icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
