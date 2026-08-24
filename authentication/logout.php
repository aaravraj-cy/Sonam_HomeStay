<?php
// Logout
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    $conn->prepare('UPDATE users SET remember_token = NULL WHERE id = ?')->execute([$_SESSION['user_id']]);
}

if (isset($_COOKIE['sn_remember'])) {
    set_remember_cookie('', time() - 3600);
}

session_destroy();
redirect(BASE_URL);
