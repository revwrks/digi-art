<?php
/**
 * API: Image Upload Handler
 * Menerima file gambar yang sudah dikompresi di sisi client (via Canvas)
 * Mendukung Base64 Data URL maupun Multipart Form Data.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Auth.php';

$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$fileData = null;
$ext = 'jpg';

// Kasus 1: Upload via Base64 Data URL (Hasil client-side compressor)
if (!empty($input['image_base64'])) {
    $rawBase64 = $input['image_base64'];
    if (preg_match('/^data:image\/(\w+);base64,/', $rawBase64, $type)) {
        $rawBase64 = substr($rawBase64, strpos($rawBase64, ',') + 1);
        $ext = strtolower($type[1]);
        if ($ext === 'jpeg') $ext = 'jpg';
        $fileData = base64_decode($rawBase64);
    }
}

// Kasus 2: Upload via Standard $_FILES
if (!$fileData && !empty($_FILES['image']['tmp_name'])) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES['image']['tmp_name']);
    finfo_close($finfo);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowed[$mime])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Format file tidak didukung. Gunakan JPG, PNG, atau WebP.']);
        exit;
    }

    $ext = $allowed[$mime];
    $fileData = file_get_contents($_FILES['image']['tmp_name']);
}

if (!$fileData) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tidak ada data gambar yang diterima.']);
    exit;
}

// Batasi ukuran maksimal di sisi server (misal 5MB, meskipun compressor client-side biasanya <500KB)
if (strlen($fileData) > 5 * 1024 * 1024) {
    http_response_code(413);
    echo json_encode(['success' => false, 'message' => 'Ukuran file terlalu besar untuk hosting gratis. Gunakan compressor.']);
    exit;
}

// Generate nama file aman
$filename = 'karya_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$destPath = $uploadDir . $filename;

if (file_put_contents($destPath, $fileData) === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file gambar ke server.']);
    exit;
}

$relativeUrl = 'uploads/' . $filename;

echo json_encode([
    'success'   => true,
    'message'   => 'Gambar berhasil diunggah!',
    'file_path' => $relativeUrl,
    'file_url'  => $relativeUrl,
    'size_kb'   => round(strlen($fileData) / 1024, 1)
]);
