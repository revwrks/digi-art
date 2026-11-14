<?php
$pageTitle = 'Studio Digital — Digi-Art';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/auth_guard.php';

require_once __DIR__ . '/../classes/StudioDraft.php';
$serverDraft = null;
if (!empty($currentUser['id'])) {
    $draftModel = new StudioDraft();
    $draftRow = $draftModel->getDraft((int)$currentUser['id']);
    if ($draftRow && !empty($draftRow['draft_data'])) {
        $serverDraft = json_decode($draftRow['draft_data'], true);
        if ($serverDraft) {
            $serverDraft['updated_at'] = $draftRow['updated_at'];
        }
    }
}
?>

<div class="page-header" style="margin-bottom:14px">
    <div class="kicker">STUDIO SENI RUPA DIGITAL</div>
    <h1>Studio Digital Menggambar</h1>
    <p>Kanvas kreatif dengan navigasi Flying Bar, Zoom & Pan responsif, autosave anti-hilang per akun (Cloud Sync), dan pengatur urutan lapisan (layers).</p>
</div>

<div class="studio-wrapper">
    <!-- Slim Top Header Bar -->
    <header class="studio-top-bar">
        <div class="studio-title-block">
            <h1>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18.37 2.63 14 7l-1.59-1.59a2 2 0 0 0-2.82 0L8 7l9 9 1.59-1.59a2 2 0 0 0 0-2.82L17 10l4.37-4.37a2.12 2.12 0 1 0-3-3Z"/><path d="M9 8c-2 3-4 3.5-7 4l8 8c.5-3 1-5 4-7"/><path d="M14.5 17.5 4.5 7.5"/></svg>
                Studio Digital
            </h1>
            <span class="autosave-pill" id="autosaveStatus">
                <span class="dot-indicator"></span> Draf: <?= htmlspecialchars($currentUser['username'] ?? 'Tamu') ?>
            </span>
        </div>

        <div class="studio-header-actions">
            <button class="btn-header-action" onclick="studio?.undo()" title="Batal (Undo)" aria-label="Undo">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg>
                <span class="hide-mobile">Undo</span>
            </button>
            <button class="btn-header-action" onclick="studio?.redo()" title="Ulangi (Redo)" aria-label="Redo">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3l3 2.7"/></svg>
                <span class="hide-mobile">Redo</span>
            </button>
            <button class="btn-header-action" onclick="studio?.toggleGrid()" id="btnStudioGrid" title="Bantuan Grid Komposisi 3x3 & 8x8" aria-label="Grid Komposisi">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M3 15h18"/><path d="M9 3v18"/><path d="M15 3v18"/></svg>
                <span id="gridStatusText">Grid</span>
            </button>
            <button class="btn-header-action" onclick="toggleStudioFullscreen()" id="btnStudioFullscreen" title="Layar Penuh (Fullscreen)" aria-label="Layar Penuh">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
                <span class="hide-mobile">Fullscreen</span>
            </button>
            <label class="btn-header-action" title="Impor Gambar dari Komputer / HP" aria-label="Impor Gambar">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                <span class="hide-mobile">Muat</span>
                <input type="file" accept="image/*" style="display:none" onchange="handleStudioImageUpload(event)">
            </label>
            <div class="download-dropdown-wrap" style="position:relative;display:inline-flex">
                <button class="btn-header-action" id="btnStudioDownload" onclick="toggleDownloadMenu()" title="Unduh Hasil Karya" aria-label="Menu Unduh">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    <span class="hide-mobile">Unduh</span> ▾
                </button>
                <div id="downloadMenuDropdown" class="studio-sub-dropdown" style="display:none;position:absolute;top:100%;right:0;margin-top:4px">
                    <button type="button" class="btn-dropdown-item" onclick="studio?.downloadPNG();toggleDownloadMenu(false)">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        Format PNG <span style="font-size:10px;color:var(--muted);margin-left:auto">Transparan</span>
                    </button>
                    <button type="button" class="btn-dropdown-item" onclick="studio?.downloadJPG();toggleDownloadMenu(false)">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                        Format JPG <span style="font-size:10px;color:var(--muted);margin-left:auto">Kompresi</span>
                    </button>
                </div>
            </div>
            <button class="btn-header-action primary" id="btnExportPameran" onclick="exportToPameranModal()" title="Kirim Hasil Karya ke Pameran Karya Siswa">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                Kirim Pameran
            </button>
            <button class="btn-header-action danger" id="btnNewCanvas" onclick="openNewCanvasModal()" title="Mulai Lembar Kanvas Baru">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                <span class="hide-mobile">Baru</span>
            </button>
        </div>
    </header>

    <!-- Main Workspace with Flying Bar & Full Canvas Area -->
    <div class="studio-workspace" id="studioWorkspace">
        
        <!-- FLYING TOOLBAR (Floating Action Bar on Left/Right) -->
        <aside class="flying-toolbar" id="flyingToolbar" aria-label="Toolbar Studio Menggambar">
            <!-- Brush Tools (Lucide Vector Icons) -->
            <button class="btn-fly-tool active" id="toolBtnSimple" onclick="setStudioTool('brush-simple')" title="Simple Pen (Garis Tegas)" aria-label="Simple Pen">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m18 2 4 4-14 14H4v-4L18 2z"/></svg>
            </button>
            <button class="btn-fly-tool" id="toolBtnSketch" onclick="setStudioTool('brush-sketch')" title="Sketch Pencil (Pensil Tekstur Arsir)" aria-label="Sketch Pencil">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="M2 2l7.586 7.586"/><circle cx="11" cy="11" r="2"/></svg>
            </button>
            <button class="btn-fly-tool" id="toolBtnSoft" onclick="setStudioTool('brush-soft')" title="Soft Brush (Kuas Cat Air Gradasi)" aria-label="Soft Brush">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9.06 11.9 8.07-8.06a2.85 2.85 0 1 1 4.03 4.03l-8.06 8.08"/><path d="M7.07 14.94c-1.66 0-3 1.34-3 3 0 1.31-1.16 2-2.07 2 1.66 0 3-1.34 3-3 0-1.31 1.16-2 2.07-2z"/></svg>
            </button>
            <button class="btn-fly-tool" id="toolBtnAirbrush" onclick="setStudioTool('brush-airbrush')" title="Airbrush (Gradasi Halus Transparan)" aria-label="Airbrush">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v3m-6 3 2.5 2.5m11-2.5-2.5 2.5M4 14h3m10 0h3M7 19.5 9.5 17m5 0 2.5 2.5"/></svg>
            </button>
            <button class="btn-fly-tool" id="toolBtnEraser" onclick="setStudioTool('eraser')" title="Penghapus (Eraser)" aria-label="Penghapus">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m7 21-4.3-4.3c-1-1-1-2.5 0-3.4l9.6-9.6c1-1 2.5-1 3.4 0l5.6 5.6c1 1 1 2.5 0 3.4L13 21"/><path d="M22 21H7"/><path d="m5 11 9 9"/></svg>
            </button>
            <button class="btn-fly-tool" id="toolBtnBucket" onclick="setStudioTool('bucket')" title="Bucket Fill (Ember Cat Otomatis)" aria-label="Bucket Fill">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m19 11-8-8-8.6 8.6a2 2 0 0 0 0 2.8l5.2 5.2c.8.8 2 .8 2.8 0L19 11Z"/><path d="m5 2 5 5"/><path d="M2 13h15"/><path d="M22 20a2 2 0 1 1-4 0c0-1.6 1.7-2.4 2-4 .3 1.6 2 2.4 2 4Z"/></svg>
            </button>
            <button class="btn-fly-tool" id="toolBtnShape" onclick="toggleFlyout('flyoutShapes')" title="Bentuk Geometri Otomatis" aria-label="Bentuk Geometri">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><rect x="8" y="8" width="8" height="8" rx="1"/></svg>
            </button>
            <button class="btn-fly-tool" id="toolBtnText" onclick="openTextModal()" title="Bubuhkan Teks ke Kanvas" aria-label="Teks Kanvas">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>
            </button>
            <button class="btn-fly-tool" id="toolBtnMove" onclick="setStudioTool('move')" title="Geser Isi Lapisan (Move Layer)" aria-label="Geser Lapisan">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="5 9 2 12 5 15"/><polyline points="9 5 12 2 15 5"/><polyline points="15 19 12 22 9 19"/><polyline points="19 9 22 12 19 15"/><line x1="2" y1="12" x2="22" y2="12"/><line x1="12" y1="2" x2="12" y2="22"/></svg>
            </button>
            <button class="btn-fly-tool" id="toolBtnReopenText" onclick="studio?.reopenMovableText()" title="Pindah / Geser Ulang Teks Terakhir" aria-label="Pindah Teks Terakhir">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
            </button>

            <div class="flying-separator"></div>

            <!-- Popover Toggles: Color, Brush Settings, Layers -->
            <button class="btn-fly-tool" id="btnFlyColor" onclick="toggleFlyout('flyoutColor')" title="Pilih Warna & Palet" aria-label="Palet Warna">
                <span class="fly-color-swatch" id="flyColorPreview" style="background:#2563eb"></span>
            </button>
            <button class="btn-fly-tool" id="btnFlySettings" onclick="toggleFlyout('flyoutSettings')" title="Pengaturan Kuas" aria-label="Pengaturan Kuas">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
            <button class="btn-fly-tool" id="btnFlyLayers" onclick="toggleFlyout('flyoutLayers')" title="Manajemen Lapisan (Layers)" aria-label="Manajemen Layer">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                <span class="fly-badge" id="flyLayerBadge">2</span>
            </button>

            <div class="flying-separator"></div>

            <!-- Dock Toggle (Flipped to Left or Right) -->
            <button class="btn-fly-tool" onclick="toggleFlyingDock()" title="Pindahkan Toolbar ke Kiri/Kanan" aria-label="Pindah Sisi Toolbar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 7h12m0 0-4-4m4 4-4 4m0 6H4m0 0 4 4m-4-4 4-4"/></svg>
            </button>
        </aside>

        <!-- FLYOUT POPOVER PANELS -->
        
        <!-- 1. Color Picker Flyout -->
        <div class="flyout-panel" id="flyoutColor">
            <div class="flyout-header">
                <strong>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>
                    Palet Warna
                </strong>
                <button class="btn-flyout-close" onclick="closeAllFlyouts()" aria-label="Tutup Panel">×</button>
            </div>
            <div class="color-picker-box">
                <input type="color" id="nativeColorInput" class="native-color-input" value="#2563eb" onchange="setStudioColor(this.value)" title="Pilih Warna Bebas">
                <div>
                    <strong id="currentColorHex" style="font-size:13px;display:block;color:var(--navy,#0f172a)">#2563eb</strong>
                    <small style="color:var(--muted,#64748b);font-size:11px">Klik lingkaran untuk pemilih warna penuh</small>
                </div>
            </div>

            <div class="color-swatches-grid" style="margin-top:12px">
                <button class="color-dot" style="background:#2563eb" onclick="setStudioColor('#2563eb')" aria-label="Biru Cobalt"></button>
                <button class="color-dot" style="background:#06b6d4" onclick="setStudioColor('#06b6d4')" aria-label="Cyan"></button>
                <button class="color-dot" style="background:#10b981" onclick="setStudioColor('#10b981')" aria-label="Hijau"></button>
                <button class="color-dot" style="background:#f59e0b" onclick="setStudioColor('#f59e0b')" aria-label="Kuning Emas"></button>
                <button class="color-dot" style="background:#f43f5e" onclick="setStudioColor('#f43f5e')" aria-label="Rose Coral"></button>
                <button class="color-dot" style="background:#8b5cf6" onclick="setStudioColor('#8b5cf6')" aria-label="Ungu"></button>
                <button class="color-dot" style="background:#854d0e" onclick="setStudioColor('#854d0e')" aria-label="Cokelat"></button>
                <button class="color-dot" style="background:#0f172a" onclick="setStudioColor('#0f172a')" aria-label="Hitam Pekat"></button>
                <button class="color-dot" style="background:#64748b" onclick="setStudioColor('#64748b')" aria-label="Abu-abu"></button>
                <button class="color-dot" style="background:#ffffff" onclick="setStudioColor('#ffffff')" aria-label="Putih"></button>
                <button class="color-dot" style="background:#fed7aa" onclick="setStudioColor('#fed7aa')" aria-label="Warna Kulit Terang"></button>
                <button class="color-dot" style="background:#c2410c" onclick="setStudioColor('#c2410c')" aria-label="Terracotta"></button>
            </div>
        </div>

        <!-- 2. Brush Settings Flyout -->
        <div class="flyout-panel" id="flyoutSettings">
            <div class="flyout-header">
                <strong>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    Kuas: <span id="currentToolName" style="color:var(--primary,#2563eb);font-weight:700">Simple Pen</span>
                </strong>
                <button class="btn-flyout-close" onclick="closeAllFlyouts()" aria-label="Tutup Panel">×</button>
            </div>

            <div class="slider-group">
                <label>
                    <span>Ukuran Kuas</span>
                    <span id="brushSizeVal">8 px</span>
                </label>
                <input type="range" class="studio-range" id="brushSizeRange" min="1" max="60" value="8" oninput="setBrushSize(this.value)">
                <div class="quick-size-presets">
                    <button class="btn-size-preset" onclick="setBrushSize(2)">2px</button>
                    <button class="btn-size-preset" onclick="setBrushSize(8)">8px</button>
                    <button class="btn-size-preset" onclick="setBrushSize(16)">16px</button>
                    <button class="btn-size-preset" onclick="setBrushSize(32)">32px</button>
                </div>
            </div>

            <div class="slider-group" style="margin-top:14px">
                <label>
                    <span>Transparansi (Opacity)</span>
                    <span id="brushOpacityVal">100%</span>
                </label>
                <input type="range" class="studio-range" id="brushOpacityRange" min="5" max="100" value="100" oninput="setBrushOpacity(this.value)">
            </div>

            <div class="slider-group" style="margin-top:14px">
                <label>
                    <span>Stabilizer Goresan (Penghalus Jari)</span>
                    <span id="brushStabilizerVal">4 (Sedang)</span>
                </label>
                <input type="range" class="studio-range" id="brushStabilizerRange" min="0" max="10" value="4" oninput="setBrushStabilizer(this.value)">
                <small style="color:var(--muted,#64748b);font-size:11px;display:block;margin-top:4px">
                    Membantu menggambar dengan jari di HP agar goresan garis tetap mulus dan rapi.
                </small>
            </div>
        </div>

        <!-- 3. Layers Flyout (With Reordering & Drag & Drop) -->
        <div class="flyout-panel" id="flyoutLayers">
            <div class="flyout-header">
                <strong>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                    Lapisan (Layers)
                </strong>
                <button class="btn-flyout-close" onclick="closeAllFlyouts()" aria-label="Tutup Panel">×</button>
            </div>

            <div class="layers-action-bar">
                <button class="btn-layer-cmd" onclick="studio?.addLayer()" title="Tambah Layer Baru">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14m-7-7h14"/></svg>
                    + Layer
                </button>
                <button class="btn-layer-cmd" onclick="studio?.activateMoveLayerTool()" title="Aktifkan Mode Geser Layer">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="5 9 2 12 5 15"/><polyline points="9 5 12 2 15 5"/><polyline points="15 19 12 22 9 19"/><polyline points="19 9 22 12 19 15"/><line x1="2" y1="12" x2="22" y2="12"/><line x1="12" y1="2" x2="12" y2="22"/></svg>
                    Geser
                </button>
                <button class="btn-layer-cmd" onclick="studio?.mergeDownActiveLayer()" title="Gabung ke Bawah">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12m0 0 4-4m-4 4-4-4m-7 8h18"/></svg>
                    Gabung
                </button>
                <button class="btn-layer-cmd" onclick="studio?.deleteActiveLayer()" title="Hapus Layer Aktif" style="color:#ef4444">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18m-2 0v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    Hapus
                </button>
            </div>

            <div class="slider-group" style="margin-bottom:10px">
                <label>
                    <span>Opacity Layer Aktif</span>
                </label>
                <input type="range" class="studio-range" id="layerOpacity" min="0" max="100" value="100" oninput="studio?.setLayerOpacity(this.value)">
            </div>

            <div class="layers-list" id="layersList"></div>
            <small style="display:block;margin-top:6px;color:var(--muted,#64748b);font-size:10px;text-align:center">
                Gunakan tombol ▲/▼ atau seret (drag) untuk ubah urutan.
            </small>
        </div>

        <!-- 4. Shape Selection Flyout -->
        <div class="flyout-panel" id="flyoutShapes">
            <div class="flyout-header">
                <strong>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><rect x="8" y="8" width="8" height="8" rx="1"/></svg>
                    Bentuk Geometri
                </strong>
                <button class="btn-flyout-close" onclick="closeAllFlyouts()" aria-label="Tutup Panel">×</button>
            </div>
            <p style="font-size:11px;color:var(--muted,#64748b);margin:0 0 12px 0">
                Pilih bentuk lalu sentuh & tarik (drag) di kanvas untuk menggambar secara otomatis.
            </p>

            <!-- Grid Pilihan Bentuk dengan SVG -->
            <div class="shapes-picker-grid">
                <button type="button" class="btn-shape-pick active" id="shapePick_circle" onclick="setStudioShape('circle')" title="Lingkaran / Elips">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/></svg>
                    <span class="shape-label">Lingkaran</span>
                </button>
                <button type="button" class="btn-shape-pick" id="shapePick_rectangle" onclick="setStudioShape('rectangle')" title="Kotak / Persegi Panjang">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                    <span class="shape-label">Kotak</span>
                </button>
                <button type="button" class="btn-shape-pick" id="shapePick_triangle" onclick="setStudioShape('triangle')" title="Segitiga">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 3 22 21 2 21 12 3"/></svg>
                    <span class="shape-label">Segitiga</span>
                </button>
                <button type="button" class="btn-shape-pick" id="shapePick_arrow" onclick="setStudioShape('arrow')" title="Tanda Panah">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    <span class="shape-label">Panah</span>
                </button>
                <button type="button" class="btn-shape-pick" id="shapePick_line" onclick="setStudioShape('line')" title="Garis Lurus">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="20" x2="20" y2="4"/></svg>
                    <span class="shape-label">Garis</span>
                </button>
                <button type="button" class="btn-shape-pick" id="shapePick_star" onclick="setStudioShape('star')" title="Bintang">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <span class="shape-label">Bintang</span>
                </button>
                <button type="button" class="btn-shape-pick" id="shapePick_heart" onclick="setStudioShape('heart')" title="Simbol Hati">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                    <span class="shape-label">Hati</span>
                </button>
            </div>

            <!-- Pilihan Gaya (Garis Tepi / Isi Penuh / Keduanya) -->
            <div style="margin-top:14px;border-top:1px solid var(--line,#e2e8f0);padding-top:12px">
                <label style="font-size:12px;font-weight:700;display:block;margin-bottom:6px;color:var(--navy,#0f172a)">Gaya Bentuk:</label>
                <div class="shape-style-toggle">
                    <button type="button" class="btn-shape-style active" id="btnShapeStyleStroke" onclick="setShapeStyle('stroke')">Garis Tepi</button>
                    <button type="button" class="btn-shape-style" id="btnShapeStyleFill" onclick="setShapeStyle('fill')">Isi Penuh</button>
                    <button type="button" class="btn-shape-style" id="btnShapeStyleBoth" onclick="setShapeStyle('both')">Keduanya</button>
                </div>
            </div>
        </div>

        <!-- CENTER: INTERACTIVE CANVAS ZONE WITH ZOOM & PAN -->
        <section class="studio-canvas-zone" id="studioCanvasZone" onclick="handleCanvasZoneClick(event)">
            <div class="canvas-transform-wrapper" id="canvasTransformWrapper">
                <canvas id="studioDisplayCanvas" width="960" height="600"></canvas>
            </div>
        </section>

        <!-- FLOATING ZOOM & PAN BAR (BOTTOM-RIGHT) -->
        <div class="floating-zoom-bar">
            <button class="btn-zoom" onclick="studio?.zoomOut()" title="Perkecil Kanvas (Zoom Out)" aria-label="Zoom Out">−</button>
            <span class="zoom-level-text" id="zoomLevelDisplay" onclick="studio?.resetZoom()" title="Klik untuk Reset ke Pas Layar">100%</span>
            <button class="btn-zoom" onclick="studio?.zoomIn()" title="Perbesar Kanvas (Zoom In)" aria-label="Zoom In">+</button>
            <button class="btn-zoom btn-zoom-reset" onclick="studio?.resetZoom()" title="Sesuaikan Ukuran Pas Layar">Fit</button>
        </div>
    </div>
