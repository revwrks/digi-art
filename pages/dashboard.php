<?php
$pageTitle = 'Dashboard Guru & Admin — Digi-Art';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/auth_guard.php';

require_once __DIR__ . '/../classes/Activity.php';
require_once __DIR__ . '/../classes/SiteSetting.php';
require_once __DIR__ . '/../classes/Materi.php';

$stats = Activity::getStats();
$recentLogs = Activity::getRecent(25);
$isAdmin = Auth::hasRole('admin');
$currentUser = Auth::user();

$settingModel = new SiteSetting();
$homeSettings = $settingModel->getAll();
$isDemoMode   = $settingModel->isDemoMode();

$materiModel  = new Materi();
$materiHeader = $materiModel->getHeader();
?>

<div class="page-header">
    <div class="kicker">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
        PANEL KELOLA GURU & ADMINISTRATOR
    </div>
    <h1>Dashboard Pembelajaran Digi-Art</h1>
    <p>Kelola persetujuan karya siswa, atur urutan dan isi materi pembelajaran, kelola akun pengguna, serta dekorasi beranda.</p>
</div>

<div class="dash-container">
    <!-- Stats Overview -->
    <div class="dash-stats-grid">
        <div class="dash-stat-card">
            <div class="stat-icon-circle">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="stat-meta">
                <span>Total Akun</span>
                <strong><?= $stats['students'] ?></strong>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="stat-icon-circle" style="background:#fff1f2;color:#f43f5e">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="stat-meta">
                <span>Antrean Approval</span>
                <strong style="color:var(--accent-coral)"><?= $stats['pending_works'] ?></strong>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="stat-icon-circle">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            </div>
            <div class="stat-meta">
                <span>Karya Terpamerkan</span>
                <strong><?= $stats['works'] ?></strong>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="stat-icon-circle" style="background:#fef3c7;color:#d97706">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            </div>
            <div class="stat-meta">
                <span>Kuis Diselesaikan</span>
                <strong><?= $stats['quizzes'] ?></strong>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="dash-nav-tabs">
        <button class="dash-nav-btn active" data-tab="pameran" onclick="DashboardApp.switchTab('pameran')">
            Persetujuan Karya (<?= $stats['pending_works'] ?> Menunggu)
        </button>
        <button class="dash-nav-btn" data-tab="materi" id="materi-tab" onclick="DashboardApp.switchTab('materi')">
            Kelola Materi Pembelajaran
        </button>
        <button class="dash-nav-btn" data-tab="kuis" id="kuis-tab" onclick="DashboardApp.switchTab('kuis')">
            Kelola Soal Kuis
        </button>
        <button class="dash-nav-btn" data-tab="siswa" id="siswa-tab" onclick="DashboardApp.switchTab('siswa')">
            Manajemen Akun Siswa
        </button>
        <button class="dash-nav-btn" data-tab="log" id="log-tab" onclick="DashboardApp.switchTab('log')">
            Log Aktivitas
        </button>
        <?php if ($isAdmin): ?>
        <button class="dash-nav-btn" data-tab="pengaturan" id="pengaturan-tab" onclick="DashboardApp.switchTab('pengaturan')">
            Pengaturan Akses
        </button>
        <button class="dash-nav-btn" data-tab="dekorasi" id="dekorasi-tab" onclick="DashboardApp.switchTab('dekorasi')">
            Dekorasi Beranda
        </button>
        <?php endif; ?>
    </div>

    <!-- Panel 1: Persetujuan Karya Pameran -->
    <div class="dash-panel active" id="panel-pameran">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:10px">
            <div>
                <h3 style="font-size:18px;margin-bottom:4px">Antrean Persetujuan Karya Siswa</h3>
                <p style="font-size:13px;color:var(--muted)">Nama Anda akan tercatat sebagai kurator resmi karya saat disetujui.</p>
            </div>
            <div style="display:flex;gap:6px">
                <button class="btn secondary small" onclick="DashboardApp.loadPameranApprovals('pending')">Hanya Menunggu</button>
                <button class="btn secondary small" onclick="DashboardApp.loadPameranApprovals('approved')">Disetujui</button>
                <button class="btn secondary small" onclick="DashboardApp.loadPameranApprovals()">Semua Karya</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th style="width:70px">Gambar</th>
                        <th>Judul & Keterangan</th>
                        <th>Pembuat</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th style="text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="dashPameranTable"></tbody>
            </table>
        </div>

        <!-- Pagination untuk Tabel Karya Pameran (100% Client-Side) -->
        <div id="dashPameranPagination" class="dash-pagination-wrap" style="display:none"></div>
    </div>

    <!-- Panel 2: Kelola Materi Pembelajaran (Tambah/Kurang Nomor, Isi, Gambar, Bab & Judul) -->
    <div class="dash-panel" id="panel-materi">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px">
            <div>
                <h3 style="font-size:18px;margin-bottom:4px">Kelola Materi Pembelajaran</h3>
                <p style="font-size:13px;color:var(--muted)">
                    Guru dapat mengatur bab/judul materi, menambah nomor baru, menghapus materi, mengganti template tampilan, serta memasang ilustrasi rujukan.
                </p>
            </div>
            <div>
                <button class="btn small" onclick="DashboardApp.openAddMateriModal()">
                    ＋ Tambah Nomor Materi Baru
                </button>
            </div>
        </div>

        <!-- Pengaturan Bab, Judul, & Deskripsi Pengantar Materi -->
        <div class="materi-header-setting-card">
            <div class="materi-header-setting-top">
                <div>
                    <h4 style="font-size:15px;margin:0 0 4px 0;display:flex;align-items:center;gap:8px">
                        <span>🏷️</span> Informasi Bab & Judul Materi Pembelajaran
                    </h4>
                    <p style="font-size:13px;color:var(--muted);margin:0">
                        Atur nama bab, judul materi pokok, dan deskripsi pengantar yang akan tampil pada banner halaman Materi siswa.
                    </p>
                </div>
                <button type="button" class="btn small" onclick="DashboardApp.saveMateriHeader()" id="btnSaveMateriHeader">
                    💾 Simpan Judul & Bab
                </button>
            </div>
            <div class="form-row materi-header-inputs-row">
                <div class="form-group">
                    <label for="materiBabInput">Bab & Kelas (Kicker):</label>
                    <input type="text" id="materiBabInput" class="field-input" placeholder="Contoh: BAB 1 • SENI RUPA KELAS VIII" value="<?= htmlspecialchars($materiHeader['bab'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="materiTitleInput">Judul Pokok Materi:</label>
                    <input type="text" id="materiTitleInput" class="field-input" placeholder="Contoh: Menggambar Ilustrasi untuk Mata Pelajaran Lain" value="<?= htmlspecialchars($materiHeader['title'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group" style="margin-top:12px">
                <label for="materiDescInput">Deskripsi / Pengantar Materi:</label>
                <textarea id="materiDescInput" class="field-input" rows="2" style="resize:vertical" placeholder="Deskripsi ringkas materi pembelajaran..."><?= htmlspecialchars($materiHeader['description'] ?? '') ?></textarea>
            </div>
        </div>

        <div id="dashMateriList"></div>
    </div>

    <!-- Panel 3: Kelola Soal & Kunci Jawaban Kuis Interaktif -->
    <div class="dash-panel" id="panel-kuis">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px">
            <div>
                <h3 style="font-size:18px;margin-bottom:4px">Bank Soal Kuis Interaktif Seni Budaya</h3>
                <p style="font-size:13px;color:var(--muted)">
                    Guru dapat menambah pertanyaan baru, mengedit opsi & kunci jawaban, mengatur durasi pengerjaan, serta bobot poin soal kuis siswa.
                </p>
            </div>
            <div>
                <button class="btn small" onclick="DashboardApp.openAddQuizQuestionModal()">
                    ＋ Tambah Soal Kuis Baru
                </button>
            </div>
        </div>

        <div id="dashQuizQuestionsList" class="dash-quiz-questions-grid">
            <div style="text-align:center;padding:30px;color:var(--muted)">Memuat daftar soal kuis...</div>
        </div>

        <!-- Pagination untuk Bank Soal Kuis (100% Client-Side) -->
        <div id="dashQuizPagination" class="dash-pagination-wrap" style="display:none"></div>
    </div>

    <!-- Panel 4: Manajemen Akun Pengguna (Admin & Guru: Buat & Hapus) -->
    <div class="dash-panel" id="panel-siswa">
        <div class="dash-user-mgmt-layout">
            <!-- Form Tambah Akun (Desktop Sidebar) -->
            <div class="dash-create-user-sidebar" style="background:#f8fafc;border:1px solid var(--line);border-radius:var(--radius-md);padding:20px">
                <h3 style="font-size:16px;margin-bottom:6px">Tambah Akun Pengguna</h3>
                <p style="font-size:12px;color:var(--muted);margin-bottom:16px">
                    <?php if ($isAdmin): ?>
                        Sebagai <strong>Administrator</strong>, Anda dapat membuat akun Siswa, Guru, maupun Admin baru.
                    <?php else: ?>
                        Sebagai <strong>Guru</strong>, Anda dapat mendaftarkan akun Siswa baru ke dalam sistem.
                    <?php endif; ?>
                </p>

                <form id="addStudentForm" onsubmit="DashboardApp.createUser(event)">
                    <div class="form-group">
                        <label for="newStudentName">Nama Lengkap:</label>
                        <input type="text" id="newStudentName" class="field-input" placeholder="Contoh: Ahmad Fauzan" required>
                    </div>
                    <div class="form-group">
                        <label for="newStudentUsername">Username Login:</label>
                        <input type="text" id="newStudentUsername" class="field-input" placeholder="Contoh: fauzan" required>
                    </div>
                    <div class="form-group">
                        <label for="newStudentPassword">Password Awal:</label>
                        <input type="password" id="newStudentPassword" class="field-input" placeholder="Minimal 4 karakter" required>
                    </div>

                    <?php if ($isAdmin): ?>
                    <div class="form-group">
                        <label for="newUserRole">Peran Akun (Role):</label>
                        <select id="newUserRole" class="field-select" onchange="DashboardApp.toggleKelasField(this.value)">
                            <option value="murid">Siswa (Murid)</option>
                            <option value="guru">Guru Seni Rupa</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <?php else: ?>
                        <input type="hidden" id="newUserRole" value="murid">
                    <?php endif; ?>

                    <div class="form-group" id="groupKelasInput">
                        <label for="newStudentKelas">Kelas Siswa:</label>
                        <select id="newStudentKelas" class="field-select">
                            <optgroup label="Kelas VII (7A s/d 7F)">
                                <option>VII A</option>
                                <option>VII B</option>
                                <option>VII C</option>
                                <option>VII D</option>
                                <option>VII E</option>
                                <option>VII F</option>
                            </optgroup>
                            <optgroup label="Kelas VIII">
                                <option selected>VIII A</option>
                                <option>VIII B</option>
                                <option>VIII C</option>
                                <option>VIII D</option>
                                <option>VIII E</option>
                                <option>VIII F</option>
                            </optgroup>
                            <optgroup label="Kelas IX">
                                <option>IX A</option>
                                <option>IX B</option>
                                <option>IX C</option>
                                <option>IX D</option>
                                <option>IX E</option>
                                <option>IX F</option>
                            </optgroup>
                        </select>
                    </div>

                    <button type="submit" id="btnCreateStudent" class="btn small" style="width:100%;margin-top:8px">
                        ＋ Simpan Akun Pengguna
                    </button>
                </form>
            </div>

            <!-- Daftar Pengguna Terdaftar -->
            <div class="dash-user-mgmt-main">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:10px">
                    <div>
                        <h3 style="font-size:16px;margin:0">Daftar Akun Terdaftar</h3>
                        <small style="color:var(--muted)">
                            <?= $isAdmin ? 'Mode: Administrator (Semua Hak Akses)' : 'Mode: Guru (Hanya dapat mengelola akun murid)' ?>
                        </small>
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                        <!-- View Toggle: Kartu vs Tabel -->
                        <div class="user-view-toggle">
                            <button type="button" class="btn-view-toggle" id="btnViewCards" onclick="DashboardApp.setUserView('cards')" title="Tampilan Kartu">🗂️ Kartu</button>
                            <button type="button" class="btn-view-toggle" id="btnViewTable" onclick="DashboardApp.setUserView('table')" title="Tampilan Tabel">📋 Tabel</button>
                        </div>
                        <button type="button" class="btn small btn-mobile-create-user" onclick="DashboardApp.openCreateUserModal()">
                            ＋ Tambah Akun Baru
                        </button>
                    </div>
                </div>

                <!-- Filter & Search Bar untuk Manajemen Akun -->
                <div class="dash-user-filter-bar" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;background:#f8fafc;padding:10px 14px;border:1px solid var(--line);border-radius:var(--radius-md);margin-bottom:14px">
                    <div style="flex:1;min-width:200px">
                        <input type="text" id="userSearchInput" class="field-input" style="padding:7px 12px;font-size:13px;margin:0" placeholder="🔍 Cari nama siswa atau username..." oninput="DashboardApp.onUsersSearch(this.value)">
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                        <select id="userClassFilter" class="field-select" style="padding:7px 10px;font-size:12px;margin:0;width:auto" onchange="DashboardApp.onUsersClassFilter(this.value)">
                            <option value="">Semua Kelas</option>
                            <optgroup label="Kelas VII">
                                <option value="VII A">VII A</option>
                                <option value="VII B">VII B</option>
                                <option value="VII C">VII C</option>
                                <option value="VII D">VII D</option>
                                <option value="VII E">VII E</option>
                                <option value="VII F">VII F</option>
                            </optgroup>
                            <optgroup label="Kelas VIII">
                                <option value="VIII A">VIII A</option>
                                <option value="VIII B">VIII B</option>
                                <option value="VIII C">VIII C</option>
                                <option value="VIII D">VIII D</option>
                                <option value="VIII E">VIII E</option>
                                <option value="VIII F">VIII F</option>
                            </optgroup>
                            <optgroup label="Kelas IX">
                                <option value="IX A">IX A</option>
                                <option value="IX B">IX B</option>
                                <option value="IX C">IX C</option>
                                <option value="IX D">IX D</option>
                                <option value="IX E">IX E</option>
                                <option value="IX F">IX F</option>
                            </optgroup>
                        </select>
                        <select id="userPageSize" class="field-select" style="padding:7px 10px;font-size:12px;margin:0;width:auto" onchange="DashboardApp.onUsersPageSize(this.value)">
                            <option value="15" selected>15 per hal</option>
                            <option value="30">30 per hal</option>
                            <option value="50">50 per hal</option>
                            <option value="100">100 per hal</option>
                            <option value="all">Tampilkan Semua</option>
                        </select>
                    </div>
                </div>

                <!-- Tampilan 1: User Cards Grid (Sangat Rapi di Layar HP & Tablet) -->
                <div id="dashStudentsCardsGrid" class="dash-user-cards-grid">
                    <div style="text-align:center;padding:24px;color:var(--muted)">Memuat kartu pengguna…</div>
                </div>

                <!-- Tampilan 2: Tabel Pengguna Klasik -->
                <div class="table-responsive dash-students-table-wrap" id="dashStudentsTableWrap">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th style="width:40px">#</th>
                                <th>Nama Lengkap</th>
                                <th>Username</th>
                                <th>Peran</th>
                                <th>Kelas</th>
                                <th style="text-align:center;min-width:130px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="dashStudentsTable"></tbody>
                    </table>
                </div>

                <!-- Pagination untuk Manajemen Akun (100% Client-Side) -->
                <div id="dashStudentsPagination" class="dash-pagination-wrap" style="display:none"></div>
            </div>
        </div>
    </div>

    <!-- Panel 5: Log Aktivitas Sistem (Maksimal 250 Log dengan Paginasi) -->
    <div class="dash-panel" id="panel-log">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px">
            <div>
                <h3 style="font-size:18px;margin-bottom:4px">Catatan Aktivitas Pengguna (Maksimal 250 Log)</h3>
                <p style="font-size:13px;color:var(--muted)">Riwayat aksi pengguna, login, pengiriman karya, kurasi pameran, dan pembaruan materi.</p>
            </div>
            <div>
                <button type="button" class="btn secondary small" onclick="DashboardApp.loadActivityLogs()">↻ Segarkan Log</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th style="width:160px">Waktu</th>
                        <th>Pengguna</th>
                        <th>Peran (Role)</th>
                        <th>Aktivitas</th>
                    </tr>
                </thead>
                <tbody id="dashActivityLogsTable">
                    <tr><td colspan="4" style="text-align:center;padding:24px;color:var(--muted)">Memuat riwayat aktivitas…</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination untuk Tabel Log Aktivitas (100% Client-Side) -->
        <div id="dashActivityPagination" class="dash-pagination-wrap" style="display:none"></div>
    </div>

    <!-- Panel 5: Pengaturan Hiasan & Tampilan Beranda (Khusus Administrator) -->
    <?php if ($isAdmin): ?>
    <div class="dash-panel" id="panel-dekorasi">
        <div style="margin-bottom:20px">
            <h3 style="font-size:18px;margin-bottom:4px">🎨 Pengaturan Hiasan & Tampilan Beranda (Index)</h3>
            <p style="font-size:13px;color:var(--muted)">
                Sebagai Administrator, Anda dapat secara fleksibel mengubah teks sambutan, banner kicker, badge pengumuman, tema gradien, dan gambar hiasan beranda.
            </p>
        </div>

        <form id="homeDecorationForm" onsubmit="DashboardApp.saveHomeDecorations(event)" style="max-width:820px">
            <div class="form-row">
                <div class="form-group">
                    <label for="decorKicker">Teks Kicker (Label Atas):</label>
                    <input type="text" id="decorKicker" class="field-input" value="<?= htmlspecialchars($homeSettings['home_kicker'] ?? 'PEMBELAJARAN SENI RUPA • KELAS VIII') ?>" required>
                </div>
                <div class="form-group">
                    <label for="decorBadge">Badge Pengumuman / Info:</label>
                    <input type="text" id="decorBadge" class="field-input" value="<?= htmlspecialchars($homeSettings['home_badge'] ?? '✨ Kurikulum Seni Rupa SMP • Kelas VIII') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="decorTitle">Judul Utama Hero:</label>
                    <input type="text" id="decorTitle" class="field-input" value="<?= htmlspecialchars($homeSettings['home_title'] ?? 'Belajar Seni Visual di') ?>" required>
                </div>
                <div class="form-group">
                    <label for="decorHighlight">Teks Sorotan (Highlight Biru):</label>
                    <input type="text" id="decorHighlight" class="field-input" value="<?= htmlspecialchars($homeSettings['home_highlight'] ?? 'Digi-Art') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="decorDescription">Paragraf Sambutan / Deskripsi Beranda:</label>
                <textarea id="decorDescription" class="field-textarea" rows="3" required><?= htmlspecialchars($homeSettings['home_description'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="decorTheme">Tema Aksen Warna Beranda:</label>
                    <select id="decorTheme" class="field-select">
                        <option value="blue" <?= ($homeSettings['home_theme'] ?? '') === 'blue' ? 'selected' : '' ?>>🔵 Biru Modern Studio (Default)</option>
                        <option value="crimson" <?= ($homeSettings['home_theme'] ?? '') === 'crimson' ? 'selected' : '' ?>>🔴 Merah Crimson Dinamis</option>
                        <option value="emerald" <?= ($homeSettings['home_theme'] ?? '') === 'emerald' ? 'selected' : '' ?>>🟢 Hijau Emerald Alami</option>
                        <option value="purple" <?= ($homeSettings['home_theme'] ?? '') === 'purple' ? 'selected' : '' ?>>🟣 Ungu Royal Artistik</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="decorArtMode">Model Hiasan Visual Hero:</label>
                    <select id="decorArtMode" class="field-select" onchange="DashboardApp.toggleCustomArtInput(this.value)">
                        <option value="svg_default" <?= ($homeSettings['home_art_mode'] ?? '') === 'svg_default' ? 'selected' : '' ?>>🎨 Ilustrasi Lanskap Seni Rupa (SVG Default)</option>
                        <option value="custom" <?= ($homeSettings['home_art_mode'] ?? '') === 'custom' ? 'selected' : '' ?>>🖼️ Gambar Kustom Sendiri (URL)</option>
                    </select>
                </div>
            </div>

            <div class="form-group" id="decorCustomArtGroup" style="<?= ($homeSettings['home_art_mode'] ?? '') === 'custom' ? '' : 'display:none' ?>">
                <label for="decorCustomArt">URL Gambar Kustom Hiasan Beranda:</label>
                <input type="text" id="decorCustomArt" class="field-input" value="<?= htmlspecialchars($homeSettings['home_custom_art'] ?? '') ?>" placeholder="Contoh: assets/img/sample-volcano.svg atau URL gambar eksternal">
                <small style="color:var(--muted);font-size:11px">Gambar akan tampil menggantikan ilustrasi SVG bawaan di kartu sisi kanan banner hero.</small>
            </div>

            <div style="margin-top:20px;display:flex;gap:12px;align-items:center">
                <button type="submit" id="btnSaveDecorations" class="btn">
                    💾 Simpan Pengaturan Hiasan Beranda
                </button>
                <a href="index.php" target="_blank" class="btn secondary">
                    👁️ Lihat Tampilan Beranda
                </a>
            </div>
        </form>
    </div>

    <!-- Panel 6: Pengaturan Akses Akun Bawaan & Sistem (Khusus Administrator) -->
    <div class="dash-panel" id="panel-pengaturan">
        <div style="margin-bottom:20px">
            <h3 style="font-size:18px;margin-bottom:4px">🛠️ Pengaturan Akses Akun & Keamanan Sistem</h3>
            <p style="font-size:13px;color:var(--muted)">
                Atur ketersediaan akun contoh bawaan (murid/guru) dan visibilitas menu pembelajaran untuk pengunjung yang belum login.
            </p>
        </div>

        <div class="demo-mode-card" style="background:#ffffff;border:1px solid var(--line);border-radius:var(--radius-md);padding:24px;max-width:820px;box-shadow:var(--shadow-sm);margin-bottom:24px">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:20px;flex-wrap:wrap">
                <div style="flex:1;min-width:280px">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
                        <span style="font-size:24px">👥</span>
                        <strong style="font-size:17px;color:var(--navy)">Status Akses Akun Bawaan</strong>
                        <span id="demoModeBadge" class="role-badge <?= $isDemoMode ? 'murid' : 'guru' ?>" style="font-size:12px;padding:4px 10px">
                            <?= $isDemoMode ? '🟢 Akun Bawaan Aktif' : '🔒 Khusus Akun Terdaftar' ?>
                        </span>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:14px;margin-bottom:14px">
                        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px">
                            <strong style="color:#166534;font-size:13px;display:block;margin-bottom:4px">Saat Saklar AKTIF (Mode Terbuka):</strong>
                            <ul style="font-size:12px;color:#15803d;margin:0;padding-left:16px;line-height:1.5">
                                <li>Akun contoh siswa (<code>murid</code>) dan guru (<code>guru</code>) dapat digunakan untuk mencoba fitur aplikasi.</li>
                                <li>Tombol akses cepat akun contoh muncul pada jendela masuk.</li>
                                <li>Menu fitur pembelajaran tetap terlihat di navigasi dengan tanda kunci (🔒) sebelum masuk.</li>
                            </ul>
                        </div>
                        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px">
                            <strong style="color:#991b1b;font-size:13px;display:block;margin-bottom:4px">Saat Saklar NONAKTIF (Khusus Akun Terdaftar):</strong>
                            <ul style="font-size:12px;color:#b91c1c;margin:0;padding-left:16px;line-height:1.5">
                                <li>Akun bawaan dinonaktifkan sepenuhnya demi ketertiban pembelajaran kelas.</li>
                                <li>Hanya akun siswa dan guru resmi yang dapat masuk ke dalam sistem.</li>
                                <li>Menu fitur pembelajaran disembunyikan bagi pengunjung luar yang belum login.</li>
                                <li>Kredensial login disembunyikan dari halaman yang terkunci.</li>
                            </ul>
                        </div>
                    </div>

                    <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:10px 14px;font-size:12px;color:#92400e;display:flex;align-items:center;gap:8px">
                        <span>🛡️</span>
                        <span><strong>Proteksi Keamanan:</strong> Akun administrator hanya dapat diakses melalui login resmi untuk menjaga keamanan sistem sekolah.</span>
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;align-items:center;gap:10px;padding:18px 22px;background:var(--bg);border-radius:12px;border:1px solid var(--line);min-width:140px">
                    <span style="font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:0.5px">Saklar Akses</span>
                    <label class="toggle-switch">
                        <input type="checkbox" id="toggleDemoMode" <?= $isDemoMode ? 'checked' : '' ?> onchange="DashboardApp.toggleDemoMode(this.checked)">
                        <span class="toggle-slider"></span>
                    </label>
                    <span id="toggleDemoModeLabel" style="font-size:13px;font-weight:800;color:<?= $isDemoMode ? '#10b981' : '#ef4444' ?>">
                        <?= $isDemoMode ? 'AKTIF' : 'NONAKTIF' ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="assets/js/dashboard.js?v=<?= @filemtime(__DIR__ . '/../assets/js/dashboard.js') ?: time() ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        DashboardApp.init(<?= json_encode($currentUser) ?>);
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
