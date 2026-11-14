<?php
require_once __DIR__ . '/../classes/SiteSetting.php';

$currentPage = $_GET['page'] ?? 'home';
$isProtected = !Auth::canAccessProtectedFeatures();
$isDemoMode  = SiteSetting::isDemoModeEnabled();
?>
<!-- Top Navigation Bar -->
<header class="app-nav">
    <a href="index.php?page=home" class="brand-logo" aria-label="Digi-Art Beranda">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18.37 2.63 14 7l-1.59-1.59a2 2 0 0 0-2.82 0L8 7l9 9 1.59-1.59a2 2 0 0 0 0-2.82L17 10l4.37-4.37a2.12 2.12 0 1 0-3-3Z"/><path d="M9 8c-2 3-4 3.5-7 4l8 8c.5-3 1-5 4-7"/><path d="M14.5 17.5 4.5 7.5"/></svg>
        Digi<span class="dot">-</span><span class="art">Art</span>
    </a>

    <!-- Desktop Navigation Menu -->
    <nav class="nav-menu desktop-only" aria-label="Menu Utama">
        <a href="index.php?page=home" class="nav-link <?= $currentPage === 'home' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Beranda
        </a>
        <?php if (!$isProtected || $isDemoMode): ?>
        <a href="index.php?page=materi" class="nav-link <?= $currentPage === 'materi' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10m-10 4h10"/></svg>
            Materi
        </a>
        <a href="index.php?page=kuis" class="nav-link <?= $currentPage === 'kuis' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            Kuis
        </a>
        <a href="index.php?page=referensi" class="nav-link <?= $currentPage === 'referensi' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            Media Referensi
        </a>
        <a href="index.php?page=studio" class="nav-link <?= $currentPage === 'studio' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m18 2 4 4-14 14H4v-4L18 2z"/></svg>
            Studio Digital
        </a>
        <?php endif; ?>
        <a href="index.php?page=pameran" class="nav-link <?= $currentPage === 'pameran' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            Pameran
        </a>
        <?php if (Auth::canManageMateri()): ?>
        <a href="index.php?page=dashboard" class="nav-link teacher-only-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
            Dashboard Guru
        </a>
        <?php endif; ?>
    </nav>

    <!-- User & Auth Actions -->
    <div class="nav-user-actions">
        <span id="navRoleBadge" class="role-badge" style="display:none"></span>
        <button id="btnNavUser" class="btn-nav-auth" onclick="App.openLoginModal()" aria-label="Masuk Akun">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <span>Masuk</span>
        </button>
        <!-- Mobile Hamburger Toggle Button (iPhone & Mobile Viewports) -->
        <button class="mobile-nav-toggle" id="mobileNavToggle" aria-label="Buka Menu Navigasi" onclick="App.toggleMobileNav()">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
</header>

