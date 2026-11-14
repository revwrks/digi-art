<?php
/**
 * API: User Management (Add users, delete users, list users with strict role hierarchy)
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/User.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$userModel = new User();

switch ($action) {
    case 'list_users':
        if (!Auth::canManageStudents()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Hanya Guru dan Admin yang dapat melihat daftar pengguna.']);
            exit;
        }
        $users = $userModel->getAllUsers();
        $curr = Auth::user();
        echo json_encode([
            'success'         => true, 
            'users'           => $users, 
            'current_role'    => $curr['role'] ?? 'murid',
            'current_user_id' => (int)($curr['id'] ?? 0)
        ]);
        break;

    case 'list_students':
        if (!Auth::canManageStudents()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Hanya Guru dan Admin yang dapat melihat daftar siswa.']);
            exit;
        }
        $students = $userModel->getStudents();
        echo json_encode(['success' => true, 'students' => $students]);
        break;

    case 'create_user':
    case 'create_student':
        if (!Auth::canManageStudents()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Hanya Guru dan Admin yang dapat menambahkan pengguna baru.']);
            exit;
        }
        $fullName = $input['full_name'] ?? '';
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';
        $role     = $input['role'] ?? 'murid';
        $kelas    = $input['kelas'] ?? 'VIII A';

        $res = $userModel->createUser($fullName, $username, $password, $role, $kelas);
        if (!$res['success']) {
            http_response_code(400);
        }
        echo json_encode($res);
        break;

    case 'delete_user':
    case 'delete_student':
        if (!Auth::canManageStudents()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak: Anda tidak memiliki izin.']);
            exit;
        }
        $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
        $res = $userModel->deleteUser($id);
        if (!$res['success']) {
            http_response_code(403);
        }
        echo json_encode($res);
        break;

    case 'update_user':
    case 'edit_user':
        if (!Auth::canManageStudents()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak: Anda tidak memiliki izin.']);
            exit;
        }
        $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
        $data = [
            'full_name' => $input['full_name'] ?? null,
            'username'  => $input['username'] ?? null,
            'password'  => !empty($input['password']) ? $input['password'] : null,
            'role'      => $input['role'] ?? null,
            'kelas'     => $input['kelas'] ?? null
        ];
        $res = $userModel->updateUser($id, $data);
        if (!$res['success']) {
            http_response_code(400);
        }
        echo json_encode($res);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi manajemen pengguna tidak valid.']);
        break;
}
