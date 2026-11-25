<?php
if (!defined('DIGIART_APP')) { http_response_code(403); exit; }

$pageTitle = 'Digi-Art — Beranda Pembelajaran Seni Rupa';
include __DIR__ . '/../includes/header.php';

require_once __DIR__ . '/../classes/SiteSetting.php';
require_once __DIR__ . '/../classes/Karya.php';

$settingModel = new SiteSetting();
$homeSettings = $settingModel->getAll();

$defaultHomeDesc = 'Media interaktif untuk menguasai Menggambar Ilustrasi untuk Mata Pelajaran Lain. Pelajari konsep visual, temukan referensi edukatif, berkarya di studio kanvas berlapis, dan uji pemahaman lewat kuis cepat.';

$homeKicker    = $homeSettings['home_kicker'] ?? 'PEMBELAJARAN SENI RUPA • KELAS VIII';
$homeTitle     = $homeSettings['home_title'] ?? 'Belajar Seni Visual di';
$homeHighlight = $homeSettings['home_highlight'] ?? 'Digi-Art';
$homeDesc      = !empty(trim((string)($homeSettings['home_description'] ?? '')))
    ? $homeSettings['home_description']
    : $defaultHomeDesc;
$homeTheme     = $homeSettings['home_theme'] ?? 'blue';
$homeArtMode   = $homeSettings['home_art_mode'] ?? 'svg_default';
$homeCustomArt = trim((string)($homeSettings['home_custom_art'] ?? ''));

$themeClassMap = [
    'blue'    => 'hero-theme-blue',
    'crimson' => 'hero-theme-crimson',
    'emerald' => 'hero-theme-emerald',
    'slate'   => 'hero-theme-slate',
    'purple'  => 'hero-theme-slate',
];
$heroThemeClass = $themeClassMap[$homeTheme] ?? 'hero-theme-blue';

$canAccessStudio = Auth::canAccessProtectedFeatures() || SiteSetting::isDemoModeEnabled();
$hasCustomHeroArt = ($homeArtMode === 'custom')
    && $homeCustomArt !== ''
    && Security::isSafeAssetOrHttpsUrl($homeCustomArt);

// Live Pameran Showcase: ambil maksimal 3 karya yang disetujui, fallback ke karya contoh terkurasi
$curatedSamples = [
    [
        'title'        => 'Gunung Merapi Meletus',
        'creator_name' => 'Siti Aisyah',
        'kelas'        => 'VIII A',
        'description'  => 'Materi IPS Geografi',
        'image_path'   => 'assets/img/sample-volcano-art.png',
    ],
    [
        'title'        => 'Siklus Hidrologi & Air Bersih',
        'creator_name' => 'Budi Pratama',
        'kelas'        => 'VIII A',
        'description'  => 'Materi IPA Biologi',
        'image_path'   => 'assets/img/sample-watercycle-art.png',
    ],
    [
        'title'        => 'Fabel Kancil & Buaya',
        'creator_name' => 'Ratna Dewi',
        'kelas'        => 'VIII B',
        'description'  => 'Materi Bahasa Indonesia',
        'image_path'   => 'assets/img/sample-story.svg',
    ],
];

$galleryShowcase = [];
try {
    $karyaModel = new Karya();
    $approvedWorks = $karyaModel->getApproved(3);
    foreach ($approvedWorks as $work) {
        $imgPath = trim((string)($work['image_path'] ?? ''));
        if ($imgPath !== '' && Security::isSafeAssetOrHttpsUrl($imgPath)) {
            $galleryShowcase[] = [
                'title'        => (string)($work['title'] ?? 'Karya Siswa'),
                'creator_name' => (string)($work['creator_name'] ?? 'Siswa'),
                'kelas'        => (string)($work['kelas'] ?? 'VIII'),
                'description'  => trim((string)($work['description'] ?? '')) ?: 'Karya Ilustrasi Siswa',
                'image_path'   => $imgPath,
            ];
        }
        if (count($galleryShowcase) >= 3) {
            break;
        }
    }
} catch (Throwable $e) {
    $galleryShowcase = [];
}

if (count($galleryShowcase) < 3) {
    foreach ($curatedSamples as $sample) {
        if (count($galleryShowcase) >= 3) {
            break;
        }
        $galleryShowcase[] = $sample;
    }
}
?>

