<?php
/**
 * API: Pameran Karya Endpoints (Public gallery, submissions, teacher approvals)
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Security.php';
require_once __DIR__ . '/../classes/Karya.php';

Security::sendSecurityHeaders();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$rawBody = file_get_contents('php://input');
if ($rawBody === false) {
    $rawBody = '';
}
$input = json_decode($rawBody, true) ?: $_POST;

$karyaModel = new Karya();

switch ($action) {
    case 'get_approved':
        // Pameran terbuka untuk semua akses (publik)
        $limit = (int)($_GET['limit'] ?? 60);
        $works = $karyaModel->getApproved($limit);
        echo json_encode(['success' => true, 'works' => $works]);
        break;

    case 'get_all_teacher':
        if (!Auth::canManagePameran()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Hanya Guru dan Admin yang dapat mengakses persetujuan pameran.']);
            exit;
        }
        $status = $_GET['status'] ?? null;
        $works = $karyaModel->getAllForTeacher($status);
        echo json_encode(['success' => true, 'works' => $works]);
        break;

    case 'submit':
        Security::requireMethod('POST');
        Security::requireCsrf($rawBody);
        if (!Auth::check()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu untuk mengirim karya.']);
            exit;
        }
        if (!Auth::canAccessProtectedFeatures()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Akun Anda tidak memiliki izin mengirim karya.']);
            exit;
        }
        // Pengguna terautentikasi dapat mengirim karya (otomatis pending)
        $res = $karyaModel->submitWork($input);
        echo json_encode($res);
        break;

    case 'approve':
        Security::requireMethod('POST');
        Security::requireCsrf($rawBody);
        if (!Auth::canManagePameran()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Hanya Guru dan Admin yang dapat menyetujui karya.']);
            exit;
        }
        $id = (int)($input['id'] ?? 0);
        $res = $karyaModel->approve($id);
        echo json_encode($res);
        break;

    case 'reject':
        Security::requireMethod('POST');
        Security::requireCsrf($rawBody);
        if (!Auth::canManagePameran()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Hanya Guru dan Admin yang dapat menolak karya.']);
            exit;
        }
        $id = (int)($input['id'] ?? 0);
        $res = $karyaModel->reject($id);
        echo json_encode($res);
        break;

    case 'delete':
        Security::requireMethod('POST');
        Security::requireCsrf($rawBody);
        if (!Auth::canManagePameran()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Hanya Guru dan Admin yang dapat menghapus karya.']);
            exit;
        }
        $id = (int)($input['id'] ?? 0);
        $res = $karyaModel->delete($id);
        echo json_encode($res);
        break;

    case 'like':
        Security::requireMethod('POST');
        Security::requireCsrf($rawBody);
        $id = (int)($input['id'] ?? 0);
        $res = $karyaModel->like($id);
        echo json_encode($res);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi pameran tidak valid.']);
        break;
}
