<?php
/**
 * Security Core & Infrastructure Helper
 * Menyediakan perlindungan CSRF, validasi metode HTTP, sanitasi URL/path,
 * rate limiting login, dan HTTP security headers.
 */

class Security {
    private const RATE_LIMIT_MAX_ATTEMPTS = 8;
    private const RATE_LIMIT_WINDOW_SECONDS = 600;

    private static ?string $cachedRawBody = null;

    /**
     * Pastikan sesi PHP aktif sebelum mengakses $_SESSION
     */
    private static function ensureSession(): void {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }

    /**
     * Ambil raw request body (cached per-request)
     */
    private static function getRawBody(): string {
        if (self::$cachedRawBody === null) {
            $raw = file_get_contents('php://input');
            self::$cachedRawBody = ($raw === false) ? '' : $raw;
        }
        return self::$cachedRawBody;
    }

    /**
     * Ambil atau buat token CSRF 32-byte hex dalam sesi aktif
     */
    public static function csrfToken(): string {
        self::ensureSession();
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verifikasi token CSRF dari header HTTP_X_CSRF_TOKEN, $_POST['_csrf'], atau JSON body ['_csrf']
     */
    public static function verifyCsrf(?string $rawJsonBody = null): bool {
        self::ensureSession();
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        if (!is_string($sessionToken) || $sessionToken === '') {
            return false;
        }

        $providedToken = null;
        if (!empty($_SERVER['HTTP_X_CSRF_TOKEN']) && is_string($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $providedToken = $_SERVER['HTTP_X_CSRF_TOKEN'];
        } elseif (!empty($_POST['_csrf']) && is_string($_POST['_csrf'])) {
            $providedToken = $_POST['_csrf'];
        } else {
            $body = $rawJsonBody !== null ? $rawJsonBody : self::getRawBody();
            if ($body !== '') {
                $decoded = json_decode($body, true);
                if (is_array($decoded) && !empty($decoded['_csrf']) && is_string($decoded['_csrf'])) {
                    $providedToken = $decoded['_csrf'];
                }
            }
        }

        if (!is_string($providedToken) || $providedToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $providedToken);
    }

    /**
     * Paksa validasi CSRF; kirim HTTP 403 JSON dan hentikan eksekusi jika tidak valid
     */
    public static function requireCsrf(?string $rawJsonBody = null): void {
        if (!self::verifyCsrf($rawJsonBody)) {
            http_response_code(403);
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'success' => false,
                'message' => 'Sesi keamanan tidak valid. Muat ulang halaman dan coba lagi.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Paksa metode HTTP tertentu; kirim HTTP 405 JSON jika tidak cocok
     */
    public static function requireMethod(string $method): void {
        $current = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($current !== strtoupper($method)) {
            http_response_code(405);
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'success' => false,
                'message' => 'Metode permintaan tidak diizinkan.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Validasi URL aset internal (assets/ atau uploads/) atau URL HTTPS eksternal yang aman.
     * Memblokir javascript:, data:, vbscript:, http://, path traversal (..), backslash, dan null bytes.
     */
    public static function isSafeAssetOrHttpsUrl(string $url): bool {
        if ($url === '') {
            return true;
        }

        if (str_contains($url, "\0") || preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return false;
        }

        if (str_starts_with($url, 'assets/') || str_starts_with($url, 'uploads/')) {
            if (str_contains($url, '\\') || str_contains($url, '..') || str_contains($url, '//')) {
                return false;
            }
            // Pastikan tidak ada skema tersembunyi sebelum slash
            if (preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*:/', $url)) {
                return false;
            }
            return true;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parsed = parse_url($url);
        if (!is_array($parsed)) {
            return false;
        }

        $scheme = strtolower($parsed['scheme'] ?? '');
        return $scheme === 'https' && !empty($parsed['host']);
    }

    /**
     * Validasi path file unggahan karya agar sesuai pola ketat
     */
    public static function isSafeUploadPath(string $path): bool {
        return preg_match('#^uploads/karya_[0-9]+_[a-f0-9]{12}\.(jpg|jpeg|png|webp)$#', $path) === 1;
    }

    /**
     * Hitung kunci rate limit berdasarkan IP klien dan username ternormalisasi
     */
    private static function rateLimitKey(string $username): string {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return hash('sha256', $ip . '|' . strtolower(trim($username)));
    }

    /**
     * Ambil direktori penyimpanan rate limit jika tersedia dan writable
     */
    private static function getRateLimitDir(): ?string {
        $dir = rtrim(sys_get_temp_dir(), '/\\') . '/digiart_rl';
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        if (is_dir($dir) && is_writable($dir)) {
            return $dir;
        }
        return null;
    }

    /**
     * Baca daftar timestamp kegagalan dalam sliding window aktif
     */
    private static function loadRateLimitTimestamps(string $key, int $now): array {
        $dir = self::getRateLimitDir();
        $timestamps = [];

        if ($dir !== null) {
            $file = $dir . '/' . $key . '.json';
            if (is_file($file) && is_readable($file)) {
                $raw = @file_get_contents($file);
                $decoded = is_string($raw) ? json_decode($raw, true) : null;
                if (is_array($decoded)) {
                    $timestamps = $decoded;
                }
            }
        } else {
            self::ensureSession();
            $sessionBucket = $_SESSION['login_rl'][$key] ?? [];
            if (is_array($sessionBucket)) {
                $timestamps = $sessionBucket;
            }
        }

        $valid = [];
        foreach ($timestamps as $ts) {
            if (is_int($ts) || is_numeric($ts)) {
                $t = (int)$ts;
                if (($now - $t) < self::RATE_LIMIT_WINDOW_SECONDS && $t <= $now + 5) {
                    $valid[] = $t;
                }
            }
        }
        sort($valid, SORT_NUMERIC);
        return $valid;
    }

    /**
     * Simpan daftar timestamp kegagalan
     */
    private static function saveRateLimitTimestamps(string $key, array $timestamps): void {
        $dir = self::getRateLimitDir();
        if ($dir !== null) {
            $file = $dir . '/' . $key . '.json';
            @file_put_contents($file, json_encode(array_values($timestamps)), LOCK_EX);
        } else {
            self::ensureSession();
            if (!isset($_SESSION['login_rl']) || !is_array($_SESSION['login_rl'])) {
                $_SESSION['login_rl'] = [];
            }
            $_SESSION['login_rl'][$key] = array_values($timestamps);
        }
    }

    /**
     * Periksa apakah percobaan login diizinkan berdasarkan sliding window
     *
     * @return array{allowed: bool, retry_after: int}
     */
    public static function checkLoginRateLimit(string $username): array {
        $now = time();
        $key = self::rateLimitKey($username);
        $timestamps = self::loadRateLimitTimestamps($key, $now);

        if (count($timestamps) >= self::RATE_LIMIT_MAX_ATTEMPTS) {
            $oldestInWindow = $timestamps[count($timestamps) - self::RATE_LIMIT_MAX_ATTEMPTS];
            $retryAfter = max(1, self::RATE_LIMIT_WINDOW_SECONDS - ($now - $oldestInWindow));
            return [
                'allowed'     => false,
                'retry_after' => $retryAfter
            ];
        }

        return [
            'allowed'     => true,
            'retry_after' => 0
        ];
    }

    /**
     * Catat satu kegagalan login ke sliding window
     */
    public static function recordLoginFailure(string $username): void {
        $now = time();
        $key = self::rateLimitKey($username);
        $timestamps = self::loadRateLimitTimestamps($key, $now);
        $timestamps[] = $now;
        self::saveRateLimitTimestamps($key, $timestamps);
    }

    /**
     * Bersihkan catatan kegagalan login setelah berhasil masuk
     */
    public static function clearLoginFailures(string $username): void {
        $key = self::rateLimitKey($username);
        $dir = self::getRateLimitDir();
        if ($dir !== null) {
            $file = $dir . '/' . $key . '.json';
            if (is_file($file)) {
                @unlink($file);
            }
        }
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['login_rl'][$key])) {
            unset($_SESSION['login_rl'][$key]);
        }
    }

    /**
     * Kirim HTTP security headers standar jika header belum terkirim
     */
    public static function sendSecurityHeaders(): void {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: blob: https: http:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
    }
}
