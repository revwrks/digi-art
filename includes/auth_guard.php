<?php
if (!defined('DIGIART_APP')) { http_response_code(403); exit; }
/**
 * Auth Guard
 * Memeriksa hak akses halaman.
 * Materi, Kuis, Referensi, Studio Digital terkunci hanya untuk akun Guru dan Murid.
 * Pameran terbuka untuk publik.
 * Dashboard hanya untuk Guru dan Admin.
 */

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/SiteSetting.php';

$page = $_GET['page'] ?? 'home';
$isDemoMode = SiteSetting::isDemoModeEnabled();

$protectedPages = ['materi', 'kuis', 'referensi', 'studio'];
$teacherPages   = ['dashboard'];

if (in_array($page, $protectedPages) && !Auth::canAccessProtectedFeatures()) {
    ?>
    <div class="locked-card">
        <div class="lock-icon" aria-hidden="true">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <h2>Akses Dibatasi</h2>
        <p>
            Halaman <strong><?= htmlspecialchars(ucfirst($page)) ?></strong> hanya dapat diakses oleh akun
            <strong>Siswa (Murid)</strong> dan <strong>Guru</strong>.
        </p>
        <p style="font-size:13px;color:var(--muted);margin-bottom:20px">
            Silakan masuk dengan akun siswa atau guru Anda yang telah terdaftar untuk melanjutkan pembelajaran.
        </p>
        <button type="button" class="btn" onclick="App.openLoginModal()">Masuk Sekarang</button>
        <?php if ($isDemoMode): ?>
        <div style="margin-top:20px;font-size:12px;color:var(--muted)">
            Contoh Akun Siswa: <code>murid</code> / <code>murid123</code><br>
            Contoh Akun Guru: <code>guru</code> / <code>guru123</code>
        </div>
        <?php endif; ?>
    </div>
    <?php
    include __DIR__ . '/footer.php';
    exit;
}

if (in_array($page, $teacherPages) && !Auth::canManageMateri()) {
    ?>
    <div class="locked-card">
        <div class="lock-icon" aria-hidden="true">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <h2>Khusus Guru &amp; Administrator</h2>
        <p>Halaman Dashboard Pengelolaan hanya dapat diakses oleh akun dengan peran Guru atau Admin.</p>
        <button type="button" class="btn secondary" onclick="window.location.href='index.php?page=home'">Kembali ke Beranda</button>
    </div>
    <?php
    include __DIR__ . '/footer.php';
    exit;
}
