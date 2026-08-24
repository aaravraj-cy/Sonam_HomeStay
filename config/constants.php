<?php
// Sonam Homestay - Settings

function env_value($key, $default = '')
{
    static $envLoaded = false;

    if (!$envLoaded) {
        $envFile = dirname(__DIR__) . '/.env';
        if (is_readable($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#') {
                    continue;
                }

                $parts = explode('=', $line, 2);
                if (count($parts) !== 2) {
                    continue;
                }

                $name = trim($parts[0]);
                $value = trim($parts[1]);
                if ($value !== '' && (
                    ($value[0] === '"' && substr($value, -1) === '"') ||
                    ($value[0] === "'" && substr($value, -1) === "'")
                )) {
                    $value = substr($value, 1, -1);
                }

                if ($name !== '' && getenv($name) === false) {
                    putenv($name . '=' . $value);
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
        $envLoaded = true;
    }

    $value = getenv($key);
    if ($value === false && isset($_ENV[$key])) {
        $value = $_ENV[$key];
    }
    if ($value === false && isset($_SERVER[$key])) {
        $value = $_SERVER[$key];
    }

    return ($value === false || $value === '') ? $default : $value;
}

function base_url_from_env()
{
    $baseUrl = env_value('BASE_URL', '');
    if ($baseUrl === '') {
        $railwayDomain = env_value('RAILWAY_PUBLIC_DOMAIN', '');
        if ($railwayDomain !== '') {
            $baseUrl = 'https://' . preg_replace('#^https?://#', '', $railwayDomain);
        }
    }
    if ($baseUrl === '') {
        $baseUrl = 'http://localhost/Sonam_HomeStay/';
    }
    return rtrim($baseUrl, '/') . '/';
}

define('APP_NAME', 'Sonam Homestay');
define('BASE_URL', base_url_from_env());
define('BASE_PATH', dirname(__DIR__) . '/');

define('DB_HOST', env_value('DB_HOST', 'localhost'));
define('DB_PORT', (int) env_value('DB_PORT', '3306'));
define('DB_NAME', env_value('DB_NAME', 'sonamDB'));
define('DB_USER', env_value('DB_USER', 'root'));
define('DB_PASS', env_value('DB_PASS', ''));
define('DB_SSL_CA', env_value('DB_SSL_CA', ''));
define('DB_SSL_CA_CONTENT', env_value('DB_SSL_CA_CONTENT', ''));

function db_ssl_ca_path()
{
    static $resolvedPath = null;

    if ($resolvedPath !== null) {
        return $resolvedPath;
    }

    if (DB_SSL_CA !== '') {
        $resolvedPath = DB_SSL_CA;
        if ($resolvedPath[0] !== '/' && !preg_match('/^[A-Za-z]:[\/\\\\]/', $resolvedPath)) {
            $resolvedPath = BASE_PATH . ltrim($resolvedPath, '/\\');
        }
        return $resolvedPath;
    }

    if (DB_SSL_CA_CONTENT !== '') {
        $resolvedPath = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'sonam-aiven-ca.pem';
        $caContent = str_replace("\\n", "\n", DB_SSL_CA_CONTENT);
        file_put_contents($resolvedPath, $caContent);
        @chmod($resolvedPath, 0600);
        return $resolvedPath;
    }

    $resolvedPath = '';
    return $resolvedPath;
}

define('MAX_OWNERS', -1); // Limit host registrations (set to 1 for a single-homestay production site, or -1 for unlimited)

define('UPLOAD_PROFILES', BASE_PATH . 'assets/uploads/profiles/');
define('UPLOAD_HOMESTAYS', BASE_PATH . 'assets/uploads/homestays/');
define('UPLOAD_ROOMS', BASE_PATH . 'assets/uploads/rooms/');
define('UPLOAD_GALLERY', BASE_PATH . 'assets/uploads/gallery/');

define('ITEMS_PER_PAGE', 12);

date_default_timezone_set('Asia/Kolkata');
