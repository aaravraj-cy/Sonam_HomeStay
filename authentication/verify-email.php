<?php
// Email verify (simple)
require_once __DIR__ . '/../includes/functions.php';

$email = trim($_GET['email'] ?? '');
$token = trim($_GET['token'] ?? '');

if ($email && $token) {
    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? AND verification_token = ?');
    $stmt->execute([$email, $token]);
    $user = $stmt->fetch();
    if ($user) {
        $conn->prepare('UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?')->execute([$user['id']]);
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user['id']) {
            $_SESSION['email_verified'] = 1;
        }
        set_flash('success', 'Email verified!');
    } else {
        set_flash('error', 'Invalid verification link.');
    }
}
redirect(BASE_URL . 'authentication/login.php');
