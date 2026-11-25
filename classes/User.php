<?php
/**
 * User Model & Management (OOP Deep Module)
 * Mengatur pengelolaan akun pengguna oleh Guru dan Admin dengan aturan hak akses role yang ketat:
 * - Admin dan Guru dapat membuat dan menghapus akun.
 * - Guru TIDAK DAPAT menghapus akun Admin ataupun sesama Guru.
 * - Admin BISA menghapus akun Guru, Murid, dan Admin lain (kecuali akun sendiri).
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Activity.php';

class User {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Buat Akun Baru (Admin dapat membuat admin/guru/murid; Guru hanya dapat membuat murid)
     */
    public function createUser(string $fullName, string $username, string $password, string $role = 'murid', ?string $kelas = null): array {
        if (!Auth::canManageStudents()) {
            return ['success' => false, 'message' => 'Hanya Guru dan Admin yang memiliki akses pembuatan akun.'];
        }

        $currentUser = Auth::user();
        $callerRole  = $currentUser['role'] ?? 'murid';

        $fullName = trim($fullName);
        $username = strtolower(trim($username));
        $role     = strtolower(trim($role));
        $kelas    = trim($kelas ?? '');

        // Validasi role yang diizinkan dibuat oleh pemanggil
        if ($callerRole === 'guru' && $role !== 'murid') {
            return ['success' => false, 'message' => 'Akses ditolak: Guru hanya memiliki hak untuk membuat akun Siswa.'];
        }

        if (!in_array($role, ['admin', 'guru', 'murid'])) {
            $role = 'murid';
        }

        if (empty($fullName) || empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Nama lengkap, username, dan password wajib diisi.'];
        }

        if (preg_match('/^[a-z0-9._-]{3,50}$/', $username) !== 1) {
            return ['success' => false, 'message' => 'Username harus 3–50 karakter (huruf kecil, angka, titik, underscore, atau strip).'];
        }

        if (strlen($password) < 4) {
            return ['success' => false, 'message' => 'Password minimal 4 karakter.'];
        }

        // Cek duplikasi username
        $existing = $this->db->fetch("SELECT id FROM users WHERE username = :u LIMIT 1", [':u' => $username]);
        if ($existing) {
            return ['success' => false, 'message' => "Username '{$username}' sudah terdaftar. Gunakan username lain."];
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $newId = $this->db->insert('users', [
            'username'  => $username,
            'password'  => $hashedPassword,
            'full_name' => $fullName,
            'role'      => $role,
            'kelas'     => $role === 'murid' ? ($kelas ?: 'VIII A') : null
        ]);

        Activity::log("Membuat akun baru: {$fullName} ({$username}) sebagai " . strtoupper($role));

        return [
            'success' => true,
            'message' => "Akun {$role} untuk '{$fullName}' berhasil dibuat!",
            'user_id' => $newId
        ];
    }

    /**
     * Alias pembuatan murid (backward compatibility)
     */
    public function createStudent(string $fullName, string $username, string $password, string $kelas): array {
        return $this->createUser($fullName, $username, $password, 'murid', $kelas);
    }

    /**
     * Hapus Akun Pengguna dari Database
     * Aturan ketat:
     * - Guru TIDAK DAPAT menghapus Admin
     * - Guru TIDAK DAPAT menghapus sesama Guru
     * - Admin BISA menghapus Guru dan Murid
     * - Pengguna tidak dapat menghapus akunnya sendiri
     */
    public function deleteUser(int $targetUserId): array {
        if (!Auth::canManageStudents()) {
            return ['success' => false, 'message' => 'Akses ditolak: Anda tidak memiliki izin mengelola akun.'];
        }

        $currentUser = Auth::user();
        $callerId    = (int)($currentUser['id'] ?? 0);
        $callerRole  = $currentUser['role'] ?? 'murid';

        if ($callerId === $targetUserId) {
            return ['success' => false, 'message' => 'Anda tidak dapat menghapus akun Anda sendiri saat sedang login.'];
        }

        $target = $this->db->fetch("SELECT id, username, full_name, role FROM users WHERE id = :id", [':id' => $targetUserId]);
        if (!$target) {
            return ['success' => false, 'message' => 'Akun pengguna tidak ditemukan di database.'];
        }

        $targetRole = $target['role'];

        // Proteksi 1: Guru tidak dapat menghapus Admin
        if ($targetRole === 'admin' && $callerRole !== 'admin') {
            return [
                'success' => false, 
                'message' => 'Akses ditolak: Guru TIDAK DAPAT menghapus akun Administrator.'
            ];
        }

        // Proteksi 2: Guru tidak dapat menghapus sesama Guru
        if ($targetRole === 'guru' && $callerRole !== 'admin') {
            return [
                'success' => false, 
                'message' => 'Akses ditolak: Guru tidak dapat menghapus sesama akun Guru. Hanya Admin yang berhak menghapus Guru.'
            ];
        }

        // Hapus dari database
        $this->db->delete('users', 'id = :id', [':id' => $targetUserId]);
        Activity::log("Menghapus akun: {$target['full_name']} ({$target['username']} - " . strtoupper($targetRole) . ")");

        return [
            'success' => true,
            'message' => "Akun {$targetRole} '{$target['full_name']}' berhasil dihapus dari database."
        ];
    }

    /**
     * Alias hapus murid (backward compatibility)
     */
    public function deleteStudent(int $studentId): array {
        return $this->deleteUser($studentId);
    }

    /**
     * Ambil daftar semua pengguna dengan penanda hak hapus per pengguna
     */
    public function getAllUsers(): array {
        $currentUser = Auth::user();
        $callerRole  = $currentUser['role'] ?? 'murid';
        $callerId    = (int)($currentUser['id'] ?? 0);

        $users = $this->db->fetchAll(
            "SELECT id, username, full_name, role, kelas, created_at FROM users 
             ORDER BY FIELD(role, 'admin', 'guru', 'murid'), kelas ASC, full_name ASC"
        );

        foreach ($users as &$u) {
            $u['is_self'] = ((int)$u['id'] === $callerId);
            // Hitung izin hapus dan edit untuk pengguna saat ini
            if ($u['is_self']) {
                $u['can_delete'] = false;
                $u['can_edit']   = true; // Baik Admin maupun Guru berhak mengedit data akunnya sendiri
            } elseif ($callerRole === 'admin') {
                $u['can_delete'] = true; // Admin bisa menghapus siapa pun selain diri sendiri
                $u['can_edit']   = true; // Admin bisa mengedit akun siapa pun
            } elseif ($callerRole === 'guru') {
                // Guru HANYA bisa menghapus & mengedit murid, TIDAK BISA mengedit/menghapus admin atau sesama guru
                $u['can_delete'] = ($u['role'] === 'murid');
                $u['can_edit']   = ($u['role'] === 'murid');
            } else {
                $u['can_delete'] = false;
                $u['can_edit']   = false;
            }
        }
        return $users;
    }

    /**
     * Update data akun pengguna (Nama, Username, Kelas, Role, Password)
     * Hak akses:
     * - Admin bisa mengubah akun siapa pun (admin, guru, murid).
     * - Guru HANYA bisa mengubah akun murid (role === 'murid') atau profil sendiri.
     */
    public function updateUser(int $targetUserId, array $data): array {
        if (!Auth::canManageStudents()) {
            return ['success' => false, 'message' => 'Akses ditolak: Anda tidak memiliki izin mengelola akun.'];
        }

        $currentUser = Auth::user();
        $callerRole  = $currentUser['role'] ?? 'murid';
        $callerId    = (int)($currentUser['id'] ?? 0);

        $target = $this->db->fetch("SELECT * FROM users WHERE id = :id", [':id' => $targetUserId]);
        if (!$target) {
            return ['success' => false, 'message' => 'Pengguna tidak ditemukan di database.'];
        }

        // Proteksi Role Guru
        if ($callerRole === 'guru') {
            // Guru boleh mengedit murid, ATAU mengedit akun dirinya sendiri
            if ($target['role'] !== 'murid' && $targetUserId !== $callerId) {
                return [
                    'success' => false,
                    'message' => 'Akses ditolak: Guru hanya diizinkan mengedit akun murid atau akun sendiri, bukan akun Guru lain atau Admin.'
                ];
            }
        }

        $updateFields = [];

        // 1. Nama Lengkap
        if (isset($data['full_name'])) {
            $fullName = trim($data['full_name']);
            if (empty($fullName)) {
                return ['success' => false, 'message' => 'Nama lengkap tidak boleh kosong.'];
            }
            $updateFields['full_name'] = $fullName;
        }

        // 2. Username
        if (isset($data['username'])) {
            $username = strtolower(trim($data['username']));
            if (empty($username)) {
                return ['success' => false, 'message' => 'Username tidak boleh kosong.'];
            }
            if (preg_match('/^[a-z0-9._-]{3,50}$/', $username) !== 1) {
                return ['success' => false, 'message' => 'Username harus 3–50 karakter (huruf kecil, angka, titik, underscore, atau strip).'];
            }
            if ($username !== $target['username']) {
                $dup = $this->db->fetch("SELECT id FROM users WHERE username = :u AND id != :id LIMIT 1", [
                    ':u'  => $username,
                    ':id' => $targetUserId
                ]);
                if ($dup) {
                    return ['success' => false, 'message' => "Username '{$username}' sudah digunakan oleh akun lain."];
                }
            }
            $updateFields['username'] = $username;
        }

        // 3. Password (opsional: hanya jika diisi)
        if (!empty($data['password'])) {
            $password = trim($data['password']);
            if (strlen($password) < 4) {
                return ['success' => false, 'message' => 'Password minimal harus 4 karakter.'];
            }
            $updateFields['password'] = password_hash($password, PASSWORD_BCRYPT);
        }

        // 4. Role (Hanya Admin yang berhak mengubah role akun)
        if (isset($data['role']) && $callerRole === 'admin') {
            $role = strtolower(trim($data['role']));
            if (in_array($role, ['admin', 'guru', 'murid'])) {
                $updateFields['role'] = $role;
            }
        }

        // 5. Kelas (Khusus murid)
        $effectiveRole = $updateFields['role'] ?? $target['role'];
        if ($effectiveRole === 'murid') {
            if (isset($data['kelas'])) {
                $updateFields['kelas'] = trim($data['kelas']) ?: 'VIII A';
            }
        } else {
            $updateFields['kelas'] = null;
        }

        if (empty($updateFields)) {
            return ['success' => true, 'message' => 'Tidak ada perubahan data yang disimpan.'];
        }

        $this->db->update('users', $updateFields, 'id = :id', [':id' => $targetUserId]);

        // Perbarui session jika yang diubah adalah user yang sedang aktif login
        if (($currentUser['id'] ?? 0) === $targetUserId) {
            if (isset($updateFields['username']))  $_SESSION['user']['username']  = $updateFields['username'];
            if (isset($updateFields['full_name'])) $_SESSION['user']['full_name'] = $updateFields['full_name'];
            if (isset($updateFields['role']))      $_SESSION['user']['role']      = $updateFields['role'];
            if (isset($updateFields['kelas']))     $_SESSION['user']['kelas']     = $updateFields['kelas'];
        }

        Activity::log(
            "Mengedit akun: " . ($updateFields['full_name'] ?? $target['full_name']) . " (" . ($updateFields['username'] ?? $target['username']) . ")",
            $currentUser['id'],
            $currentUser['full_name'],
            $currentUser['role']
        );

        $safeUser = array_merge($target, $updateFields);
        unset($safeUser['password']);

        return [
            'success' => true,
            'message' => 'Akun pengguna berhasil diperbarui!',
            'user'    => $safeUser
        ];
    }

    /**
     * Ambil daftar murid saja (untuk filter khusus)
     */
    public function getStudents(): array {
        return $this->db->fetchAll(
            "SELECT id, username, full_name, role, kelas, created_at FROM users WHERE role = 'murid' ORDER BY kelas ASC, full_name ASC"
        );
    }

    /**
     * Murid (atau user sendiri) mengubah username dan password mereka sendiri
     */
    public function updateSelfProfile(int $userId, string $newUsername, ?string $newPassword, ?string $currentPassword = null): array {
        $currentUser = Auth::user();
        if (!$currentUser || ($currentUser['id'] !== $userId && !Auth::hasRole('admin'))) {
            return ['success' => false, 'message' => 'Tidak memiliki izin untuk mengubah profil ini.'];
        }

        $newUsername = strtolower(trim($newUsername));
        if (empty($newUsername)) {
            return ['success' => false, 'message' => 'Username baru tidak boleh kosong.'];
        }
        if (preg_match('/^[a-z0-9._-]{3,50}$/', $newUsername) !== 1) {
            return ['success' => false, 'message' => 'Username harus 3–50 karakter (huruf kecil, angka, titik, underscore, atau strip).'];
        }

        $user = $this->db->fetch("SELECT * FROM users WHERE id = :id", [':id' => $userId]);
        if (!$user) {
            return ['success' => false, 'message' => 'Pengguna tidak ditemukan.'];
        }

        if (!empty($newPassword)) {
            if (strlen($newPassword) < 4) {
                return ['success' => false, 'message' => 'Password baru minimal 4 karakter.'];
            }
            if (!Auth::hasRole('admin') && (empty($currentPassword) || !password_verify($currentPassword, $user['password']))) {
                return ['success' => false, 'message' => 'Password saat ini salah.'];
            }
        }

        if ($newUsername !== $user['username']) {
            $duplicate = $this->db->fetch("SELECT id FROM users WHERE username = :u AND id != :id", [
                ':u'  => $newUsername,
                ':id' => $userId
            ]);
            if ($duplicate) {
                return ['success' => false, 'message' => "Username '{$newUsername}' sudah digunakan oleh pengguna lain."];
            }
        }

        $updateData = ['username' => $newUsername];
        if (!empty($newPassword)) {
            $updateData['password'] = password_hash($newPassword, PASSWORD_BCRYPT);
        }

        $this->db->update('users', $updateData, 'id = :id', [':id' => $userId]);

        if ($currentUser['id'] === $userId) {
            $_SESSION['user']['username'] = $newUsername;
        }

        Activity::log("Mengubah profil akun: {$newUsername}", $userId, $user['full_name'], $user['role']);

        return ['success' => true, 'message' => 'Profil akun berhasil diperbarui!'];
    }
}
