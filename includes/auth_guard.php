<?php
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
        <div class="lock-icon">🔒</div>
        <h2>Akses Dibatasi</h2>
        <p>
            Halaman <strong><?= htmlspecialchars(ucfirst($page)) ?></strong> hanya dapat diakses oleh akun 
            <strong>Siswa (Murid)</strong> dan <strong>Guru</strong>.
        </p>
        <p style="font-size:13px;color:var(--muted);margin-bottom:20px">
            Silakan masuk dengan akun siswa atau guru Anda yang telah terdaftar untuk melanjutkan pembelajaran.
        </p>
        <button class="btn" onclick="App.openLoginModal()">Masuk Sekarang</button>
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
        <div class="lock-icon">🛡️</div>
        <h2>Khusus Guru & Administrator</h2>
        <p>Halaman Dashboard Pengelolaan hanya dapat diakses oleh akun dengan peran Guru atau Admin.</p>
        <button class="btn secondary" onclick="window.location.href='index.php?page=home'">Kembali ke Beranda</button>
    </div>
    <?php
    include __DIR__ . '/footer.php';
    exit;
}
