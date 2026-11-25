<?php
/**
 * Authentication & Authorization (OOP Deep Module)
 * Mengelola sesi, autentikasi berbasis role (admin, guru, murid), dan hak akses fitur.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Activity.php';
require_once __DIR__ . '/Security.php';

class Auth {
    private const DEMO_ACCOUNTS = ['murid', 'guru', 'budi', 'ratna'];

    /**
     * Proses Login dengan rate limiting & regenerasi sesi anti session-fixation
     */
    public static function login(string $username, string $password): array {
        $username = trim($username);
        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Username dan password wajib diisi.'];
        }

        $rateLimit = Security::checkLoginRateLimit($username);
        if (!$rateLimit['allowed']) {
            return [
                'success'     => false,
                'message'     => "Terlalu banyak percobaan login gagal. Silakan coba lagi dalam {$rateLimit['retry_after']} detik.",
                'retry_after' => $rateLimit['retry_after']
            ];
        }

        // Cek jika akun yang digunakan adalah akun bawaan saat dinonaktifkan
        require_once __DIR__ . '/SiteSetting.php';
        if (!SiteSetting::isDemoModeEnabled() && in_array(strtolower($username), self::DEMO_ACCOUNTS, true)) {
            Security::recordLoginFailure($username);
            return [
                'success' => false,
                'message' => 'Akun bawaan ini sedang dinonaktifkan. Silakan gunakan username dan password akun Anda yang telah didaftarkan oleh Guru.'
            ];
        }

        $db = Database::getInstance();
        $user = $db->fetch("SELECT * FROM users WHERE username = :u LIMIT 1", [':u' => $username]);

        if (!$user) {
            Security::recordLoginFailure($username);
            return ['success' => false, 'message' => 'Akun tidak ditemukan. Periksa kembali username Anda.'];
        }

        if (!password_verify($password, $user['password'])) {
            Security::recordLoginFailure($username);
            return ['success' => false, 'message' => 'Password salah.'];
        }

        Security::clearLoginFailures($username);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

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
     * Proses Logout: bersihkan sesi, hapus cookie sesi, dan hancurkan sesi
     */
    public static function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['user'])) {
            Activity::log("Logout: {$_SESSION['user']['full_name']}");
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires'  => time() - 42000,
                    'path'     => $params['path'] ?: '/',
                    'domain'   => $params['domain'] ?? '',
                    'secure'   => (bool)($params['secure'] ?? false),
                    'httponly' => (bool)($params['httponly'] ?? true),
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
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
            if (in_array($username, self::DEMO_ACCOUNTS, true)) {
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