</div>

<!-- Modal Teks Studio Digital -->
<div class="modal-overlay" id="textToolModal">
    <div class="modal">
        <button class="btn-close" onclick="closeTextModal()" aria-label="Tutup Modal">×</button>
        <h2>Tambahkan Teks ke Kanvas</h2>
        <p class="modal-sub">Atur ukuran, gaya, jenis font, dan warna teks untuk ilustrasimu.</p>

        <div class="form-group">
            <label for="textInputString">Isi Teks:</label>
            <input type="text" id="textInputString" class="field-input" placeholder="Contoh: Kawah Gunung Berapi" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="textFontSize">Ukuran Huruf (px):</label>
                <input type="number" id="textFontSize" class="field-input" value="32" min="12" max="120">
            </div>
            <div class="form-group">
                <label for="textFontFamily">Jenis Font:</label>
                <select id="textFontFamily" class="field-select">
                    <option value="Outfit, sans-serif">Outfit (Modern Bold)</option>
                    <option value="Plus Jakarta Sans, sans-serif">Jakarta Sans (Bersih)</option>
                    <option value="Georgia, serif">Georgia (Klasik/Buku)</option>
                    <option value="Impact, sans-serif">Impact (Tebal Poster)</option>
                    <option value="Courier New, monospace">Courier (Mesin Ketik)</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="textFontStyle">Gaya Teks:</label>
                <select id="textFontStyle" class="field-select">
                    <option value="normal">Normal</option>
                    <option value="bold" selected>Tebal (Bold)</option>
                    <option value="italic">Miring (Italic)</option>
                    <option value="bold italic">Tebal & Miring</option>
                </select>
            </div>
            <div class="form-group">
                <label for="textBgColor">Warna Latar Belakang Teks:</label>
                <select id="textBgColor" class="field-select">
                    <option value="transparent">Transparan (Tanpa Latar)</option>
                    <option value="rgba(255,255,255,0.9)">Putih Semi-Transparan</option>
                    <option value="rgba(0,0,0,0.75)">Hitam Semi-Transparan</option>
                    <option value="rgba(254,240,138,0.9)">Kuning Catatan</option>
                </select>
            </div>
        </div>

        <div class="modal-actions">
            <button type="button" class="btn secondary" onclick="closeTextModal()">Batal</button>
            <button type="button" class="btn" onclick="applyTextToStudio()">Bubuhkan ke Kanvas</button>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Mulai Kanvas Baru -->
