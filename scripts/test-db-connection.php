<?php
// CLI-only database connection check. Never prints DB_PASS.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/constants.php';

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

echo "Sonam Homestay database connection test\n";
echo "Host: " . DB_HOST . "\n";
echo "Port: " . DB_PORT . "\n";
echo "Database: " . DB_NAME . "\n";
echo "User: " . DB_USER . "\n";
echo "SSL CA: " . ($sslCaPath !== '' ? $sslCaPath : '(not configured)') . "\n";

if (DB_SSL_CA !== '' && !is_readable($sslCaPath)) {
    fwrite(STDERR, "FAIL: DB_SSL_CA is set but the file is not readable.\n");
    exit(1);
}

$socket = @fsockopen(DB_HOST, DB_PORT, $socketErrno, $socketErrstr, 8);
if ($socket === false) {
    fwrite(STDERR, "TCP reachable: no\n");
    fwrite(STDERR, "TCP error: " . $socketErrno . " " . $socketErrstr . "\n");
    fwrite(STDERR, "FAIL: Could not reach the MySQL host and port from this machine.\n");
    exit(1);
}
fclose($socket);
echo "TCP reachable: yes\n";

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    $selectedDb = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    $sslCipherStmt = $pdo->query("SHOW STATUS LIKE 'Ssl_cipher'");
    $sslCipher = $sslCipherStmt->fetch()['Value'] ?? '';

    echo "Connected: yes\n";
    echo "Selected database: " . ($selectedDb ?: '(none)') . "\n";
    echo "Server version: " . $version . "\n";
    echo "SSL active: " . ($sslCipher !== '' ? 'yes' : 'no') . "\n";
    if ($sslCipher !== '') {
        echo "SSL cipher: " . $sslCipher . "\n";
    }

    if (DB_SSL_CA !== '' && $sslCipher === '') {
        fwrite(STDERR, "FAIL: DB_SSL_CA is configured, but MySQL did not report an active SSL cipher.\n");
        exit(1);
    }
} catch (Throwable $e) {
    $code = method_exists($e, 'getCode') ? (string) $e->getCode() : '';
    if ($code !== '') {
        fwrite(STDERR, "PDO error code: " . $code . "\n");
    }
    fwrite(STDERR, "PDO error: " . $e->getMessage() . "\n");
    fwrite(STDERR, "FAIL: Could not connect. Check host, port, database, user, password, SSL CA, and network access.\n");
    exit(1);
}
