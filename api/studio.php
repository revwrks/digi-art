<?php
/**
 * API: Studio Digital Autosave & Draft Endpoints
 * Menyimpan, mengambil, dan menghapus draf kanvas per akun siswa/guru (Cloud Sync Antar-Perangkat).
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Security.php';
require_once __DIR__ . '/../classes/StudioDraft.php';

Security::sendSecurityHeaders();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$rawBody = file_get_contents('php://input');
if ($rawBody === false) {
    $rawBody = '';
}
$input = json_decode($rawBody, true) ?: $_POST;

$user = Auth::user();

if (!$user || empty($user['id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Anda harus login untuk mengakses autosave akun.'
    ]);
    exit;
}

$userId = (int)$user['id'];
$draftModel = new StudioDraft();

switch ($action) {
    case 'save_draft':
        Security::requireMethod('POST');
        Security::requireCsrf($rawBody);
        $raw = $rawBody;
        if ($raw === '') {
            $raw = $_POST['draft_data'] ?? '';
        }
        if (empty($raw)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Data gambar draf kosong.']);
            exit;
        }

        $res = $draftModel->saveDraft($userId, $raw);
        if (empty($res['success'])) {
            http_response_code(400);
        }
        echo json_encode($res);
        break;

    case 'get_draft':
        $draft = $draftModel->getDraft($userId);
        if ($draft && !empty($draft['draft_data'])) {
            $parsed = json_decode($draft['draft_data'], true);
            echo json_encode([
                'success'    => true,
                'has_draft'  => true,
                'draft'      => $parsed,
                'updated_at' => $draft['updated_at']
            ]);
        } else {
            echo json_encode([
                'success'   => true,
                'has_draft' => false,
                'draft'     => null
            ]);
        }
        break;

    case 'clear_draft':
        Security::requireMethod('POST');
        Security::requireCsrf($rawBody);
        $ok = $draftModel->clearDraft($userId);
        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Draf akun berhasil dibersihkan.' : 'Gagal membersihkan draf akun.'
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action tidak dikenali.']);
        break;
}
