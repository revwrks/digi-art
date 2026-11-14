<?php
$pageTitle = 'Galeri Pameran Karya Seni Siswa — Digi-Art';
include __DIR__ . '/../includes/header.php';
?>

<!-- Header Pameran Publik (Terbuka untuk Semua Akses) -->
<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;margin-bottom:24px">
    <div>
        <div class="kicker">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            APRESIASI KARYA SENI RUPA
        </div>
        <h1>Pameran Karya Siswa</h1>
        <p>
            Ruang pameran publik untuk merayakan kreativitas menggambar ilustrasi siswa kelas VIII. 
            Setiap karya telah ditinjau dan disetujui oleh Guru Seni Rupa.
        </p>
    </div>
    <div>
        <button class="btn" onclick="PameranApp.openUploadModal()" aria-label="Kirim Karya Baru">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14m-7-7h14"/></svg>
            Kirim Karya Baru
        </button>
    </div>
</div>

<!-- Upload Callout Banner -->
<div class="card-enclosed" style="padding:24px;text-align:center;margin-bottom:28px;background:var(--surface);border:2px dashed var(--line);align-items:center;justify-content:center">
    <div style="width:48px;height:48px;border-radius:12px;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;margin-bottom:12px">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
    </div>
    <h3 style="font-size:16px;margin-bottom:6px;color:var(--navy)">Ingin Memajang Gambar Buatanmu?</h3>
    <p style="font-size:13px;color:var(--muted);margin-bottom:16px;max-width:540px">
        Unggah file JPG/PNG atau langsung ekspor dari Studio Digital. Gambar akan otomatis dikompresi sebelum dikirim ke Guru Seni Rupa.
    </p>
    <button class="btn secondary small" onclick="PameranApp.openUploadModal()">
        Buka Form Pengiriman Karya
    </button>
</div>

<!-- Works Grid -->
<div id="worksGrid" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(300px, 1fr));gap:24px"></div>

<script src="assets/js/pameran.js?v=<?= @filemtime(__DIR__ . '/../assets/js/pameran.js') ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        PameranApp.init();
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
