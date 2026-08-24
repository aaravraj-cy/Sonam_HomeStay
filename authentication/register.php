<?php
// Unified user and owner registration page
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    redirect(BASE_URL . (is_owner() ? 'owner' : 'user') . '/dashboard.php');
}

$error = '';
$name = '';
$email = '';
$phone = '';
$address = '';
$city = '';
$state = '';
$country = 'India';
$role = 'user';
$business_name = 'Sonam HomeStay';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $name = input_string($_POST['full_name'] ?? '', 120);
    $email = input_string($_POST['email'] ?? '', 150);
    $phone = input_string($_POST['phone'] ?? '', 20);
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';
    $role = trim($_POST['role'] ?? 'user');
    if (!in_array($role, ['user', 'owner'], true)) {
        $role = 'user';
    }
    $business_name = input_string($_POST['business_name'] ?? 'Sonam HomeStay', 150) ?: 'Sonam HomeStay';

    // Form validations
    if (!valid_name($name)) {
        $error = 'Please enter your full name.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($phone === '' || !valid_phone($phone)) {
        $error = 'Please enter a valid phone number.';
    } elseif (!valid_password($password)) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif ($role === 'owner' && defined('MAX_OWNERS') && MAX_OWNERS >= 0 && owner_count() >= MAX_OWNERS) {
        $error = 'Owner registration is currently closed.';
    } elseif ($role === 'owner' && strlen($business_name) < 2) {
        $error = 'Please enter your Business Name to register as Host.';
    } else {
        // Check if email already registered
        $check = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'Email address is already registered.';
        } else {
            try {
                $conn->beginTransaction();

                // Handle profile image upload
                $profileImage = null;

                $hash = password_hash($password, PASSWORD_DEFAULT);
                // Insert into users (automatically verified)
                $stmt = $conn->prepare('
                    INSERT INTO users (full_name, email, phone, password, role, profile_image, address, city, state, country, email_verified, verification_token)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NULL)
                ');
                $stmt->execute([
                    $name,
                    $email,
                    $phone ?: null,
                    $hash,
                    $role,
                    $profileImage,
                    $address ?: null,
                    $city ?: null,
                    $state ?: null,
                    $country ?: 'India'
                ]);

                $uid = $conn->lastInsertId();

                // If registering as owner, insert into owners table
                if ($role === 'owner') {
                    $stmt2 = $conn->prepare('INSERT INTO owners (user_id, business_name) VALUES (?, ?)');
                    $stmt2->execute([$uid, $business_name]);
                }

                $conn->commit();

                // Set session variables
                refresh_login_session();
                $_SESSION['user_id'] = $uid;
                $_SESSION['full_name'] = $name;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $role;
                $_SESSION['profile_image'] = $profileImage;

                // Welcome notifications
                if ($role === 'owner') {
                    add_notification($uid, 'Welcome Host!', 'Add your first homestay property to get started.', BASE_URL . 'owner/add-homestay.php');
                    set_flash('success', 'Host account created successfully!');
                    redirect(BASE_URL . 'owner/dashboard.php');
                } else {
                    add_notification($uid, 'Welcome!', 'Your Sonam Homestay guest account is ready.', BASE_URL . 'pages/search.php');
                    set_flash('success', 'Account created successfully!');
                    redirect(BASE_URL . 'user/dashboard.php');
                }
            } catch (Exception $e) {
                $conn->rollBack();
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

$pageTitle = 'Register';
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
            <h2 class="display-font text-white h1 mb-3 fw-bold" style="line-height: 1.3">Find your home away from home.</h2>
            <p class="text-white-50 mb-0 fs-5">Access thousands of local, cozy, and verified stays and connect with hosts who treat you like family.</p>
        </div>
        <div class="d-flex align-items-center gap-3 bg-white bg-opacity-10 p-3 rounded-3 mt-4" style="backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1)">
            <i class="fas fa-quote-left text-teal fs-3"></i>
            <div class="text-white">
                <p class="small mb-1 text-white-50 font-italic">"Registering was a breeze. Now I book stays in local homes whenever I travel—it's cheaper and much more authentic!"</p>
                <small class="fw-bold text-white">— Priya M., Traveler explorer</small>
            </div>
        </div>
    </div>

    <!-- Form Wrapper -->
    <div class="auth-form-wrap py-5">
        <div class="auth-card border p-4" style="max-width: 500px; width: 100%;">
            <!-- Mobile Brand Header -->
            <div class="text-center mb-4 d-lg-none">
                <a href="<?= BASE_URL ?>" class="sn-brand mb-2">
                    <span class="brand-mark"><i class="fas fa-mountain"></i></span>
                    <span class="brand-text"><?= e(APP_NAME) ?></span>
                </a>
            </div>

            <h1 class="h3 display-font fw-bold mb-1">Create Account</h1>
            <p class="text-muted small mb-4">Start your journey with us today.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 small py-2 px-3 rounded-3 mb-3" role="alert">
                    <i class="fas fa-triangle-exclamation"></i>
                    <div><?= e($error) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="registerForm">
                <?= csrf_field() ?>

                <!-- Account Role Selection -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Register As</label>
                    <select name="role" id="roleSelect" class="form-select fw-semibold" required>
                        <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>Guest (Traveler)</option>
                        <option value="owner" <?= $role === 'owner' ? 'selected' : '' ?>>Host (Property Owner)</option>
                    </select>
                </div>

                <!-- Business Name (Only shown for Host) -->
                <div class="mb-3 d-none" id="businessNameGroup">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Business Name</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="fas fa-briefcase" style="font-size: 0.85rem"></i></span>
                        <input type="text" name="business_name" id="businessNameInput" class="form-control border-start-0 ps-0" value="<?= e($business_name) ?>" placeholder="e.g. Sonam Hospitality Group">
                    </div>
                </div>

                <!-- Full Name Input -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Full name</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="far fa-user"></i></span>
                        <input type="text" name="full_name" class="form-control border-start-0 ps-0" value="<?= e($name) ?>" placeholder="e.g. Sonam Bhutia" required>
                    </div>
                </div>

                <!-- Email Input -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Email address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="far fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control border-start-0 ps-0" value="<?= e($email) ?>" placeholder="name@example.com" required>
                    </div>
                </div>

                <!-- Phone Input -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Phone number</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="fas fa-phone-alt" style="font-size: 0.85rem"></i></span>
                        <input type="text" name="phone" class="form-control border-start-0 ps-0" value="<?= e($phone) ?>" placeholder="e.g. +91 9735589678" required>
                    </div>
                </div>


                <!-- Password Input -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Password</label>
                    <div class="input-group position-relative">
                        <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" class="form-control border-start-0 border-end-0 ps-0" placeholder="Minimum 6 characters" required>
                        <button type="button" class="password-toggle" style="border: 1px solid var(--sn-border); border-left: 0; background: transparent; border-radius: 0 var(--sn-radius) var(--sn-radius) 0; height: 100%; padding: 0 0.9rem;" tabindex="-1"><i class="fas fa-eye"></i></button>
                    </div>
                </div>

                <!-- Confirm Password Input -->
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Confirm password</label>
                    <div class="input-group position-relative">
                        <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="fas fa-shield-halved" style="font-size: 0.85rem"></i></span>
                        <input type="password" name="password_confirm" class="form-control border-start-0 border-end-0 ps-0" placeholder="Repeat password" required>
                        <button type="button" class="password-toggle" style="border: 1px solid var(--sn-border); border-left: 0; background: transparent; border-radius: 0 var(--sn-radius) var(--sn-radius) 0; height: 100%; padding: 0 0.9rem;" tabindex="-1"><i class="fas fa-eye"></i></button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2.5 fw-semibold"><i class="fas fa-user-plus me-2"></i>Sign Up</button>
            </form>

            <p class="mt-4 mb-0 text-center text-muted small">
                Already have an account? <a href="<?= BASE_URL ?>authentication/login.php" class="fw-semibold text-teal text-decoration-none">Sign in here</a>
            </p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('roleSelect');
    const businessNameGroup = document.getElementById('businessNameGroup');
    const businessNameInput = document.getElementById('businessNameInput');

    function toggleBusinessName() {
        if (roleSelect.value === 'owner') {
            businessNameGroup.classList.remove('d-none');
            businessNameInput.setAttribute('required', 'required');
        } else {
            businessNameGroup.classList.add('d-none');
            businessNameInput.removeAttribute('required');
        }
    }

    roleSelect.addEventListener('change', toggleBusinessName);
    toggleBusinessName(); // Initial execution on load

    // Toggle Password Visibility
    document.querySelectorAll('.password-toggle').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const input = this.previousElementSibling;
            const icon = this.querySelector('i');

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
