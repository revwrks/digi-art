<?php
/**
 * API: Materi Endpoints (Flexible templates, toggle cards)
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Materi.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$materiModel = new Materi();

switch ($action) {
    case 'get_active':
        // Cek proteksi fitur materi (Hanya Guru dan Murid)
        if (!Auth::canAccessProtectedFeatures()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'locked'  => true,
                'message' => 'Materi pembelajaran hanya dapat diakses oleh akun Siswa dan Guru. Silakan login terlebih dahulu.'
            ]);
            exit;
        }
        $sections = $materiModel->getAllActive();
        $header   = $materiModel->getHeader();
        echo json_encode(['success' => true, 'sections' => $sections, 'header' => $header]);
        break;

    case 'get_header':
        $header = $materiModel->getHeader();
        echo json_encode(['success' => true, 'header' => $header]);
        break;

    case 'update_header':
        if (!Auth::canManageMateri()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Hanya Guru dan Admin yang dapat mengubah informasi materi.']);
            exit;
        }
        $res = $materiModel->updateHeader($input);
        echo json_encode($res);
        break;

    case 'get_all':
        if (!Auth::canManageMateri()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Hanya Guru dan Admin yang dapat mengakses editor materi.']);
            exit;
        }
        $sections = $materiModel->getAllForTeacher();
        $header   = $materiModel->getHeader();
        echo json_encode(['success' => true, 'sections' => $sections, 'header' => $header]);
        break;

    case 'update_section':
        if (!Auth::canManageMateri()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Hanya Guru dan Admin yang dapat mengubah materi.']);
            exit;
        }
        $id = (int)($input['id'] ?? 0);
        $res = $materiModel->updateSection($id, $input);
        echo json_encode($res);
        break;

    case 'add_section':
        if (!Auth::canManageMateri()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Hanya Guru dan Admin yang dapat menambah materi.']);
            exit;
        }
        $res = $materiModel->addSection($input);
        echo json_encode($res);
        break;

    case 'delete_section':
        if (!Auth::canManageMateri()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Hanya Guru dan Admin yang dapat menghapus materi.']);
            exit;
        }
        $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
        $res = $materiModel->deleteSection($id);
        echo json_encode($res);
        break;

    case 'toggle_cards':
        if (!Auth::canManageMateri()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
            exit;
        }
        $id = (int)($input['id'] ?? 0);
        $showCards = !empty($input['show_cards']);
        $res = $materiModel->toggleCards($id, $showCards);
        echo json_encode($res);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi materi tidak valid.']);
        break;
}
