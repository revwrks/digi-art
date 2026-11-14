<?php
/**
 * Digi-Art Configuration
 * Mudah disesuaikan untuk Localhost (XAMPP) maupun Hosting Gratis (InfinityFree)
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
// Otomatis mendeteksi Localhost, Akses Jaringan Lokal (LAN / Wi-Fi), IP Publik XAMPP, maupun Hosting InfinityFree
$rawHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$hostOnly = strtolower(explode(':', $rawHost)[0]);

// 1. Cek loopback / localhost
$isLoopback = in_array($hostOnly, ['localhost', '127.0.0.1', '::1']);

// 2. Cek apakah IP lokal / private network (192.168.x.x, 10.x.x.x, 172.16-31.x.x)
$isPrivateIp = filter_var($hostOnly, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;

// 3. Cek apakah lingkungan fisik server adalah XAMPP / Windows lokal
$isLocalServer = (PHP_OS_FAMILY === 'Windows') 
    || str_contains(strtolower(__DIR__), 'xampp') 
    || str_contains(strtolower(__DIR__), 'htdocs')
    || file_exists('C:/xampp') 
    || file_exists('D:/xampp');

// 4. Cek apakah domain resmi hosting InfinityFree
$isInfinityFreeDomain = str_ends_with($hostOnly, '.epizy.com') 
    || str_ends_with($hostOnly, '.infinityfree.me') 
    || str_ends_with($hostOnly, '.rf.gd')
    || str_ends_with($hostOnly, '.great-site.net')
    || str_ends_with($hostOnly, '.42web.io');

// Keputusan: Jika dijalankan di server XAMPP lokal, LAN, atau localhost, selalu gunakan database lokal XAMPP
$isLocal = ($isLoopback || $isPrivateIp || $isLocalServer) && !$isInfinityFreeDomain;

if ($isLocal) {
    // Pengaturan Localhost & LAN (XAMPP)
    define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1'); // Gunakan 127.0.0.1 untuk koneksi TCP stabil di Windows & LAN
    define('DB_NAME', getenv('DB_NAME') ?: 'digi_art');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') ?: '');
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
} else {
    // Pengaturan Remote / Production Hosting (Disarankan set via Environment Variable atau isi saat deploy di cPanel)
    define('DB_HOST', getenv('DB_HOST') ?: 'sqlXXX.infinityfree.com');
    define('DB_NAME', getenv('DB_NAME') ?: 'epiz_XXXX_digiart');
    define('DB_USER', getenv('DB_USER') ?: 'epiz_XXXX');
    define('DB_PASS', getenv('DB_PASS') ?: 'your_production_db_password');
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
}

// App Settings
define('APP_NAME', 'Digi-Art');
define('APP_TAGLINE', 'Pembelajaran Seni Rupa Kelas VIII');
define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', BASE_URL . 'uploads/');

// Google Images API / Custom Search Engine (Opsional: isi bila punya API Key & CX)
define('GOOGLE_API_KEY', getenv('GOOGLE_API_KEY') ?: '');
define('GOOGLE_SEARCH_CX', getenv('GOOGLE_SEARCH_CX') ?: '');

// Error reporting (sembunyikan display error di produksi agar tidak bocor)
if ($isLocal) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
