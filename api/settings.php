<?php
/**
 * API: Site Settings Endpoints (Home decorations management by Admin)
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/SiteSetting.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$settingModel = new SiteSetting();

switch ($action) {
    case 'get_home':
        $settings = $settingModel->getAll();
        echo json_encode(['success' => true, 'settings' => $settings]);
        break;

    case 'update_home':
        if (!Auth::hasRole('admin')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Hanya Administrator yang berhak mengubah hiasan beranda.']);
            exit;
        }
        $res = $settingModel->updateMany($input);
        if (!$res['success']) {
            http_response_code(400);
        }
        echo json_encode($res);
        break;

    case 'toggle_demo_mode':
        if (!Auth::hasRole('admin')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Hanya Administrator yang berhak mengubah mode demo.']);
            exit;
        }
        $enabled = !empty($input['enabled']);
        $ok = $settingModel->setDemoMode($enabled);
        echo json_encode([
            'success'   => $ok,
            'demo_mode' => $enabled,
            'message'   => $enabled 
                ? 'Akses akun bawaan berhasil diaktifkan.' 
                : 'Akses akun bawaan dinonaktifkan. Seluruh akses pembelajaran kini khusus untuk akun resmi yang terdaftar.'
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi pengaturan tidak valid.']);
        break;
}
