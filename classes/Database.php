<?php
/**
 * Database Connection & Helper (OOP PDO Singleton)
 * Deep module: interface ringkas, menangani koneksi, prepared statements, dan error handling.
 */

require_once __DIR__ . '/../config/config.php';

class Database {
    private static ?Database $instance = null;
    private ?PDO $pdo = null;

    private function __construct() {
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 5,
        ];

        // Percobaan koneksi 1
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";port=" . DB_PORT . ";charset=utf8mb4";
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            @$this->pdo->exec("SET time_zone = '+07:00'");
        } catch (PDOException $e1) {
            // Percobaan koneksi 2 (Fallback cerdas: Jika host 127.0.0.1 gagal, coba localhost, atau sebaliknya di XAMPP)
            $fallbackHost = (DB_HOST === '127.0.0.1') ? 'localhost' : '127.0.0.1';
            try {
                $dsnFallback = "mysql:host=" . $fallbackHost . ";dbname=" . DB_NAME . ";port=" . DB_PORT . ";charset=utf8mb4";
                $this->pdo = new PDO($dsnFallback, DB_USER, DB_PASS, $options);
                @$this->pdo->exec("SET time_zone = '+07:00'");
            } catch (PDOException $e2) {
                $this->handleConnectionFailure($e1);
            }
        }
    }

    /**
     * Tangani kegagalan koneksi database secara profesional (API JSON atau Halaman Visual Diagnostik)
     */
    private function handleConnectionFailure(PDOException $e): void {
        error_log('[Digi-Art] Database connection failed: ' . $e->getMessage());

        $isDebug = defined('APP_DEBUG') && APP_DEBUG === true;
        $isApi = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            || str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')
            || str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/api/');

        if ($isApi) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            http_response_code(500);
            if ($isDebug) {
                echo json_encode([
                    'success'        => false,
                    'database_error' => true,
                    'message'        => 'Gagal terhubung ke database MySQL. Pastikan service MySQL XAMPP sedang berjalan.',
                    'error'          => $e->getMessage(),
                    'host'           => DB_HOST,
                    'database'       => DB_NAME
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'success'        => false,
                    'database_error' => true,
                    'message'        => 'Layanan database sedang tidak tersedia. Silakan coba beberapa saat lagi.'
                ], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }

        // Tampilkan halaman gangguan atau diagnostik sesuai APP_DEBUG
        $this->renderDiagnosticErrorScreen($e);
        exit;
    }

    /**
     * Render Halaman Gangguan / Diagnostik Database
     */
    private function renderDiagnosticErrorScreen(PDOException $e): void {
        http_response_code(500);
        $isDebug = defined('APP_DEBUG') && APP_DEBUG === true;

        if (!$isDebug) {
            echo <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Layanan Tidak Tersedia — Digi-Art</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        body { background: #f8fafc; font-family: 'Plus Jakarta Sans', system-ui, sans-serif; margin: 0; padding: 20px; display: flex; align-items: center; justify-content: center; min-height: 100vh; box-sizing: border-box; }
        .error-card { background: #ffffff; border-radius: 20px; box-shadow: 0 12px 36px rgba(15,23,42,0.1); max-width: 560px; width: 100%; padding: 32px; border: 1px solid #e2e8f0; }
        .error-badge { display: inline-flex; align-items: center; gap: 8px; background: #fef2f2; color: #dc2626; padding: 6px 14px; border-radius: 999px; font-weight: 800; font-size: 12px; margin-bottom: 16px; border: 1px solid #fee2e2; }
        h1 { margin: 0 0 10px; font-size: 22px; color: #0f172a; }
        p { color: #475569; font-size: 14px; line-height: 1.6; margin: 0 0 24px; }
        .actions { display: flex; gap: 12px; flex-wrap: wrap; }
        .btn-retry { background: #1f4be0; color: #fff; padding: 12px 24px; border-radius: 12px; font-weight: 700; text-decoration: none; border: none; cursor: pointer; font-size: 14px; flex: 1; text-align: center; }
        .btn-retry:hover { background: #1539b5; }
        .btn-home { background: #e2e8f0; color: #1e293b; padding: 12px 20px; border-radius: 12px; font-weight: 700; text-decoration: none; font-size: 14px; text-align: center; }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-badge">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            <span>GANGGUAN LAYANAN</span>
        </div>
        <h1>Layanan Database Sedang Tidak Tersedia</h1>
        <p>Layanan database sedang tidak tersedia. Silakan coba beberapa saat lagi.</p>
        <div class="actions">
            <button class="btn-retry" onclick="window.location.reload()">Coba Muat Ulang</button>
            <a href="index.php?page=home" class="btn-home">Beranda</a>
        </div>
    </div>
</body>
</html>
HTML;
            return;
        }

        $host = htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost');
        $dbHost = htmlspecialchars(DB_HOST);
        $dbName = htmlspecialchars(DB_NAME);
        $dbUser = htmlspecialchars(DB_USER);
        $errMsg = htmlspecialchars($e->getMessage());
        $clientIp = htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

        echo <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Koneksi Database Gagal — Digi-Art</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        body { background: #f8fafc; font-family: 'Plus Jakarta Sans', system-ui, sans-serif; margin: 0; padding: 20px; display: flex; align-items: center; justify-content: center; min-height: 100vh; box-sizing: border-box; }
        .error-card { background: #ffffff; border-radius: 20px; box-shadow: 0 12px 36px rgba(15,23,42,0.1); max-width: 620px; width: 100%; padding: 32px; border: 1px solid #e2e8f0; }
        .error-badge { display: inline-flex; align-items: center; gap: 8px; background: #fef2f2; color: #dc2626; padding: 6px 14px; border-radius: 999px; font-weight: 800; font-size: 12px; margin-bottom: 16px; border: 1px solid #fee2e2; }
        h1 { margin: 0 0 10px; font-size: 24px; color: #0f172a; }
        p { color: #475569; font-size: 14px; line-height: 1.6; margin: 0 0 20px; }
        .diag-box { background: #0f172a; color: #e2e8f0; border-radius: 12px; padding: 16px; font-family: monospace; font-size: 12px; line-height: 1.6; margin-bottom: 24px; overflow-x: auto; }
        .diag-box span.key { color: #94a3b8; }
        .diag-box span.val { color: #38bdf8; font-weight: bold; }
        .diag-box span.err { color: #f87171; }
        .step-list { background: #f1f5f9; border-radius: 14px; padding: 18px 20px; margin-bottom: 24px; }
        .step-list h4 { margin: 0 0 10px; font-size: 14px; color: #1e293b; }
        .step-list ol { margin: 0; padding-left: 20px; font-size: 13px; color: #334155; line-height: 1.65; }
        .actions { display: flex; gap: 12px; flex-wrap: wrap; }
        .btn-retry { background: #1f4be0; color: #fff; padding: 12px 24px; border-radius: 12px; font-weight: 700; text-decoration: none; border: none; cursor: pointer; font-size: 14px; flex: 1; text-align: center; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-retry:hover { background: #1539b5; }
        .btn-home { background: #e2e8f0; color: #1e293b; padding: 12px 20px; border-radius: 12px; font-weight: 700; text-decoration: none; font-size: 14px; text-align: center; }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-badge">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            <span>GANGGUAN KONEKSI DATABASE</span>
        </div>
        <h1>Tidak Dapat Terhubung ke MySQL</h1>
        <p>Aplikasi Digi-Art tidak dapat mengakses database pada server saat ini. Berikut rincian diagnosa koneksi Anda:</p>

        <div class="diag-box">
            <div><span class="key">Akses Host:</span> <span class="val">{$host}</span> (IP Client: {$clientIp})</div>
            <div><span class="key">Target Database:</span> <span class="val">{$dbHost}:3306</span> &rarr; DB: <span class="val">{$dbName}</span></div>
            <div><span class="key">User Database:</span> <span class="val">{$dbUser}</span></div>
            <div style="margin-top:8px"><span class="key">Pesan Error PDO:</span></div>
            <div class="err">&bull; {$errMsg}</div>
        </div>

        <div class="step-list">
            <h4>Solusi Cepat (XAMPP / LAN / Wi-Fi):</h4>
            <ol>
                <li>Buka <strong>XAMPP Control Panel</strong> pada komputer server, klik <strong>Start</strong> pada modul <strong>MySQL</strong> hingga indikator berwarna hijau (Port 3306).</li>
                <li>Buka <a href="http://localhost/phpmyadmin" target="_blank" style="color:#1f4be0;font-weight:bold;">phpMyAdmin</a>, pastikan database <strong>{$dbName}</strong> sudah dibuat dan di-import dari file <code>schema.sql</code>.</li>
                <li>Jika mengakses dari HP/iPhone di jaringan Wi-Fi, pastikan Firewall Windows mengizinkan Apache (Port 80/443).</li>
            </ol>
        </div>

        <div class="actions">
            <button class="btn-retry" onclick="window.location.reload()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                </svg>
                <span>Coba Muat Ulang</span>
            </button>
            <a href="index.php?page=home" class="btn-home">Beranda</a>
        </div>
    </div>
</body>
</html>
HTML;
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->pdo;
    }

    /**
     * Eksekusi prepared statement dengan explicit PDO type binding
     */
    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $param = is_int($key) ? ($key + 1) : $key;
            if (is_int($value)) {
                $type = PDO::PARAM_INT;
            } elseif (is_bool($value)) {
                $type = PDO::PARAM_BOOL;
            } elseif ($value === null) {
                $type = PDO::PARAM_NULL;
            } else {
                $type = PDO::PARAM_STR;
            }
            $stmt->bindValue($param, $value, $type);
        }
        $stmt->execute();
        return $stmt;
    }

    /**
     * Ambil 1 baris
     */
    public function fetch(string $sql, array $params = []): ?array {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    /**
     * Ambil semua baris
     */
    public function fetchAll(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Helper Insert
     */
    public function insert(string $table, array $data): int {
        $fields = array_keys($data);
        $placeholders = array_map(fn($f) => ":$f", $fields);
        $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $this->query($sql, $data);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Helper Update
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int {
        $fields = array_keys($data);
        $setPart = implode(', ', array_map(fn($f) => "{$f} = :{$f}", $fields));
        $sql = "UPDATE {$table} SET {$setPart} WHERE {$where}";
        $stmt = $this->query($sql, array_merge($data, $whereParams));
        return $stmt->rowCount();
    }

    /**
     * Helper Delete
     */
    public function delete(string $table, string $where, array $params = []): int {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function lastInsertId(): int {
        return (int)$this->pdo->lastInsertId();
    }
}
