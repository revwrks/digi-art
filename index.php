<?php
/**
 * Digi-Art: Front Controller / Router
 * Media Pembelajaran Seni Rupa Kelas VIII SMP
 */

define('DIGIART_APP', true);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Security.php';
Security::sendSecurityHeaders();

require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Auth.php';

// Routing Halaman
$page = $_GET['page'] ?? 'home';

// Whitelist halaman yang diizinkan
$allowedPages = [
    'home'      => 'pages/home.php',
    'materi'    => 'pages/materi.php',
    'kuis'      => 'pages/kuis.php',
    'referensi' => 'pages/referensi.php',
    'studio'    => 'pages/studio.php',
    'pameran'   => 'pages/pameran.php',
    'dashboard' => 'pages/dashboard.php'
];

if (!array_key_exists($page, $allowedPages)) {
    $page = 'home';
}

$pageFile = __DIR__ . '/' . $allowedPages[$page];

try {
    if (file_exists($pageFile)) {
        require $pageFile;
    } else {
        http_response_code(404);
        $pageTitle = '404 — Halaman Tidak Ditemukan';
        include __DIR__ . '/includes/header.php';
        echo <<<HTML
        <div style="text-align:center;padding:60px 20px;max-width:540px;margin:40px auto;background:#fff;border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);border:1px solid var(--line);">
            <div style="width:64px;height:64px;margin:0 auto 16px;border-radius:16px;background:#eff6ff;color:#1f4be0;display:flex;align-items:center;justify-content:center;">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </div>
            <h1 style="font-size:24px;margin-bottom:8px;">Halaman Tidak Ditemukan</h1>
            <p style="color:var(--muted);font-size:14px;margin-bottom:24px;">Halaman yang Anda minta tidak tersedia atau telah dipindahkan.</p>
            <a href="index.php?page=home" class="btn" style="display:inline-block;">Kembali ke Beranda</a>
        </div>
HTML;
        include __DIR__ . '/includes/footer.php';
    }
} catch (Throwable $e) {
    error_log(sprintf(
        '[Digi-Art] Uncaught %s in %s:%d — %s',
        get_class($e),
        $e->getFile(),
        $e->getLine(),
        $e->getMessage()
    ));

    http_response_code(500);
    $isDebug = defined('APP_DEBUG') && APP_DEBUG === true;
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) || str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');

    if ($isAjax) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        $payload = [
            'success' => false,
            'message' => 'Terjadi kesalahan sistem saat memproses permintaan.',
        ];
        if ($isDebug) {
            $payload['error'] = $e->getMessage();
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $debugBlock = '';
    if ($isDebug) {
        $errMsg = htmlspecialchars($e->getMessage());
        $errFile = htmlspecialchars(basename($e->getFile()));
        $errLine = (int)$e->getLine();
        $debugBlock = <<<HTML
        <div class="err-box">
            <span style="color:#f87171;">Error:</span> {$errMsg}<br>
            <span style="color:#94a3b8;">Lokasi:</span> {$errFile}:{$errLine}
        </div>
HTML;
    }

    $badgeLabel = $isDebug
        ? 'GANGGUAN PADA HALAMAN ' . htmlspecialchars($page)
        : 'GANGGUAN SISTEM';

    echo <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Terjadi Gangguan — Digi-Art</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        body { background: #f8fafc; font-family: 'Plus Jakarta Sans', system-ui, sans-serif; margin: 0; padding: 20px; display: flex; align-items: center; justify-content: center; min-height: 100vh; box-sizing: border-box; }
        .error-card { background: #ffffff; border-radius: 20px; box-shadow: 0 12px 36px rgba(15,23,42,0.1); max-width: 580px; width: 100%; padding: 32px; border: 1px solid #e2e8f0; }
        .error-badge { display: inline-flex; align-items: center; gap: 8px; background: #fff7ed; color: #c2410c; padding: 6px 14px; border-radius: 999px; font-weight: 800; font-size: 12px; margin-bottom: 16px; border: 1px solid #ffedd5; }
        h1 { margin: 0 0 10px; font-size: 22px; color: #0f172a; }
        p { color: #475569; font-size: 14px; line-height: 1.6; margin: 0 0 20px; }
        .err-box { background: #0f172a; color: #e2e8f0; border-radius: 12px; padding: 14px 16px; font-family: monospace; font-size: 12px; line-height: 1.5; margin-bottom: 24px; overflow-x: auto; }
        .actions { display: flex; gap: 12px; flex-wrap: wrap; }
        .btn-retry { background: #1f4be0; color: #fff; padding: 12px 24px; border-radius: 12px; font-weight: 700; border: none; cursor: pointer; font-size: 14px; flex: 1; text-align: center; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
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
            <span>{$badgeLabel}</span>
        </div>
        <h1>Terjadi Kesalahan Sistem</h1>
        <p>Halaman sedang mengalami kendala teknis sementara. Data Anda tetap aman. Silakan muat ulang atau kembali ke beranda.</p>
        {$debugBlock}
        <div class="actions">
            <button class="btn-retry" onclick="window.location.reload()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                </svg>
                <span>Muat Ulang Halaman</span>
            </button>
            <a href="index.php?page=home" class="btn-home">Ke Beranda</a>
        </div>
    </div>
</body>
</html>
HTML;
    exit;
}
