<?php
/**
 * Authentication & Authorization (OOP Deep Module)
 * Mengelola sesi, autentikasi berbasis role (admin, guru, murid), dan hak akses fitur.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Activity.php';

class Auth {
    /**
     * Proses Login
     */
    public static function login(string $username, string $password): array {
        $username = trim($username);
        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Username dan password wajib diisi.'];
        }

        // Cek jika akun yang digunakan adalah akun bawaan saat dinonaktifkan
        require_once __DIR__ . '/SiteSetting.php';
        $demoAccounts = ['murid', 'guru', 'budi', 'ratna'];
        if (!SiteSetting::isDemoModeEnabled() && in_array(strtolower($username), $demoAccounts, true)) {
            return [
                'success' => false,
                'message' => 'Akun bawaan ini sedang dinonaktifkan. Silakan gunakan username dan password akun Anda yang telah didaftarkan oleh Guru.'
            ];
        }

        $db = Database::getInstance();
        $user = $db->fetch("SELECT * FROM users WHERE username = :u LIMIT 1", [':u' => $username]);

        if (!$user) {
            return ['success' => false, 'message' => 'Akun tidak ditemukan. Periksa kembali username Anda.'];
        }

        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Password salah.'];
        }

        // Simpan sesi aman
        unset($user['password']);
        $_SESSION['user'] = $user;

        Activity::log("Login {$user['role']}: {$user['full_name']}", $user['id'], $user['full_name'], $user['role']);

        return [
            'success' => true,
            'message' => "Selamat datang, {$user['full_name']}!",
            'user'    => $user
        ];
    }

    /**
     * Proses Logout
     */
    public static function logout(): void {
        if (isset($_SESSION['user'])) {
            Activity::log("Logout: {$_SESSION['user']['full_name']}");
            unset($_SESSION['user']);
        }
        session_destroy();
    }

    /**
     * Cek status login
     */
    public static function check(): bool {
        return !empty($_SESSION['user']);
    }

    /**
     * Ambil data user yang sedang login
     */
    public static function user(): ?array {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Cek apakah user memiliki salah satu dari role yang ditentukan
     */
    public static function hasRole(string|array $roles): bool {
        if (!self::check()) return false;
        $userRole = $_SESSION['user']['role'] ?? '';
        if (is_array($roles)) {
            return in_array($userRole, $roles, true);
        }
        return $userRole === $roles;
    }

    /**
     * Akses fitur terlindungi (Materi, Kuis, Referensi, Studio Digital):
     * Terbuka untuk 'guru', 'murid', dan 'admin'
     */
    public static function canAccessProtectedFeatures(): bool {
        if (!self::hasRole(['admin', 'guru', 'murid'])) {
            return false;
        }

        // Jika Mode Demo nonaktif, pastikan sesi tidak sedang menggunakan akun demo
        require_once __DIR__ . '/SiteSetting.php';
        if (!SiteSetting::isDemoModeEnabled()) {
            $user = self::user();
            $username = strtolower($user['username'] ?? '');
            $demoAccounts = ['murid', 'guru', 'budi', 'ratna'];
            if (in_array($username, $demoAccounts, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Hak akses menambah murid: Guru dan Admin saja
     */
    public static function canManageStudents(): bool {
        return self::hasRole(['admin', 'guru']);
    }

    /**
     * Hak akses approve karya pameran: Guru dan Admin saja
     */
    public static function canManagePameran(): bool {
        return self::hasRole(['admin', 'guru']);
    }

    /**
     * Hak akses kelola materi: Guru dan Admin saja
     */
    public static function canManageMateri(): bool {
        return self::hasRole(['admin', 'guru']);
    }
}
