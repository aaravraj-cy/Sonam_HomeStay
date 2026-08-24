<?php
// Installer - creates empty database (no demo users)
require_once __DIR__ . '/../config/constants.php';

$messages = [];
$ok = false;
$canRun = false;

function install_bool_env($key)
{
    return in_array(strtolower((string) env_value($key, '')), ['1', 'true', 'yes', 'on'], true);
}

function install_is_local_database()
{
    return in_array(strtolower(DB_HOST), ['localhost', '127.0.0.1', '::1'], true);
}

function install_pdo_options()
{
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ];

    $sslCaPath = db_ssl_ca_path();
    if ($sslCaPath !== '') {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCaPath;
    }

    return $options;
}

try {
    $isLocal = install_is_local_database();
    $remoteInstallAllowed = install_bool_env('INSTALL_ALLOW_REMOTE');
    $forceLocalReinstall = $isLocal && ($_GET['force'] ?? '') === '1';

    if (!$isLocal && !$remoteInstallAllowed) {
        $messages[] = 'Remote database installation is disabled. Set INSTALL_ALLOW_REMOTE=1 only for a deliberate first-time production initialization.';
    } elseif (DB_NAME !== 'sonamDB') {
        $messages[] = 'Installer blocked because database/sonam.sql creates sonamDB. Set DB_NAME=sonamDB before running it.';
    } else {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, install_pdo_options());

        $stmt = $pdo->prepare('SHOW DATABASES LIKE ?');
        $stmt->execute([DB_NAME]);
        $dbExists = (bool) $stmt->fetchColumn();

        if (!$isLocal && $dbExists) {
            $messages[] = 'Remote install blocked because the database already exists. Import database/sonam.sql manually only into an empty sonamDB database.';
        } elseif ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $messages[] = $dbExists
                ? 'Database already exists. Installer is locked to prevent accidental reset.'
                : 'Ready to install Sonam Homestay. Confirm below to create the database.';
            $canRun = !$dbExists || $forceLocalReinstall;
        } elseif ($dbExists && !$forceLocalReinstall) {
            $messages[] = 'Install blocked because the database already exists. Add ?force=1 only for an intentional local reinstall.';
        } else {
            $sql = file_get_contents(__DIR__ . '/sonam.sql');
            $pdo->exec($sql);

            $dirs = [
                UPLOAD_PROFILES,
                UPLOAD_HOMESTAYS,
                UPLOAD_ROOMS,
                UPLOAD_GALLERY,
            ];
            foreach ($dirs as $dir) {
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
            }

            $messages[] = 'Database created successfully. No demo data was added.';
            $messages[] = 'Register as Owner or as Guest User from the website.';
            $ok = true;
        }
    }
} catch (Exception $e) {
    $messages[] = 'Installation failed. Check database host, port, credentials, SSL CA path, and database permissions.';
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
