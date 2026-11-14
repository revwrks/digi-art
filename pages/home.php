<?php
$pageTitle = 'Digi-Art — Beranda Pembelajaran Seni Rupa';
include __DIR__ . '/../includes/header.php';

require_once __DIR__ . '/../classes/SiteSetting.php';
$settingModel = new SiteSetting();
$homeSettings = $settingModel->getAll();

$homeKicker    = $homeSettings['home_kicker'] ?? 'PEMBELAJARAN SENI RUPA • KELAS VIII';
$homeTitle     = $homeSettings['home_title'] ?? 'Belajar Seni Visual di';
$homeHighlight = $homeSettings['home_highlight'] ?? 'Digi-Art';
$homeDesc      = $homeSettings['home_description'] ?? 'Media interaktif untuk menguasai Menggambar Ilustrasi untuk Mata Pelajaran Lain. Jelajahi materi berformat fleksibel, temukan referensi gambar nyata, berkarya di studio digital ibis paint, dan ikuti kuis seru bergaya Kahoot!';
$homeBadge     = $homeSettings['home_badge'] ?? 'Kurikulum Seni Rupa SMP • Kelas VIII';
$homeTheme     = $homeSettings['home_theme'] ?? 'blue';
$homeArtMode   = $homeSettings['home_art_mode'] ?? 'svg_default';
$homeCustomArt = $homeSettings['home_custom_art'] ?? '';

// Penyesuaian tema gradien hiasan hero
$themeGradients = [
    'blue'    => 'linear-gradient(135deg, #ffffff 0%, #f0f7ff 100%)',
    'crimson' => 'linear-gradient(135deg, #ffffff 0%, #fff1f2 100%)',
    'emerald' => 'linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%)',
    'purple'  => 'linear-gradient(135deg, #ffffff 0%, #f5f3ff 100%)'
];
$heroBg = $themeGradients[$homeTheme] ?? $themeGradients['blue'];
?>

<!-- Announcement Badge / Hiasan Atas -->
<?php if (!empty($homeBadge)): ?>
<div style="margin-bottom:16px;display:flex;align-items:center;gap:8px">
    <span class="role-badge murid" style="font-size:12px;padding:5px 12px;border-radius:var(--radius-sm)">
        <?= htmlspecialchars($homeBadge) ?>
    </span>
</div>
<?php endif; ?>

<!-- Hero Banner -->
<section class="hero-box" style="background:<?= $heroBg ?>">
    <div class="hero-content">
        <div class="kicker">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m18 2 4 4-14 14H4v-4L18 2z"/></svg>
            <?= htmlspecialchars($homeKicker) ?>
        </div>
        <h1>
            <?= htmlspecialchars($homeTitle) ?> <span class="highlight"><?= htmlspecialchars($homeHighlight) ?></span>
        </h1>
        <p><?= nl2br(htmlspecialchars($homeDesc)) ?></p>
        <div class="hero-actions">
            <?php if (Auth::canAccessProtectedFeatures() || SiteSetting::isDemoModeEnabled()): ?>
            <a href="index.php?page=materi" class="btn">
                Mulai Belajar Materi
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
            </a>
            <?php else: ?>
            <button type="button" class="btn" onclick="App.openLoginModal()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Masuk Akun Belajar
            </button>
            <?php endif; ?>
            <a href="index.php?page=pameran" class="btn secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                Galeri Pameran
            </a>
        </div>
    </div>

    <!-- Hiasan Sisi Kanan Hero -->
    <div class="hero-art-side">
        <div class="hero-art-card">
            <?php if ($homeArtMode === 'custom' && !empty($homeCustomArt)): ?>
                <img src="<?= htmlspecialchars($homeCustomArt) ?>" alt="Hiasan Beranda" style="width:100%;height:100%;object-fit:cover;border-radius:8px">
            <?php else: ?>
                <!-- Ilustrasi Seni Rupa Edukasi Default -->
                <svg viewBox="0 0 500 400" xmlns="http://www.w3.org/2000/svg" aria-label="Ilustrasi Lanskap Seni Rupa">
                    <defs>
                        <linearGradient id="skyGradHome" x1="0" x2="0" y1="0" y2="1">
                            <stop stop-color="#91dfff" />
                            <stop offset="1" stop-color="#fff1b5" />
                        </linearGradient>
                    </defs>
                    <rect width="500" height="400" fill="url(#skyGradHome)" />
                    <circle cx="390" cy="75" r="45" fill="#ffd34e" />
                    <path d="M0 265L115 125 185 210 270 100 430 275Z" fill="#3c77c7" />
                    <path d="M115 125l-30 38 28-7 23 25 25-31zM270 100l-35 44 31-10 25 25 27-37z" fill="#fff" />
                    <path d="M0 305 Q110 250 220 310T500 290V400H0Z" fill="#55b978" />
                    <path d="M160 400 Q230 320 280 245 Q325 315 400 400Z" fill="#72c6ee" opacity=".9" />
                    <g stroke="#183d6e" stroke-width="5" fill="none">
                        <path d="M50 325q10-35 35 0m10 0q10-35 35 0" />
                        <path d="M420 300q8-30 25 0m8 0q8-30 25 0" />
                    </g>
                    <rect x="325" y="275" width="110" height="70" rx="10" fill="#fff" stroke="#193c75" stroke-width="4" />
                    <path d="M338 330l25-28 20 18 18-22 22 32z" fill="#ff765b" />
                    <circle cx="362" cy="297" r="7" fill="#ffd34e" />
                    <path d="M80 90l-50 15M95 120l-60 30M440 145l40-20" stroke="#ff6a4d" stroke-width="12" stroke-linecap="round" />
                </svg>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Feature Showcase Grid -->
