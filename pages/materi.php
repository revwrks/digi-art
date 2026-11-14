<?php
require_once __DIR__ . '/../classes/Materi.php';
$materiModel  = new Materi();
$materiHeader = $materiModel->getHeader();

$pageTitle = 'Materi Pembelajaran — ' . htmlspecialchars($materiHeader['title']) . ' | Digi-Art';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/auth_guard.php';
?>

<div class="page-header">
    <div class="kicker" id="materiKicker">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10m-10 4h10"/></svg>
        <?= htmlspecialchars($materiHeader['bab']) ?>
    </div>
    <h1 id="materiTitle"><?= htmlspecialchars($materiHeader['title']) ?></h1>
    <p id="materiDesc"><?= htmlspecialchars($materiHeader['description']) ?></p>
</div>

<!-- Materi Toolbar & Live Search -->
<div class="materi-topbar">
    <div style="font-size:13px;color:var(--muted);display:flex;align-items:center;gap:6px">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--primary)"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        <span>Struktur materi adaptif dan dapat disesuaikan langsung oleh Guru Seni Rupa.</span>
    </div>
    <div class="materi-search-box">
        <input type="text" id="materialSearch" class="materi-search-input" placeholder="Cari topik atau konsep materi…" aria-label="Cari materi">
    </div>
</div>

<!-- Dynamic Materi Container -->
<div id="materiContainer"></div>

<script src="assets/js/materi.js?v=<?= @filemtime(__DIR__ . '/../assets/js/materi.js') ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        MateriApp.init();
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