<!-- Mobile Navigation Overlay & Slide Drawer -->
<div class="mobile-nav-overlay" id="mobileNavOverlay" onclick="App.closeMobileNav()"></div>
<aside class="mobile-nav-drawer" id="mobileNavDrawer" aria-label="Menu Navigasi Mobile">
    <div class="drawer-header">
        <a href="index.php?page=home" class="brand-logo" onclick="App.closeMobileNav()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18.37 2.63 14 7l-1.59-1.59a2 2 0 0 0-2.82 0L8 7l9 9 1.59-1.59a2 2 0 0 0 0-2.82L17 10l4.37-4.37a2.12 2.12 0 1 0-3-3Z"/><path d="M9 8c-2 3-4 3.5-7 4l8 8c.5-3 1-5 4-7"/><path d="M14.5 17.5 4.5 7.5"/></svg>
            Digi<span class="dot">-</span><span class="art">Art</span>
        </a>
        <button class="btn-drawer-close" id="mobileNavClose" onclick="App.closeMobileNav()" aria-label="Tutup Menu">✕</button>
    </div>

    <div class="drawer-user-info" id="drawerUserInfo">
        <div style="font-size:11px;color:var(--muted);font-weight:800;letter-spacing:0.05em">STATUS PENGGUNA</div>
        <div style="display:flex;align-items:center;gap:8px;margin-top:6px;">
            <span id="drawerUserName" style="font-weight:800;font-size:14px;color:var(--navy);">Tamu</span>
            <span id="drawerUserBadge" class="role-badge" style="display:none"></span>
        </div>
    </div>

    <nav class="drawer-menu-list">
        <a href="index.php?page=home" class="drawer-item <?= $currentPage === 'home' ? 'active' : '' ?>" onclick="App.closeMobileNav()">
            <span class="drawer-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            </span>
            <span class="drawer-text">Beranda</span>
        </a>
        <?php if (!$isProtected || $isDemoMode): ?>
        <a href="index.php?page=materi" class="drawer-item <?= $currentPage === 'materi' ? 'active' : '' ?>" onclick="App.closeMobileNav()">
            <span class="drawer-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10m-10 4h10"/></svg>
            </span>
            <span class="drawer-text">Materi</span>
        </a>
        <a href="index.php?page=kuis" class="drawer-item <?= $currentPage === 'kuis' ? 'active' : '' ?>" onclick="App.closeMobileNav()">
            <span class="drawer-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            </span>
            <span class="drawer-text">Kuis Interaktif</span>
        </a>
        <a href="index.php?page=referensi" class="drawer-item <?= $currentPage === 'referensi' ? 'active' : '' ?>" onclick="App.closeMobileNav()">
            <span class="drawer-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </span>
            <span class="drawer-text">Media Referensi</span>
        </a>
        <a href="index.php?page=studio" class="drawer-item <?= $currentPage === 'studio' ? 'active' : '' ?>" onclick="App.closeMobileNav()">
            <span class="drawer-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m18 2 4 4-14 14H4v-4L18 2z"/></svg>
            </span>
            <span class="drawer-text">Studio Digital</span>
        </a>
        <?php endif; ?>
        <a href="index.php?page=pameran" class="drawer-item <?= $currentPage === 'pameran' ? 'active' : '' ?>" onclick="App.closeMobileNav()">
            <span class="drawer-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            </span>
            <span class="drawer-text">Galeri Pameran</span>
        </a>
        <?php if (Auth::canManageMateri()): ?>
        <a href="index.php?page=dashboard" class="drawer-item teacher-only-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" onclick="App.closeMobileNav()">
            <span class="drawer-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
            </span>
            <span class="drawer-text">Dashboard Guru</span>
        </a>
        <?php endif; ?>
    </nav>
</aside>

<!-- Fixed Native-Feel iOS Bottom Tab Bar (Optimized for One-Thumb Reach) -->
<nav class="mobile-bottom-bar" aria-label="Navigasi Cepat Mobile">
    <a href="index.php?page=home" class="bottom-tab <?= $currentPage === 'home' ? 'active' : '' ?>">
        <span class="tab-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        </span>
        <span class="tab-label">Beranda</span>
    </a>
    <?php if (!$isProtected || $isDemoMode): ?>
    <a href="index.php?page=materi" class="bottom-tab <?= $currentPage === 'materi' ? 'active' : '' ?>">
        <span class="tab-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/></svg>
        </span>
        <span class="tab-label">Materi</span>
    </a>
    <a href="index.php?page=studio" class="bottom-tab highlight-studio <?= $currentPage === 'studio' ? 'active' : '' ?>">
        <div class="studio-tab-circle">
            <span class="tab-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" aria-hidden="true"><path d="m18 2 4 4-14 14H4v-4L18 2z"/></svg>
            </span>
        </div>
        <span class="tab-label">Studio</span>
    </a>
    <a href="index.php?page=kuis" class="bottom-tab <?= $currentPage === 'kuis' ? 'active' : '' ?>">
        <span class="tab-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
        </span>
        <span class="tab-label">Kuis</span>
    </a>
    <?php endif; ?>
    <a href="index.php?page=pameran" class="bottom-tab <?= $currentPage === 'pameran' ? 'active' : '' ?>">
        <span class="tab-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
        </span>
        <span class="tab-label">Pameran</span>
    </a>
    <?php if ($isProtected && !$isDemoMode): ?>
    <a href="javascript:void(0)" onclick="App.openLoginModal()" class="bottom-tab">
        <span class="tab-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </span>
        <span class="tab-label">Masuk</span>
    </a>
    <?php endif; ?>
</nav>
