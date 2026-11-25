<?php
/**
 * Digi-Art Configuration Template
 * Salin file ini menjadi `config/config.php` atau gunakan `.env` / `config/config.local.php`.
 */

if (!defined('DIGIART_APP')) {
    define('DIGIART_APP', true);
}

// Lightweight zero-dependency .env loader
$envFile = dirname(__DIR__) . '/.env';
if (is_file($envFile) && is_readable($envFile)) {
    $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (is_array($envLines)) {
        foreach ($envLines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }
            if (str_starts_with($trimmed, 'export ')) {
                $trimmed = trim(substr($trimmed, 7));
            }
            $eqPos = strpos($trimmed, '=');
            if ($eqPos === false) {
                continue;
            }
            $envKey = trim(substr($trimmed, 0, $eqPos));
            if ($envKey === '') {
                continue;
            }
            $envVal = trim(substr($trimmed, $eqPos + 1));
            $valLen = strlen($envVal);
            if ($valLen >= 2 && (
                ($envVal[0] === '"' && $envVal[$valLen - 1] === '"') ||
                ($envVal[0] === "'" && $envVal[$valLen - 1] === "'")
            )) {
                $envVal = substr($envVal, 1, -1);
            }
            if (getenv($envKey) === false) {
                putenv("{$envKey}={$envVal}");
                $_ENV[$envKey] = $envVal;
            }
        }
    }
}

// Optional local overrides (gitignored) loaded before fallback constants
$localConfigFile = dirname(__DIR__) . '/config/config.local.php';
if (is_file($localConfigFile) && is_readable($localConfigFile)) {
    require_once $localConfigFile;
}

// Secure session configuration BEFORE session_start()
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? '') == 443)
    || (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Zona Waktu Baku Indonesia (WIB / GMT+7)
date_default_timezone_set('Asia/Jakarta');

// Environment Detection & Database Credentials
$rawHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$hostOnly = strtolower(explode(':', $rawHost)[0]);

$isLoopback = in_array($hostOnly, ['localhost', '127.0.0.1', '::1'], true);
$isValidIp = filter_var($hostOnly, FILTER_VALIDATE_IP) !== false;
$isPrivateIp = $isValidIp
    && (filter_var($hostOnly, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false);

$isInfinityFreeDomain = str_ends_with($hostOnly, '.epizy.com')
    || str_ends_with($hostOnly, '.infinityfree.me')
    || str_ends_with($hostOnly, '.rf.gd')
    || str_ends_with($hostOnly, '.great-site.net')
    || str_ends_with($hostOnly, '.42web.io');

$appEnvRaw = strtolower(trim((string)($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: '')));
if ($appEnvRaw === 'production') {
    $isLocal = false;
} elseif ($appEnvRaw === 'local') {
    $isLocal = true;
} else {
    $isLocal = ($isLoopback || $isPrivateIp || PHP_OS_FAMILY === 'Windows' || file_exists('C:/xampp') || file_exists('D:/xampp'))
        && !$isInfinityFreeDomain;
}

if (!defined('APP_ENV')) {
    define('APP_ENV', $isLocal ? 'local' : 'production');
}
$isLocal = (APP_ENV === 'local');

if (!defined('APP_DEBUG')) {
    $appDebugRaw = $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG');
    define('APP_DEBUG', $isLocal && ($appDebugRaw !== '0'));
}

$envStr = static function (string $key, string $default): string {
    $val = $_ENV[$key] ?? getenv($key);
    return ($val !== false && $val !== '') ? (string)$val : $default;
};
$envPass = static function (string $key, string $default): string {
    $val = $_ENV[$key] ?? getenv($key);
    return ($val !== false) ? (string)$val : $default;
};

if ($isLocal) {
    // Pengaturan Localhost & LAN (XAMPP)
    if (!defined('DB_HOST')) define('DB_HOST', $envStr('DB_HOST', '127.0.0.1'));
    if (!defined('DB_NAME')) define('DB_NAME', $envStr('DB_NAME', 'digi_art'));
    if (!defined('DB_USER')) define('DB_USER', $envStr('DB_USER', 'root'));
    if (!defined('DB_PASS')) define('DB_PASS', $envPass('DB_PASS', ''));
    if (!defined('DB_PORT')) define('DB_PORT', $envStr('DB_PORT', '3306'));
} else {
    // Pengaturan Remote / Production Hosting
    if (!defined('DB_HOST')) define('DB_HOST', $envStr('DB_HOST', 'sqlXXX.infinityfree.com'));
    if (!defined('DB_NAME')) define('DB_NAME', $envStr('DB_NAME', 'epiz_XXXX_digiart'));
    if (!defined('DB_USER')) define('DB_USER', $envStr('DB_USER', 'epiz_XXXX'));
    if (!defined('DB_PASS')) define('DB_PASS', $envPass('DB_PASS', 'your_production_password_here'));
    if (!defined('DB_PORT')) define('DB_PORT', $envStr('DB_PORT', '3306'));
}

// App Settings
if (!defined('APP_NAME')) define('APP_NAME', 'Digi-Art');
if (!defined('APP_TAGLINE')) define('APP_TAGLINE', 'Pembelajaran Seni Rupa Kelas VIII');

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
$scriptDir = rtrim($scriptDir, '/');
$scriptDir = (string)preg_replace('#/(api|pages)$#', '', $scriptDir);
if (!defined('BASE_URL')) define('BASE_URL', ($scriptDir === '' ? '' : $scriptDir) . '/');
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', __DIR__ . '/../uploads/');
if (!defined('UPLOAD_URL')) define('UPLOAD_URL', BASE_URL . 'uploads/');

// Google Custom Search API (Opsional untuk fitur Referensi Gambar Nyata)
if (!defined('GOOGLE_API_KEY')) define('GOOGLE_API_KEY', $envStr('GOOGLE_API_KEY', ''));
if (!defined('GOOGLE_SEARCH_CX')) define('GOOGLE_SEARCH_CX', $envStr('GOOGLE_SEARCH_CX', ''));

// Error reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