<section class="features-grid">
    <div class="feature-box">
        <div class="feature-icon-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10m-10 4h10"/></svg>
        </div>
        <h3>Materi Adaptif</h3>
        <p>Struktur materi terstruktur dengan pilihan kartu konsep, daftar poin, prosa, dan tahapan langkah berkarya.</p>
    </div>
    <div class="feature-box">
        <div class="feature-icon-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
        </div>
        <h3>Kuis Interaktif</h3>
        <p>Evaluasi pemahaman seru dengan skor kecepatan, streak bonus, dan papan peringkat siswa real-time.</p>
    </div>
    <div class="feature-box">
        <div class="feature-icon-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        </div>
        <h3>Media Referensi</h3>
        <p>Search engine gambar visual untuk menemukan inspirasi ilmiah dan langsung membukanya di Studio Digital.</p>
    </div>
    <div class="feature-box">
        <div class="feature-icon-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m18 2 4 4-14 14H4v-4L18 2z"/></svg>
        </div>
        <h3>Studio Digital</h3>
        <p>Kanvas gambar lengkap dengan brush bervariasi, ember cat (flood fill), layer manager, dan cloud autosave.</p>
    </div>
</section>

<!-- Pameran Highlight -->
<section class="card-enclosed" style="padding:32px;margin-bottom:30px;background:var(--surface)">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px">
        <div>
            <div class="kicker">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                RUANG APRESIASI
            </div>
            <h2>Galeri Karya Pameran Kelas</h2>
            <p style="color:var(--muted);font-size:14px">Karya gambar ilustrasi siswa kelas VIII yang telah disetujui Guru.</p>
        </div>
        <a href="index.php?page=pameran" class="btn secondary small">
            Lihat Semua Karya
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
        </a>
    </div>

    <div class="features-grid" style="margin-bottom:0">
        <article class="card-enclosed">
            <div class="card-enclosed-thumb">
                <img src="assets/img/sample-volcano.svg" alt="Gunung Merapi Meletus" loading="lazy">
            </div>
            <div class="card-enclosed-body">
                <span class="role-badge murid" style="align-self:flex-start">Siti Aisyah (VIII A)</span>
                <h4 style="margin:8px 0 2px;font-size:15px;color:var(--navy)">Gunung Merapi Meletus</h4>
                <small style="color:var(--muted)">Materi IPS Geografi</small>
            </div>
        </article>

        <article class="card-enclosed">
            <div class="card-enclosed-thumb">
                <img src="assets/img/sample-watercycle.svg" alt="Siklus Hidrologi" loading="lazy">
            </div>
            <div class="card-enclosed-body">
                <span class="role-badge murid" style="align-self:flex-start">Budi Pratama (VIII A)</span>
                <h4 style="margin:8px 0 2px;font-size:15px;color:var(--navy)">Siklus Hidrologi & Air Bersih</h4>
                <small style="color:var(--muted)">Materi IPA Biologi</small>
            </div>
        </article>

        <article class="card-enclosed">
            <div class="card-enclosed-thumb">
                <img src="assets/img/sample-story.svg" alt="Fabel Nusantara" loading="lazy">
            </div>
            <div class="card-enclosed-body">
                <span class="role-badge murid" style="align-self:flex-start">Ratna Dewi (VIII B)</span>
                <h4 style="margin:8px 0 2px;font-size:15px;color:var(--navy)">Fabel Kancil & Buaya</h4>
                <small style="color:var(--muted)">Materi Bahasa Indonesia</small>
            </div>
        </article>

        <div style="display:flex;flex-direction:column;justify-content:center;align-items:center;background:var(--surface-subtle);border:2px dashed var(--line);border-radius:var(--radius-md);padding:24px;text-align:center">
            <div style="width:48px;height:48px;border-radius:12px;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;margin-bottom:12px">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            </div>
            <h4 style="color:var(--navy);margin-bottom:4px">Punya Karya Sendiri?</h4>
            <p style="font-size:12px;color:var(--muted);margin-bottom:14px">Unggah gambarmu dan pajang di galeri pameran kelas.</p>
            <a href="index.php?page=pameran" class="btn small">Kirim Karya Sekarang</a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
