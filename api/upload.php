<?php
/**
 * API: Image Upload Handler
 * Menerima file gambar yang sudah dikompresi di sisi client (via Canvas)
 * Mendukung Base64 Data URL maupun Multipart Form Data.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Security.php';

Security::sendSecurityHeaders();

$rawBody = file_get_contents('php://input');
if ($rawBody === false) {
    $rawBody = '';
}
$input = json_decode($rawBody, true) ?: $_POST;

Security::requireMethod('POST');
Security::requireCsrf($rawBody);

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu untuk mengunggah gambar.']);
    exit;
}

if (!Auth::canAccessProtectedFeatures()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Akun Anda tidak memiliki izin mengunggah gambar.']);
    exit;
}

$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$fileData = null;

// Kasus 1: Upload via Base64 Data URL (Hasil client-side compressor)
// Jangan pernah mempercayai ekstensi dari header Data URL; verifikasi magic bytes di bawah.
if (!empty($input['image_base64']) && is_string($input['image_base64'])) {
    $rawBase64 = $input['image_base64'];
    $commaPos = strpos($rawBase64, ',');
    if ($commaPos !== false && str_starts_with($rawBase64, 'data:')) {
        $rawBase64 = substr($rawBase64, $commaPos + 1);
    }
    $decoded = base64_decode($rawBase64, true);
    if ($decoded !== false && $decoded !== '') {
        $fileData = $decoded;
    }
}

// Kasus 2: Upload via Standard $_FILES
if (!$fileData && !empty($_FILES['image']['tmp_name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
    $rawFile = file_get_contents($_FILES['image']['tmp_name']);
    if ($rawFile !== false && $rawFile !== '') {
        $fileData = $rawFile;
    }
}

if (!$fileData) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tidak ada data gambar yang valid.']);
    exit;
}

// Batasi ukuran maksimal di sisi server (3MB)
$maxBytes = 3 * 1024 * 1024;
if (strlen($fileData) > $maxBytes) {
    http_response_code(413);
    echo json_encode(['success' => false, 'message' => 'Ukuran file melebihi batas maksimal 3MB. Gunakan compressor.']);
    exit;
}

$allowedMimes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp'
];

// Verifikasi binary magic bytes dengan finfo_buffer dan validasi gambar via getimagesizefromstring
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = $finfo !== false ? finfo_buffer($finfo, $fileData) : false;
if ($finfo !== false) {
    finfo_close($finfo);
}

if (!is_string($mime) || !isset($allowedMimes[$mime])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Format file tidak didukung. Gunakan JPG, PNG, atau WebP.']);
    exit;
}

$imgInfo = @getimagesizefromstring($fileData);
if ($imgInfo === false || !isset($imgInfo[0], $imgInfo[1], $imgInfo['mime'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File gambar tidak valid atau rusak.']);
    exit;
}

$width = (int)$imgInfo[0];
$height = (int)$imgInfo[1];
$imgMime = (string)$imgInfo['mime'];

if ($imgMime !== $mime || $width <= 0 || $height <= 0 || $width > 4096 || $height > 4096) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dimensi atau format gambar tidak valid (maksimal 4096x4096 piksel).']);
    exit;
}

$ext = $allowedMimes[$mime];

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