<div class="modal-overlay" id="newCanvasConfirmModal" style="display:none">
    <div class="modal" style="max-width:440px;text-align:center">
        <button class="btn-close" onclick="closeNewCanvasModal()" aria-label="Tutup Modal">×</button>
        <div style="font-size:44px;margin-bottom:8px">🎨</div>
        <h2 style="margin-bottom:8px">Mulai Lembar Kanvas Baru?</h2>
        <p class="modal-sub" style="margin-bottom:20px;line-height:1.6">
            Seluruh lapisan gambar, goresan, dan draf di kanvas saat ini akan dibersihkan dan diganti dengan lembar kerja putih baru. Pastikan Anda sudah mengunduh karya jika ingin menyimpannya.
        </p>
        <div class="modal-actions" style="justify-content:center;gap:12px">
            <button type="button" class="btn secondary" onclick="closeNewCanvasModal()">Batal</button>
            <button type="button" class="btn" style="background:#ef4444;border-color:#ef4444;color:#ffffff" onclick="executeNewCanvas()">Ya, Buat Kanvas Baru</button>
        </div>
    </div>
</div>

<script src="assets/js/pameran.js?v=<?= @filemtime(__DIR__ . '/../assets/js/pameran.js') ?>"></script>
<script src="assets/js/studio.js?v=<?= @filemtime(__DIR__ . '/../assets/js/studio.js') ?>"></script>
<script>
    let studio = null;

    document.addEventListener('DOMContentLoaded', () => {
        // Pulihkan preferensi posisi docking toolbar
        const savedDock = localStorage.getItem('digiart_studio_dock');
        if (savedDock === 'right') {
            document.getElementById('flyingToolbar')?.classList.add('dock-right');
            document.querySelectorAll('.flyout-panel').forEach(p => p.classList.add('dock-right'));
        }

        // Inisialisasi Studio Engine (Resolusi 960x600)
        try {
            const studioUser = <?= json_encode($currentUser) ?>;
            const studioServerDraft = <?= json_encode($serverDraft) ?>;

            studio = new StudioEngine('studioCanvasZone', 960, 600, {
                user: studioUser,
                serverDraft: studioServerDraft
            });
            window.studio = studio;

            // Cek jika ada gambar yang diimpor dari menu Referensi
            const importedImage = sessionStorage.getItem('digiStudioImportImage');
            const importedTitle = sessionStorage.getItem('digiStudioImportImageTitle') || 'Referensi';
            if (importedImage) {
                studio.importImageAsDedicatedLayer(importedImage, importedTitle);
                sessionStorage.removeItem('digiStudioImportImage');
                sessionStorage.removeItem('digiStudioImportImageTitle');
            }

            // Sinkronkan warna dan profil alat default ke UI
            setStudioColor(studio.color || '#2563eb');
            setStudioTool(studio.tool || 'brush-simple');
            if (typeof studio.syncToolSettingsUI === 'function') {
                studio.syncToolSettingsUI();
            }
        } catch (err) {
            console.error('Inisialisasi Studio Engine gagal:', err);
            window.showToast?.('Gagal memuat studio menggambar: ' + err.message);
        }
    });

    // Toggle Layar Penuh (Fullscreen)
    function toggleStudioFullscreen() {
        if (window.studio && typeof window.studio.toggleFullscreen === 'function') {
            window.studio.toggleFullscreen();
            return;
        }
        const el = document.querySelector('.studio-wrapper') || document.documentElement;
        if (!document.fullscreenElement && !document.webkitFullscreenElement) {
            if (el.requestFullscreen) {
                el.requestFullscreen().catch(e => console.warn('Fullscreen request failed:', e));
            } else if (el.webkitRequestFullscreen) {
                el.webkitRequestFullscreen();
            }
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen().catch(e => console.warn('Exit fullscreen failed:', e));
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            }
        }
    }

    // Pindahkan docking toolbar kiri / kanan
    function toggleFlyingDock() {
        const tb = document.getElementById('flyingToolbar');
        const panels = document.querySelectorAll('.flyout-panel');
        if (!tb) return;

        const isRight = tb.classList.toggle('dock-right');
        panels.forEach(p => {
            if (isRight) {
                p.classList.add('dock-right');
            } else {
                p.classList.remove('dock-right');
            }
        });
        localStorage.setItem('digiart_studio_dock', isRight ? 'right' : 'left');
    }

    // Toggle Popover Flyout
    function toggleFlyout(panelId) {
        const panel = document.getElementById(panelId);
        if (!panel) return;
        const isOpen = panel.classList.contains('open');
        closeAllFlyouts();
        if (!isOpen) {
            panel.classList.add('open');
        }
    }

    function closeAllFlyouts() {
        document.querySelectorAll('.flyout-panel').forEach(p => p.classList.remove('open'));
    }

    function handleCanvasZoneClick(e) {
        if (e.target.id === 'studioDisplayCanvas' || e.target.id === 'studioCanvasZone') {
            closeAllFlyouts();
        }
    }

    function setStudioTool(t) {
        if (!studio) return;
        if (typeof studio.setBrushTool === 'function' && studio.brushProfiles && studio.brushProfiles[t]) {
            studio.setBrushTool(t);
        } else {
            studio.tool = t;
        }

        document.querySelectorAll('.btn-fly-tool').forEach(b => b.classList.remove('active'));

        if (t === 'brush-simple') {
            document.getElementById('toolBtnSimple')?.classList.add('active');
        } else if (t === 'brush-sketch') {
            document.getElementById('toolBtnSketch')?.classList.add('active');
        } else if (t === 'brush-soft') {
            document.getElementById('toolBtnSoft')?.classList.add('active');
        } else if (t === 'brush-airbrush') {
            document.getElementById('toolBtnAirbrush')?.classList.add('active');
        } else if (t === 'eraser') {
            document.getElementById('toolBtnEraser')?.classList.add('active');
        } else if (t === 'bucket') {
            document.getElementById('toolBtnBucket')?.classList.add('active');
        } else if (t === 'shape') {
            document.getElementById('toolBtnShape')?.classList.add('active');
        } else if (t === 'move') {
            document.getElementById('toolBtnMove')?.classList.add('active');
        }
    }

    function setStudioShape(shapeType) {
        if (!studio) return;
        studio.setShape(shapeType);
        document.querySelectorAll('.btn-fly-tool').forEach(b => b.classList.remove('active'));
        document.getElementById('toolBtnShape')?.classList.add('active');
        document.querySelectorAll('.btn-shape-pick').forEach(b => b.classList.remove('active'));
        document.getElementById('shapePick_' + shapeType)?.classList.add('active');
        closeAllFlyouts();
        window.showToast?.('Mode Bentuk: ' + shapeType + ' (tarik di kanvas)');
    }

    function setShapeStyle(style) {
        if (!studio) return;
        studio.setShapeStyle(style);
        document.querySelectorAll('.btn-shape-style').forEach(b => b.classList.remove('active'));
        if (style === 'stroke') document.getElementById('btnShapeStyleStroke')?.classList.add('active');
        else if (style === 'fill') document.getElementById('btnShapeStyleFill')?.classList.add('active');
        else if (style === 'both') document.getElementById('btnShapeStyleBoth')?.classList.add('active');
    }

    function setStudioColor(hex) {
        if (!studio) return;
        studio.color = hex;
        const nativeInput = document.getElementById('nativeColorInput');
        const hexText = document.getElementById('currentColorHex');
        const previewDot = document.getElementById('flyColorPreview');

        if (nativeInput) nativeInput.value = hex;
        if (hexText) hexText.textContent = hex;
        if (previewDot) previewDot.style.background = hex;
    }

    function setBrushSize(size) {
        if (!studio) return;
        const val = parseInt(size, 10);
        studio.brushSize = val;
        if (typeof studio.saveCurrentToolSetting === 'function') {
            studio.saveCurrentToolSetting('size', val);
        }
        const range = document.getElementById('brushSizeRange');
        const valText = document.getElementById('brushSizeVal');
        if (range) range.value = val;
        if (valText) valText.textContent = `${val} px`;
    }

    function setBrushOpacity(opacity) {
        if (!studio) return;
        const num = parseInt(opacity, 10);
        const opVal = num / 100;
        studio.brushOpacity = opVal;
        if (typeof studio.saveCurrentToolSetting === 'function') {
            studio.saveCurrentToolSetting('opacity', opVal);
        }
        const range = document.getElementById('brushOpacityRange');
        const valText = document.getElementById('brushOpacityVal');
        if (range) range.value = num;
        if (valText) valText.textContent = `${num}%`;
    }

    function setBrushStabilizer(val) {
        if (!studio) return;
        const num = parseInt(val, 10);
        studio.stabilizer = num;
        if (typeof studio.saveCurrentToolSetting === 'function') {
            studio.saveCurrentToolSetting('stabilizer', num);
        }
        const range = document.getElementById('brushStabilizerRange');
        const label = document.getElementById('brushStabilizerVal');
        if (range) range.value = num;
        if (label) {
            if (num === 0) label.textContent = 'Mati (0)';
            else if (num <= 3) label.textContent = `${num} (Ringan)`;
            else if (num <= 7) label.textContent = `${num} (Sedang)`;
            else label.textContent = `${num} (Sangat Halus)`;
        }
    }

    function openTextModal() {
        closeAllFlyouts();
        const modal = document.getElementById('textToolModal');
        if (modal) {
            modal.style.display = 'flex';
            document.getElementById('textInputString')?.focus();
        }
    }

    function closeTextModal() {
        const modal = document.getElementById('textToolModal');
        if (modal) modal.style.display = 'none';
    }

    function applyTextToStudio() {
        const text = document.getElementById('textInputString')?.value.trim();
        if (!text) {
            window.showToast?.('Tuliskan teks terlebih dahulu.');
            return;
        }

        const size = parseInt(document.getElementById('textFontSize')?.value, 10) || 32;
        const font = document.getElementById('textFontFamily')?.value || 'Outfit, sans-serif';
        const style = document.getElementById('textFontStyle')?.value || 'bold';
        const bgColor = document.getElementById('textBgColor')?.value || 'transparent';

        studio.startMovableText(text, {
            fontSize: size,
            fontFamily: font,
            fontStyle: style,
            bgColor: bgColor,
            color: studio.color,
            x: studio.textSpawnX,
            y: studio.textSpawnY
        });
        studio.textSpawnX = null;
        studio.textSpawnY = null;

        closeTextModal();
    }

    async function handleStudioImageUpload(e) {
        const file = e.target.files[0];
        if (!file) return;

        try {
            const compressed = await ImageCompressor.compress(file, { maxWidth: 1200, maxHeight: 800 });
            const cleanName = file.name ? file.name.replace(/\.[^/.]+$/, "") : 'Gambar';
            studio.importImageAsDedicatedLayer(compressed.dataUrl, cleanName);
        } catch (err) {
            window.showToast?.('Gagal memuat gambar: ' + err.message);
        }
        e.target.value = '';
    }

    function toggleDownloadMenu(forceState) {
        const el = document.getElementById('downloadMenuDropdown');
        if (!el) return;
        if (typeof forceState === 'boolean') {
            el.style.display = forceState ? 'block' : 'none';
        } else {
            el.style.display = (el.style.display === 'block') ? 'none' : 'block';
        }
    }

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.download-dropdown-wrap')) {
            const el = document.getElementById('downloadMenuDropdown');
            if (el) el.style.display = 'none';
        }
    });

    function openNewCanvasModal() {
        const modal = document.getElementById('newCanvasConfirmModal');
        if (modal) modal.style.display = 'flex';
    }

    function closeNewCanvasModal() {
        const modal = document.getElementById('newCanvasConfirmModal');
        if (modal) modal.style.display = 'none';
    }

    function executeNewCanvas() {
        closeNewCanvasModal();
        if (window.studio) {
            window.studio.newCanvas(false);
        } else if (typeof studio !== 'undefined' && studio) {
            studio.newCanvas(false);
        }
    }

    function exportToPameranModal() {
        const currentStudio = window.studio || (typeof studio !== 'undefined' ? studio : null);
        if (!currentStudio) return;

        const exportResult = currentStudio.exportForPameran();
        if (!exportResult.success) {
            window.showToast?.(exportResult.message);
            alert(exportResult.message);
            return;
        }

        const pngData = exportResult.dataUrl;

        if (typeof PameranApp === 'undefined') {
            window.showToast?.('Memuat modul pameran...');
            return;
        }

        if (exportResult.strippedReferenceCount > 0) {
            window.showToast?.(`ℹ️ ${exportResult.strippedReferenceCount} lapisan gambar referensi internet disembunyikan. Hanya karya orisinal Anda yang akan dikirim.`);
        }

        PameranApp.openUploadModal();
        PameranApp.compressedFilePayload = pngData;

        const curUser = window.currentUser || <?= json_encode($currentUser) ?>;
        if (curUser) {
            const nameEl = document.getElementById('workCreatorName');
            const classEl = document.getElementById('workKelas');
            if (nameEl && !nameEl.value) nameEl.value = curUser.full_name || '';
            if (classEl && curUser.kelas) classEl.value = curUser.kelas;
        }

        const infoBox = document.getElementById('compressInfo');
        if (infoBox) {
            infoBox.style.display = 'block';
            let refNotice = '';
            if (exportResult.strippedReferenceCount > 0) {
                refNotice = `
                    <div style="font-size:11px;color:#b45309;background:#fef3c7;padding:5px 8px;border-radius:6px;margin-top:6px;border:1px solid #fde68a;display:flex;align-items:center;gap:6px">
                        <span>🛡️</span>
                        <span><strong>Anti-Plagiasi:</strong> ${exportResult.strippedReferenceCount} lapisan referensi disaring. Hanya karya orisinal Anda yang dikirim.</span>
                    </div>
                `;
            }
            infoBox.innerHTML = `
                <div style="background:#f0fdf4;border:1px solid #bbf7d0;padding:10px 12px;border-radius:8px;display:flex;gap:12px;align-items:center;margin-top:8px">
                    <img src="${pngData}" alt="Preview Karya Studio" style="width:68px;height:44px;object-fit:cover;border-radius:4px;border:1px solid #86efac;box-shadow:0 1px 3px rgba(0,0,0,0.1)">
                    <div style="flex:1">
                        <strong style="color:#15803d;font-size:13px;display:block">✓ Karya dari Studio Siap Diunggah!</strong>
                        <span style="color:#166534;font-size:11px">Gambar otomatis terpasang. Tulis judul & deskripsi karya di atas lalu tekan "Kirim Karya".</span>
                        ${refNotice}
                    </div>
                </div>
            `;
        }
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
