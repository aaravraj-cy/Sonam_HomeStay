<?php
// Start session safely
require_once __DIR__ . '/constants.php';

if (session_status() === PHP_SESSION_NONE) {
    $secureCookie = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Create CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
}

function check_csrf()
{
    if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
        die('Invalid request. Please go back and try again.');
    }
}

function refresh_login_session()
{
    session_regenerate_id(true);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function set_remember_cookie($value, $expires)
{
    $secureCookie = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    setcookie('sn_remember', $value, [
        'expires' => $expires,
        'path' => '/',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

function is_owner()
{
    return is_logged_in() && $_SESSION['role'] === 'owner';
}

function is_user()
{
    return is_logged_in() && $_SESSION['role'] === 'user';
}

function require_login($role = '')
{
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = 'Please login first.';
        $currentPath = $_SERVER['REQUEST_URI'] ?? '';
        if ($currentPath !== '') {
            $_SESSION['redirect_after_login'] = $currentPath;
        }
        header('Location: ' . BASE_URL . 'authentication/login.php' . ($currentPath !== '' ? '?redirect=' . rawurlencode($currentPath) : ''));
        exit;
    }
    if ($role !== '' && $_SESSION['role'] !== $role) {
        $_SESSION['flash_error'] = 'Access denied.';
        header('Location: ' . BASE_URL);
        exit;
    }
}

function set_flash($type, $message)
{
    $_SESSION['flash_' . $type] = $message;
}

function show_flash()
{
    $alerts = ['success', 'error', 'warning', 'info'];
    $html = '';

    foreach ($alerts as $type) {
        $key = 'flash_' . $type;
        if (!empty($_SESSION[$key])) {
            $message = json_encode((string)$_SESSION[$key], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
            $icon = json_encode(($type === 'error') ? 'error' : (($type === 'success') ? 'success' : $type));
            $title = json_encode(($type === 'error') ? 'Error' : (($type === 'success') ? 'Success' : ucfirst($type)));

            $html .= "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: {$title},
                            text: {$message},
                            icon: {$icon},
                            confirmButtonColor: '#0f766e',
                            customClass: {
                                popup: 'rounded-4 shadow-sm'
                            }
                        });
                    }
                });
            </script>";

            unset($_SESSION[$key]);
        }
    }

    return $html;
}

function is_email_verified()
{
    return true;
}

function require_verification()
{
    if (!is_email_verified()) {
        $_SESSION['flash_error'] = 'Please verify your email address to access this feature.';
        header('Location: ' . BASE_URL . ($_SESSION['role'] === 'owner' ? 'owner/dashboard.php' : 'user/dashboard.php'));
        exit;
    }
}
