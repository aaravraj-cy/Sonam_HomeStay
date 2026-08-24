<?php
// Simple installer - creates empty database (no demo users)
$host = 'localhost';
$user = 'root';
$pass = '';
$messages = [];
$ok = false;
$canRun = false;

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::MYSQL_ATTR_MULTI_STATEMENTS, true);

    $dbExists = (bool)$pdo->query("SHOW DATABASES LIKE 'sonamDB'")->fetchColumn();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $messages[] = $dbExists
            ? 'Database already exists. Installer is locked to prevent accidental reset.'
            : 'Ready to install Sonam Homestay. Confirm below to create the database.';
        $canRun = !$dbExists || ($_GET['force'] ?? '') === '1';
    } elseif ($dbExists && ($_GET['force'] ?? '') !== '1') {
        $messages[] = 'Install blocked because the database already exists. Add ?force=1 only if you intentionally want to reinstall.';
    } else {
        $sql = file_get_contents(__DIR__ . '/sonam.sql');
        $pdo->exec($sql);

        $dirs = [
            dirname(__DIR__) . '/assets/uploads/profiles',
            dirname(__DIR__) . '/assets/uploads/homestays',
            dirname(__DIR__) . '/assets/uploads/rooms',
        ];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        $messages[] = 'Database created successfully. No demo data was added.';
        $messages[] = 'Register as Owner (only 1 allowed) or as Guest User from the website.';
        $ok = true;
    }
} catch (Exception $e) {
    $messages[] = 'Installation failed. Check that MySQL is running and database credentials are correct.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sonam Homestay Install</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card mx-auto p-4" style="max-width:520px">
        <h2>Sonam Homestay Installer</h2>
        <?php foreach ($messages as $m): ?>
            <div class="alert alert-<?= $ok ? 'success' : 'danger' ?>"><?= htmlspecialchars($m) ?></div>
        <?php endforeach; ?>
        <?php if ($ok): ?>
            <a href="../index.php" class="btn btn-primary">Open Website</a>
        <?php elseif ($canRun): ?>
            <form method="POST">
                <button class="btn btn-danger">Confirm Install</button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