<!-- Hero Banner -->
<section class="home-hero <?= htmlspecialchars($heroThemeClass) ?>">
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
            <?php if ($canAccessStudio): ?>
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

    <div class="hero-art-side">
        <div class="hero-art-card">
            <?php if ($hasCustomHeroArt): ?>
                <img src="<?= htmlspecialchars($homeCustomArt) ?>" alt="Ilustrasi sampul Digi-Art" width="1200" height="900" fetchpriority="high" decoding="async">
            <?php else: ?>
                <img src="assets/img/hero-atelier.png" alt="Meja studio ilustrasi digital dengan tablet gambar dan sketsa warna" width="1200" height="900" fetchpriority="high" decoding="async">
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Asymmetric Flagship Showcase: Studio Kanvas Berlapis -->
<section class="home-flagship" aria-labelledby="flagshipStudioHeading">
    <div class="home-flagship-copy">
        <div class="kicker">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
            RUANG BERKARYA UTAMA
        </div>
        <h2 id="flagshipStudioHeading">Studio Kanvas Berlapis</h2>
        <p>
            Susun ilustrasi ilmiah dan cerita secara bertahap dengan kontrol lapisan yang rapi langsung dari browser.
            Setiap goresan tersimpan otomatis ke akunmu sehingga proses menggambar tetap aman di perangkat apa pun.
        </p>
        <?php if ($canAccessStudio): ?>
        <a href="index.php?page=studio" class="btn">
            Buka Studio Digital
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
        </a>
        <?php else: ?>
        <button type="button" class="btn" onclick="App.openLoginModal()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Masuk untuk Membuka Studio
        </button>
        <?php endif; ?>
    </div>

    <div class="home-flagship-frame">
        <ul class="home-flagship-list">
            <li>
                <span class="module-card-icon" style="width:36px;height:36px;margin-bottom:0" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m18 2 4 4-14 14H4v-4L18 2z"/></svg>
                </span>
                <div>
                    <strong>Kuas Stabilizer &amp; Flood Fill</strong>
                    Pilihan kuas presisi, pewarnaan bidang otomatis, dan bentuk geometri siap pakai.
                </div>
            </li>
            <li>
                <span class="module-card-icon" style="width:36px;height:36px;margin-bottom:0" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                </span>
                <div>
                    <strong>Manajemen Layer Terpisah</strong>
                    Pisahkan sketsa, warna dasar, dan referensi agar mudah direvisi sebelum ekspor.
                </div>
            </li>
            <li>
                <span class="module-card-icon" style="width:36px;height:36px;margin-bottom:0" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                </span>
                <div>
                    <strong>Autosave Cloud &amp; Kirim Pameran</strong>
                    Simpan draf otomatis per akun siswa dan unggah karya akhir langsung ke galeri kelas.
                </div>
            </li>
        </ul>
    </div>
</section>

<!-- 3 Linked Learning Module Cards -->
<section class="home-modules-grid" aria-label="Modul Pembelajaran Digi-Art">
    <a href="index.php?page=materi" class="module-card">
        <div class="module-card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10m-10 4h10"/></svg>
        </div>
        <h3>Materi Pembelajaran</h3>
        <p>Pelajari konsep ilustrasi lintas mata pelajaran melalui kartu ringkas, daftar poin, dan langkah berkarya.</p>
        <span class="module-card-link">
            Buka Materi
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
        </span>
    </a>

    <a href="index.php?page=referensi" class="module-card">
        <div class="module-card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        </div>
        <h3>Media Referensi</h3>
        <p>Cari gambar rujukan edukatif yang aman bagi siswa dan muat langsung ke lapisan referensi di studio.</p>
        <span class="module-card-link">
            Cari Referensi
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
        </span>
    </a>

    <a href="index.php?page=kuis" class="module-card">
        <div class="module-card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
        </div>
        <h3>Kuis Interaktif</h3>
        <p>Uji ketepatan dan kecepatanmu memahami materi ilustrasi melalui tantangan poin dan papan peringkat.</p>
        <span class="module-card-link">
            Ikuti Kuis
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
        </span>
    </a>
</section>

<!-- Live Pameran Showcase -->
<section class="home-gallery-section" aria-labelledby="homeGalleryHeading">
    <div class="home-gallery-header">
        <div>
            <div class="kicker">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                RUANG APRESIASI
            </div>
            <h2 id="homeGalleryHeading">Galeri Karya Pameran Kelas</h2>
            <p>Karya gambar ilustrasi siswa kelas VIII yang telah dikurasi dan disetujui oleh Guru Seni Rupa.</p>
        </div>
        <a href="index.php?page=pameran" class="btn secondary small">
            Lihat Semua Karya
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
        </a>
    </div>

    <div class="home-gallery-grid">
        <?php foreach ($galleryShowcase as $item): ?>
        <article class="card-enclosed">
            <a href="index.php?page=pameran" class="card-enclosed-thumb" aria-label="Lihat <?= htmlspecialchars($item['title']) ?> di Pameran">
                <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy" decoding="async">
            </a>
            <div class="card-enclosed-body">
                <span class="role-badge murid" style="align-self:flex-start">
                    <?= htmlspecialchars($item['creator_name']) ?> (<?= htmlspecialchars($item['kelas']) ?>)
                </span>
                <h3 style="margin:8px 0 4px;font-size:15px;color:var(--navy)"><?= htmlspecialchars($item['title']) ?></h3>
                <small style="color:var(--muted)"><?= htmlspecialchars($item['description']) ?></small>
            </div>
        </article>
        <?php endforeach; ?>

        <div class="home-submit-card">
            <div style="width:48px;height:48px;border-radius:12px;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;margin-bottom:12px" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            </div>
            <h3>Punya Karya Sendiri?</h3>
            <p>Unggah gambarmu dan pajang di galeri pameran kelas bersama karya teman-temanmu.</p>
            <a href="index.php?page=pameran" class="btn small">Kirim Karya Sekarang</a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
