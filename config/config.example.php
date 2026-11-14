<?php
/**
 * Digi-Art Configuration Template
 * Salin file ini menjadi `config/config.php` dan sesuaikan kredensial database Anda.
 */

if (!defined('DIGIART_APP')) {
    define('DIGIART_APP', true);
}

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Zona Waktu Baku Indonesia (WIB / GMT+7)
date_default_timezone_set('Asia/Jakarta');

// Environment Detection & Database Credentials
$rawHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$hostOnly = strtolower(explode(':', $rawHost)[0]);

$isLoopback = in_array($hostOnly, ['localhost', '127.0.0.1', '::1']);
$isPrivateIp = filter_var($hostOnly, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
$isLocalServer = (PHP_OS_FAMILY === 'Windows') 
    || str_contains(strtolower(__DIR__), 'xampp') 
    || str_contains(strtolower(__DIR__), 'htdocs')
    || file_exists('C:/xampp') 
    || file_exists('D:/xampp');

$isInfinityFreeDomain = str_ends_with($hostOnly, '.epizy.com') 
    || str_ends_with($hostOnly, '.infinityfree.me') 
    || str_ends_with($hostOnly, '.rf.gd')
    || str_ends_with($hostOnly, '.great-site.net')
    || str_ends_with($hostOnly, '.42web.io');

$isLocal = ($isLoopback || $isPrivateIp || $isLocalServer) && !$isInfinityFreeDomain;

if ($isLocal) {
    // Pengaturan Localhost & LAN (XAMPP)
    define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
    define('DB_NAME', getenv('DB_NAME') ?: 'digi_art');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') ?: '');
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
} else {
    // Pengaturan Remote / Production Hosting
    define('DB_HOST', getenv('DB_HOST') ?: 'sqlXXX.infinityfree.com');
    define('DB_NAME', getenv('DB_NAME') ?: 'epiz_XXXX_digiart');
    define('DB_USER', getenv('DB_USER') ?: 'epiz_XXXX');
    define('DB_PASS', getenv('DB_PASS') ?: 'your_production_password_here');
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
}

// App Settings
define('APP_NAME', 'Digi-Art');
define('APP_TAGLINE', 'Pembelajaran Seni Rupa Kelas VIII');
define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', BASE_URL . 'uploads/');

// Google Custom Search API (Opsional untuk fitur Referensi Gambar Nyata)
define('GOOGLE_API_KEY', getenv('GOOGLE_API_KEY') ?: '');
define('GOOGLE_SEARCH_CX', getenv('GOOGLE_SEARCH_CX') ?: '');

// Error reporting
if ($isLocal) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
