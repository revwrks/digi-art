<?php
$pageTitle = 'Pencarian Referensi Gambar — Digi-Art';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/auth_guard.php';
?>

<div class="page-header">
    <div class="kicker">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        SEARCH ENGINE REFERENSI VISUAL
    </div>
    <h1>Temukan Referensi Gambar Nyata</h1>
    <p>
        Cari gambar ilustrasi dan diagram ilmiah untuk inspirasi karyamu. 
        Hasil dibatasi <strong>25 item terbaik</strong> dan dapat langsung dimuat ke Studio Digital untuk ditiru atau diolah.
    </p>
</div>

<!-- Search Bar & Controls Card -->
<div class="card-enclosed" style="background:var(--surface);padding:24px;margin-bottom:28px;border:1px solid var(--line);border-radius:var(--radius-lg)">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">
        <div style="display:inline-flex;align-items:center;gap:8px;background:var(--accent-emerald-light);color:var(--accent-emerald);padding:5px 12px;border-radius:999px;font-size:12px;font-weight:700;border:1px solid rgba(5,150,105,0.2)">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <span>Filter Aman Siswa (NSFW & Konten Dewasa Dicegah)</span>
            <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--accent-emerald)"></span>
        </div>
        <span style="font-size:12px;color:var(--muted);font-weight:600">Kurikulum Seni Rupa SMP • 100% Edukatif</span>
    </div>

    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
        <div style="flex:1;position:relative;min-width:280px">
            <input type="text" id="refSearchInput" class="field-input" style="padding-left:42px;min-height:46px" placeholder="Contoh: gunung berapi, siklus air, ekosistem hutan, peta nusantara…" value="gunung berapi" aria-label="Kata kunci pencarian gambar">
            <span style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--muted);display:flex">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </span>
        </div>
        <button class="btn" style="min-height:46px;padding:0 24px" onclick="ReferensiApp.search()" aria-label="Cari Gambar">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            Cari Gambar
        </button>
    </div>

    <!-- Quick Search Chips (Materi SMP Lintas Mapel) -->
    <div style="display:flex;gap:8px;margin-top:16px;flex-wrap:wrap;align-items:center">
        <span style="font-size:12px;font-weight:700;color:var(--muted)">Topik Populer:</span>
        <button class="btn secondary small" onclick="ReferensiApp.quickSearch('batik mega mendung')">Batik Mega Mendung</button>
        <button class="btn secondary small" onclick="ReferensiApp.quickSearch('wayang kulit')">Wayang Kulit</button>
        <button class="btn secondary small" onclick="ReferensiApp.quickSearch('motif ukir toraja')">Ukir Toraja</button>
        <button class="btn secondary small" onclick="ReferensiApp.quickSearch('lingkaran warna')">Lingkaran Warna</button>
        <button class="btn secondary small" onclick="ReferensiApp.quickSearch('gunung berapi')">Gunung Berapi (IPS)</button>
        <button class="btn secondary small" onclick="ReferensiApp.quickSearch('siklus air')">Siklus Air (IPA)</button>
        <button class="btn secondary small" onclick="ReferensiApp.quickSearch('tata surya')">Tata Surya</button>
        <button class="btn secondary small" onclick="ReferensiApp.quickSearch('sel tumbuhan')">Sel Tumbuhan</button>
        <button class="btn secondary small" onclick="ReferensiApp.quickSearch('metamorfosis kupu-kupu')">Metamorfosis</button>
        <button class="btn secondary small" onclick="ReferensiApp.quickSearch('candi borobudur')">Candi Borobudur</button>
        <button class="btn secondary small" onclick="ReferensiApp.quickSearch('fabel kancil buaya')">Fabel Cerita Rakyat</button>
    </div>
</div>

<div id="refSearchStatus" style="font-size:13px;color:var(--muted);margin-bottom:18px">
    Menampilkan referensi visual…
</div>

<!-- Results Grid (Max 25 Items) -->
<div id="refResultsGrid" class="ref-grid" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));gap:20px"></div>

<script src="assets/js/referensi.js?v=<?= @filemtime(__DIR__ . '/../assets/js/referensi.js') ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        ReferensiApp.init();
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
