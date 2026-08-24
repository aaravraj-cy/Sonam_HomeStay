<?php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/functions.php';
}
$pageTitle = $pageTitle ?? APP_NAME;
$hideNav = $hideNav ?? false;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Fraunces:wght@600;700&display=swap" rel="stylesheet">
    <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
    <link href="<?= asset('css/dark-mode.css') ?>" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php if (is_logged_in() && !is_email_verified()): 
    // Fetch verification token to build the direct verification link
    try {
        $stmtVer = $conn->prepare("SELECT verification_token, email FROM users WHERE id = ?");
        $stmtVer->execute([$_SESSION['user_id']]);
        $uVer = $stmtVer->fetch();
        if ($uVer) {
            $verifyLink = BASE_URL . 'authentication/verify-email.php?email=' . urlencode($uVer['email']) . '&token=' . urlencode($uVer['verification_token'] ?? '');
?>
<div class="alert alert-warning border-0 rounded-0 py-2 text-center small mb-0 d-flex align-items-center justify-content-center gap-2" style="background-color: #fffbeb; color: #b45309; font-size: 0.85rem; border-bottom: 1px solid #fde68a !important; position: relative; z-index: 1050;">
    <i class="fas fa-triangle-exclamation"></i>
    <span>Your email address is not verified yet. Some features might be restricted.</span>
    <a href="<?= $verifyLink ?>" class="btn btn-warning btn-xs fw-semibold px-2 py-0.5" style="font-size: 0.7rem;">Verify Now</a>
</div>
<?php
        }
    } catch (Exception $e) {}
endif; ?>
<div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index:9999"></div>
<?php if (!$hideNav) require __DIR__ . '/navbar.php'; ?>
<main class="main-content">
<?= show_flash() ?>
