<?php
// Safer database connection with install link
require_once __DIR__ . '/constants.php';

try {
    $conn = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS
    );
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
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Setup Required</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body{min-height:100vh;display:flex;align-items:center;background:#faf8f5;font-family:system-ui}</style>
    </head><body><div class="container"><div class="card shadow mx-auto p-4" style="max-width:480px;border-radius:1rem">
    <h2 style="color:#0f766e">Sonam Homestay</h2>
    <p class="text-muted">Database is not ready yet.</p>
    <div class="alert alert-warning">Please run the installer first. Make sure MySQL is running in XAMPP.</div>
    <a class="btn btn-success w-100" href="' . htmlspecialchars($install) . '">Open Installer</a>
    </div></div></body></html>';
    exit;
}
