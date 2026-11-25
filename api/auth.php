<?php
/**
 * API: Authentication Endpoints
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Security.php';
require_once __DIR__ . '/../classes/User.php';

Security::sendSecurityHeaders();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$rawBody = file_get_contents('php://input');
if ($rawBody === false) {
    $rawBody = '';
}
$input = json_decode($rawBody, true) ?: $_POST;

switch ($action) {
    case 'login':
        Security::requireMethod('POST');
        Security::requireCsrf($rawBody);
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';
        $res = Auth::login($username, $password);
        if (!empty($res['success'])) {
            $res['csrf_token'] = Security::csrfToken();
        }
        echo json_encode($res);
        break;

    case 'logout':
        Security::requireMethod('POST');
        Security::requireCsrf($rawBody);
        Auth::logout();
        echo json_encode(['success' => true, 'message' => 'Berhasil logout.']);
        break;

    case 'status':
        $user = Auth::user();
        echo json_encode([
            'logged_in' => Auth::check(),
            'user'      => $user,
            'role'      => $user['role'] ?? 'tamu',
            'can_access_protected' => Auth::canAccessProtectedFeatures(),
            'can_manage_students'  => Auth::canManageStudents(),
            'can_manage_pameran'   => Auth::canManagePameran(),
            'can_manage_materi'    => Auth::canManageMateri(),
            'csrf_token'           => Security::csrfToken()
        ]);
        break;

    case 'update_profile':
        Security::requireMethod('POST');
        Security::requireCsrf($rawBody);
        if (!Auth::check()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu.']);
            exit;
        }
        $userModel = new User();
        $userId = Auth::user()['id'];
        $newUsername = $input['username'] ?? '';
        $newPassword = $input['new_password'] ?? null;
        $currentPassword = $input['current_password'] ?? null;

        $res = $userModel->updateSelfProfile($userId, $newUsername, $newPassword, $currentPassword);
        echo json_encode($res);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi autentikasi tidak valid.']);
        break;
}
