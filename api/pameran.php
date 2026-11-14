<?php
/**
 * API: Pameran Karya Endpoints (Public gallery, submissions, teacher approvals)
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Karya.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

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
        // Siswa atau pengunjung dapat mengirim karya (otomatis pending)
        $res = $karyaModel->submitWork($input);
        echo json_encode($res);
        break;

    case 'approve':
        $id = (int)($input['id'] ?? 0);
        $res = $karyaModel->approve($id);
        echo json_encode($res);
        break;

    case 'reject':
        $id = (int)($input['id'] ?? 0);
        $res = $karyaModel->reject($id);
        echo json_encode($res);
        break;

    case 'delete':
        $id = (int)($input['id'] ?? 0);
        $res = $karyaModel->delete($id);
        echo json_encode($res);
        break;

    case 'like':
        $id = (int)($input['id'] ?? 0);
        $res = $karyaModel->like($id);
        echo json_encode($res);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi pameran tidak valid.']);
        break;
}
