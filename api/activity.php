<?php
/**
 * API Endpoint: Activity Logs (Guru & Admin Only)
 * Mendukung paginasi dinamis dengan pembatasan maksimal 250 log.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Activity.php';

if (!Auth::canManageMateri()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Hanya Guru atau Admin yang berhak melihat log aktivitas.']);
    exit;
}

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(5, min(250, (int)($_GET['per_page'] ?? 15)));

        $result = Activity::getPaginated($page, $perPage);
        echo json_encode(array_merge(['success' => true], $result));
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi log tidak valid.']);
        break;
}
