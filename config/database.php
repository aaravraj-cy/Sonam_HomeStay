<?php
// Safer database connection with install link
require_once __DIR__ . '/constants.php';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $sslCaPath = db_ssl_ca_path();
    if ($sslCaPath !== '') {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCaPath;
    }

    $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Self-healing database updates
    try {
        $check = $conn->query("SHOW COLUMNS FROM room_images LIKE 'sort_order'")->fetch();
        if (!$check) {
            $conn->exec("ALTER TABLE room_images ADD COLUMN sort_order INT NOT NULL DEFAULT 0");
        }

        // Self-healing check for gallery_images table
        $tableCheck = $conn->query("SHOW TABLES LIKE 'gallery_images'")->fetch();
        if (!$tableCheck) {
            $conn->exec("CREATE TABLE gallery_images (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                owner_id INT UNSIGNED NOT NULL,
                image_path VARCHAR(255) NOT NULL,
                title VARCHAR(150) DEFAULT NULL,
                city VARCHAR(100) DEFAULT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE CASCADE
            ) ENGINE=InnoDB");
        }
    } catch (PDOException $ex) {
        // Silently skip if database tables aren't installed yet
    }
} catch (PDOException $e) {
    $install = BASE_URL . 'database/install.php';
    $isLocalDatabase = in_array(strtolower(DB_HOST), ['localhost', '127.0.0.1', '::1'], true);
    $actionHtml = $isLocalDatabase
        ? '<a class="btn btn-success w-100" href="' . htmlspecialchars($install) . '">Open Installer</a>'
        : '<div class="alert alert-info mb-0">Installer access is disabled for remote database configuration.</div>';
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Setup Required</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body{min-height:100vh;display:flex;align-items:center;background:#faf8f5;font-family:system-ui}</style>
    </head><body><div class="container"><div class="card shadow mx-auto p-4" style="max-width:480px;border-radius:1rem">
    <h2 style="color:#0f766e">Sonam Homestay</h2>
    <p class="text-muted">Database is not ready yet.</p>
    <div class="alert alert-warning">Please check the database configuration and initialize the database if needed.</div>
    ' . $actionHtml . '
    </div></div></body></html>';
    exit;
}
