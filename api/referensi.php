<?php
/**
 * API: Search Engine Referensi Gambar Edukasi Berbahasa Indonesia
 * - Memanggil ReferensiEngine (OOP)
 * - Menjamin konsistensi hasil (100% identik untuk keyword yang sama via database cache)
 * - Hasil relevan berbahasa Indonesia & materi pelajaran SMP
 * - Dibatasi tepat maksimal 25 item
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Security.php';
require_once __DIR__ . '/../classes/ReferensiEngine.php';

Security::sendSecurityHeaders();

// Proteksi akses: hanya untuk Guru & Murid
if (!Auth::canAccessProtectedFeatures()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'locked'  => true,
        'message' => 'Fitur Pencarian Referensi Gambar hanya dapat diakses oleh Siswa dan Guru. Silakan login terlebih dahulu.'
    ]);
    exit;
}

$query = trim($_GET['q'] ?? $_POST['q'] ?? '');
if (empty($query)) {
    $query = 'gunung berapi';
}

$forceRefresh = !empty($_GET['refresh']);
$limit = 25; // Sesuai syarat: batasi maksimal tepat 25 item

$response = ReferensiEngine::search($query, $limit, $forceRefresh);
echo json_encode($response, JSON_UNESCAPED_UNICODE);
