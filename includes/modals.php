<!-- Modal Login -->
<div class="modal-overlay" id="loginModal">
    <div class="modal">
        <button class="btn-close" onclick="App.closeLoginModal()" aria-label="Tutup Modal">×</button>
        <h2>Masuk ke Digi-Art</h2>
        <p class="modal-sub">Gunakan akun Siswa, Guru, atau Administrator untuk mengakses seluruh fitur.</p>

        <div id="loginError" style="display:none;padding:10px 14px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;border-radius:var(--radius-sm);font-size:13px;margin-bottom:14px"></div>

        <form onsubmit="App.handleLogin(event)">
            <div class="form-group">
                <label for="loginUsername">Username:</label>
                <input type="text" id="loginUsername" class="field-input" placeholder="Contoh: siti_aisyah" autocomplete="username" required>
            </div>
            <div class="form-group">
                <label for="loginPassword">Password:</label>
                <input type="password" id="loginPassword" class="field-input" placeholder="Masukkan password" autocomplete="current-password" required>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn secondary" onclick="App.closeLoginModal()">Batal</button>
                <button type="submit" id="btnLoginSubmit" class="btn">Masuk Akun</button>
            </div>
        </form>

        <?php
        require_once __DIR__ . '/../classes/SiteSetting.php';
        if (SiteSetting::isDemoModeEnabled()):
        ?>
        <div style="margin-top:24px;border-top:1px solid var(--line);padding-top:16px">
            <span style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase">Akses Cepat (Akun Contoh):</span>
            <div style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap">
                <button type="button" class="btn secondary small" onclick="fillDemoLogin('murid', 'murid123')">👩🏻‍🎨 Siswa (Siti)</button>
                <button type="button" class="btn secondary small" onclick="fillDemoLogin('guru', 'guru123')">👩🏻‍🏫 Guru (Ibu Nurul)</button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Profil & Ganti Password Mandiri oleh Siswa/User -->
<div class="modal-overlay" id="profileModal">
    <div class="modal">
        <button class="btn-close" onclick="App.closeProfileModal()" aria-label="Tutup Modal">×</button>
        <h2>Pengaturan Akun</h2>
        <p class="modal-sub">Anda dapat memperbarui username dan password Anda sendiri.</p>

        <div id="profileError" style="display:none;padding:10px 14px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;border-radius:var(--radius-sm);font-size:13px;margin-bottom:14px"></div>

        <div style="background:var(--bg);border:1px solid var(--line);border-radius:var(--radius-sm);padding:14px;margin-bottom:18px">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <div>
                    <strong id="profFullName" style="font-size:16px;color:var(--navy)">Nama Pengguna</strong>
                    <div style="font-size:12px;color:var(--muted)">Kelas: <span id="profKelas">VIII A</span></div>
                </div>
                <span id="profRoleTag" class="role-badge murid">MURID</span>
            </div>
        </div>

        <form onsubmit="App.handleUpdateProfile(event)">
            <div class="form-group">
                <label for="profUsername">Username Anda:</label>
                <input type="text" id="profUsername" class="field-input" required>
            </div>
            <div class="form-group">
                <label for="profNewPassword">Password Baru (Kosongkan jika tidak ingin mengubah):</label>
                <input type="password" id="profNewPassword" class="field-input" placeholder="Minimal 4 karakter">
            </div>
            <div class="form-group">
                <label for="profCurrentPassword">Password Saat Ini (Dibutuhkan untuk verifikasi perubahan password):</label>
                <input type="password" id="profCurrentPassword" class="field-input" placeholder="Masukkan password saat ini">
            </div>
            <div class="modal-actions" style="justify-content:space-between">
                <button type="button" class="btn secondary" style="color:#ef4444" onclick="App.handleLogout()">Keluar (Logout)</button>
                <div style="display:flex;gap:8px">
                    <button type="button" class="btn secondary" onclick="App.closeProfileModal()">Tutup</button>
                    <button type="submit" id="btnUpdateProfile" class="btn">Simpan Profil</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Upload Karya Pameran dengan Client-Side Image Compressor -->
<div class="modal-overlay" id="uploadModal">
    <div class="modal">
        <button class="btn-close" onclick="PameranApp.closeUploadModal()" aria-label="Tutup Modal">×</button>
        <h2>Unggah Karya ke Pameran</h2>
        <p class="modal-sub">
            Gambar otomatis dikompresi di browser agar hemat kuota dan ramah hosting gratis. 
            Karya akan ditinjau oleh guru sebelum diterbitkan.
        </p>

        <form id="workForm" onsubmit="PameranApp.submitWork(event)">
            <div class="form-group">
                <label for="workTitle">Judul Karya Ilustrasi:</label>
                <input type="text" id="workTitle" class="field-input" placeholder="Contoh: Siklus Air Presipitasi" required>
            </div>
            <div class="form-row" style="margin-bottom:14px">
                <div class="form-group" style="margin-bottom:0">
                    <label for="workCreatorName">Nama Siswa / Pembuat:</label>
                    <input type="text" id="workCreatorName" class="field-input" placeholder="Nama lengkap" required>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label for="workKelas">Kelas:</label>
                    <select id="workKelas" class="field-select">
                        <optgroup label="Kelas VII">
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
            </div>
            <div class="form-group">
                <label for="workDesc">Keterangan Karya (Ide, Pelajaran Terkait, Teknik):</label>
                <textarea id="workDesc" class="field-textarea" rows="3" placeholder="Ceritakan tujuan ilustrasi dan pesan yang ingin disampaikan."></textarea>
            </div>
            <div class="form-group">
                <label for="workFile">Pilih Gambar Karya (JPG/PNG/WebP atau dari Studio Digital):</label>
                <input type="file" id="workFile" class="field-input" accept="image/*" onchange="PameranApp.handleFileSelect(event)">
                <div id="compressInfo" style="display:none;margin-top:8px;font-size:12px"></div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn secondary" onclick="PameranApp.closeUploadModal()">Batal</button>
                <button type="submit" id="btnSubmitWork" class="btn">Kirim Karya</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah Nomor Materi Baru (Guru & Admin) -->
<div class="modal-overlay" id="addMateriModal">
    <div class="modal" style="max-width:620px">
        <button class="btn-close" onclick="DashboardApp.closeAddMateriModal()" aria-label="Tutup Modal">×</button>
        <h2>＋ Tambah Nomor Materi Baru</h2>
        <p class="modal-sub">Guru dapat menambah materi pembelajaran seni rupa baru dengan template dan gambar opsional.</p>

        <form id="addMateriForm" onsubmit="DashboardApp.submitAddMateri(event)">
            <div class="form-row">
                <div class="form-group">
                    <label for="newMateriNum">Nomor Materi (Urutan):</label>
                    <input type="number" id="newMateriNum" class="field-input" min="1" placeholder="Otomatis urutan terakhir" required>
                </div>
                <div class="form-group">
                    <label for="newMateriMode">Model Template:</label>
                    <select id="newMateriMode" class="field-select">
                        <option value="cards">Grid Kartu (Cards)</option>
                        <option value="list">Daftar Poin (List)</option>
                        <option value="prose">Paragraf Mengalir (Prose)</option>
                        <option value="steps">Langkah Bertahap (Steps)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="newMateriTitle">Judul Materi:</label>
                <input type="text" id="newMateriTitle" class="field-input" placeholder="Contoh: Teknik Pewarnaan Digital" required>
            </div>

            <div class="form-group">
                <label for="newMateriSubtitle">Subjudul / Topik Bahasan:</label>
                <input type="text" id="newMateriSubtitle" class="field-input" placeholder="Contoh: Mempelajari gradasi, opacity, dan pencahayaan ilustrasi">
            </div>

            <div class="form-group">
                <label for="newMateriImage">URL Gambar Ilustrasi Materi (Opsional):</label>
                <input type="text" id="newMateriImage" class="field-input" placeholder="Contoh: assets/img/sample-volcano.svg atau URL gambar web">
                <small style="color:var(--muted);font-size:11px">Kosongkan jika materi ini tidak memerlukan banner gambar pembuka.</small>
            </div>

            <div class="form-group">
                <label for="newMateriContent">Isi Materi / Poin-Poin:</label>
                <textarea id="newMateriContent" class="field-textarea" rows="4" placeholder="Tuliskan poin materi (1 baris per poin, format: Judul Poin: Penjelasan Poin) atau paragraf penjelasan materi." required></textarea>
            </div>

            <div class="form-group">
                <label for="newMateriSource">Catatan Sumber / Rujukan (Opsional):</label>
                <input type="text" id="newMateriSource" class="field-input" placeholder="Contoh: Buku Seni Budaya SMP Kelas VIII Kemendikbud">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn secondary" onclick="DashboardApp.closeAddMateriModal()">Batal</button>
                <button type="submit" id="btnSubmitAddMateri" class="btn">＋ Simpan Nomor Materi</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Isi & Gambar Materi (Guru & Admin) -->
<div class="modal-overlay" id="editMateriModal">
    <div class="modal" style="max-width:620px">
        <button class="btn-close" onclick="DashboardApp.closeEditMateriModal()" aria-label="Tutup Modal">×</button>
        <h2>✏️ Edit Isi & Gambar Materi</h2>
        <p class="modal-sub">Ubah judul, susunan teks, template, atau perbarui banner gambar materi.</p>

        <form id="editMateriForm" onsubmit="DashboardApp.submitEditMateri(event)">
            <input type="hidden" id="editMateriId">

            <div class="form-row">
                <div class="form-group">
                    <label for="editMateriNum">Nomor Materi (Urutan):</label>
                    <input type="number" id="editMateriNum" class="field-input" min="1" required>
                </div>
                <div class="form-group">
                    <label for="editMateriMode">Model Template:</label>
                    <select id="editMateriMode" class="field-select">
                        <option value="cards">Grid Kartu (Cards)</option>
                        <option value="list">Daftar Poin (List)</option>
                        <option value="prose">Paragraf Mengalir (Prose)</option>
                        <option value="steps">Langkah Bertahap (Steps)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="editMateriTitle">Judul Materi:</label>
                <input type="text" id="editMateriTitle" class="field-input" required>
            </div>

            <div class="form-group">
                <label for="editMateriSubtitle">Subjudul / Topik Bahasan:</label>
                <input type="text" id="editMateriSubtitle" class="field-input">
            </div>

            <div class="form-group">
                <label for="editMateriImage">URL Gambar Ilustrasi Materi (Opsional):</label>
                <input type="text" id="editMateriImage" class="field-input" placeholder="URL file gambar atau path lokal">
            </div>

            <div class="form-group">
                <label for="editMateriContent">Isi Materi (Poin / Teks Paragraf):</label>
                <textarea id="editMateriContent" class="field-textarea" rows="5" required></textarea>
            </div>

            <div class="form-group">
                <label for="editMateriSource">Catatan Sumber / Rujukan (Opsional):</label>
                <input type="text" id="editMateriSource" class="field-input">
            </div>

            <div class="modal-actions" style="justify-content:space-between">
                <button type="button" class="btn secondary" style="color:#ef4444" onclick="DashboardApp.deleteMateriFromModal()">
                    🗑️ Hapus Materi Ini
                </button>
                <div style="display:flex;gap:8px">
                    <button type="button" class="btn secondary" onclick="DashboardApp.closeEditMateriModal()">Batal</button>
                    <button type="submit" id="btnSubmitEditMateri" class="btn">Simpan Perubahan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah / Edit Soal Kuis (Guru & Admin) -->
<div class="modal-overlay" id="quizQuestionModal">
    <div class="modal" style="max-width:640px">
        <button class="btn-close" onclick="DashboardApp.closeQuizQuestionModal()" aria-label="Tutup Modal">×</button>
        <h2 id="quizQuestionModalTitle">＋ Tambah Soal Kuis Baru</h2>
        <p class="modal-sub">Atur pertanyaan, 4 opsi jawaban, kunci jawaban yang benar, batas waktu, dan poin soal bergaya Kahoot!.</p>

        <form id="quizQuestionForm" onsubmit="DashboardApp.submitQuizQuestion(event)">
            <input type="hidden" id="editQuizQuestionId" value="">

            <div class="form-group">
                <label for="qqQuestion">Teks Pertanyaan Soal:</label>
                <textarea id="qqQuestion" class="field-textarea" rows="3" placeholder="Contoh: Salah satu ciri khas karya seni rupa karikatur adalah..." required></textarea>
            </div>

            <div class="form-row" style="margin-bottom:12px">
                <div class="form-group" style="margin-bottom:0">
                    <label for="qqOptionA" style="color:#ef4444;font-weight:800">▲ Opsi A (Merah):</label>
                    <input type="text" id="qqOptionA" class="field-input" placeholder="Teks opsi A" required>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label for="qqOptionB" style="color:#2563eb;font-weight:800">◆ Opsi B (Biru):</label>
                    <input type="text" id="qqOptionB" class="field-input" placeholder="Teks opsi B" required>
                </div>
            </div>

            <div class="form-row" style="margin-bottom:12px">
                <div class="form-group" style="margin-bottom:0">
                    <label for="qqOptionC" style="color:#d97706;font-weight:800">● Opsi C (Kuning):</label>
                    <input type="text" id="qqOptionC" class="field-input" placeholder="Teks opsi C" required>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label for="qqOptionD" style="color:#059669;font-weight:800">◼ Opsi D (Hijau):</label>
                    <input type="text" id="qqOptionD" class="field-input" placeholder="Teks opsi D" required>
                </div>
            </div>

            <div class="form-row" style="margin-bottom:14px">
                <div class="form-group" style="margin-bottom:0">
                    <label for="qqCorrectIndex">Kunci Jawaban yang Benar:</label>
                    <select id="qqCorrectIndex" class="field-select">
                        <option value="0">▲ Opsi A (Merah)</option>
                        <option value="1">◆ Opsi B (Biru)</option>
                        <option value="2">● Opsi C (Kuning)</option>
                        <option value="3">◼ Opsi D (Hijau)</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label for="qqTimeLimit">Batas Waktu (Detik):</label>
                    <select id="qqTimeLimit" class="field-select">
                        <option value="10">10 Detik (Cepat)</option>
                        <option value="20" selected>20 Detik (Standar)</option>
                        <option value="30">30 Detik (Panjang)</option>
                        <option value="60">60 Detik (Ekstra)</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label for="qqPoints">Bobot Nilai Poin:</label>
                    <select id="qqPoints" class="field-select">
                        <option value="1000" selected>1000 Poin (Standar)</option>
                        <option value="2000">2000 Poin (Poin Ganda)</option>
                    </select>
                </div>
            </div>

            <div class="modal-actions" style="justify-content:space-between">
                <button type="button" class="btn secondary" id="btnDeleteQuizQuestion" style="display:none;color:#ef4444" onclick="DashboardApp.deleteQuizQuestionFromModal()">
                    🗑️ Hapus Soal Ini
                </button>
                <div style="display:flex;gap:8px;margin-left:auto">
                    <button type="button" class="btn secondary" onclick="DashboardApp.closeQuizQuestionModal()">Batal</button>
                    <button type="submit" id="btnSubmitQuizQuestion" class="btn">Simpan Soal Kuis</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah Akun Pengguna (Untuk Tampilan Mobile & Quick Action) -->
<div class="modal-overlay" id="createUserModal">
    <div class="modal" style="max-width:520px">
        <button class="btn-close" onclick="DashboardApp.closeCreateUserModal()" aria-label="Tutup Modal">×</button>
        <h2>＋ Tambah Akun Pengguna</h2>
        <p class="modal-sub">Daftarkan akun Siswa, Guru, atau Administrator baru ke dalam sistem.</p>

        <form id="modalAddUserForm" onsubmit="DashboardApp.createUserFromModal(event)">
            <div class="form-group">
                <label for="modalNewStudentName">Nama Lengkap:</label>
                <input type="text" id="modalNewStudentName" class="field-input" placeholder="Contoh: Ahmad Fauzan" required>
            </div>
            <div class="form-group">
                <label for="modalNewStudentUsername">Username Login:</label>
                <input type="text" id="modalNewStudentUsername" class="field-input" placeholder="Contoh: fauzan" required>
            </div>
            <div class="form-group">
                <label for="modalNewStudentPassword">Password Awal:</label>
                <input type="password" id="modalNewStudentPassword" class="field-input" placeholder="Minimal 4 karakter" required>
            </div>

            <div class="form-group" id="modalGroupRoleInput">
                <label for="modalNewUserRole">Peran Akun (Role):</label>
                <select id="modalNewUserRole" class="field-select" onchange="DashboardApp.toggleModalKelasField(this.value)">
                    <option value="murid">Siswa (Murid)</option>
                    <option value="guru">Guru Seni Rupa</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>

            <div class="form-group" id="modalGroupKelasInput">
                <label for="modalNewStudentKelas">Kelas Siswa:</label>
                <select id="modalNewStudentKelas" class="field-select">
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

            <div class="modal-actions">
                <button type="button" class="btn secondary" onclick="DashboardApp.closeCreateUserModal()">Batal</button>
                <button type="submit" id="btnModalCreateUser" class="btn">Simpan Akun</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Akun Pengguna (Untuk Admin dan Guru) -->
<div class="modal-overlay" id="editUserModal">
    <div class="modal" style="max-width:520px">
        <button class="btn-close" onclick="DashboardApp.closeEditUserModal()" aria-label="Tutup Modal">×</button>
        <h2>✏️ Edit Akun Pengguna</h2>
        <p class="modal-sub">Perbarui nama siswa/guru, username, kelas, atau ganti password akun.</p>

        <form id="modalEditUserForm" onsubmit="DashboardApp.submitEditUser(event)">
            <input type="hidden" id="editUserId" value="">

            <div class="form-group">
                <label for="editFullName">Nama Lengkap:</label>
                <input type="text" id="editFullName" class="field-input" placeholder="Contoh: Ahmad Fauzan" required>
            </div>

            <div class="form-group">
                <label for="editUsername">Username Login:</label>
                <input type="text" id="editUsername" class="field-input" placeholder="Contoh: fauzan" required>
            </div>

            <div class="form-group">
                <label for="editPassword">Password Baru (Opsional):</label>
                <input type="password" id="editPassword" class="field-input" placeholder="Kosongkan jika tidak ingin mengubah password">
                <small style="color:var(--muted);font-size:11px">Biarkan kosong jika tidak ingin mereset password akun (minimal 4 karakter).</small>
            </div>

            <div class="form-group" id="editGroupRole">
                <label for="editUserRole">Peran Akun (Role):</label>
                <select id="editUserRole" class="field-select" onchange="DashboardApp.toggleEditKelasField(this.value)">
                    <option value="murid">Siswa (Murid)</option>
                    <option value="guru">Guru Seni Rupa</option>
                    <option value="admin">Administrator</option>
                </select>
                <small id="editRoleHint" style="color:var(--muted);font-size:11px;display:none">Guru hanya dapat mengelola akun siswa.</small>
            </div>

            <div class="form-group" id="editGroupKelas">
                <label for="editUserKelas">Kelas Siswa:</label>
                <select id="editUserKelas" class="field-select">
                    <optgroup label="Kelas VII (7A s/d 7F)">
                        <option>VII A</option>
                        <option>VII B</option>
                        <option>VII C</option>
                        <option>VII D</option>
                        <option>VII E</option>
                        <option>VII F</option>
                    </optgroup>
                    <optgroup label="Kelas VIII">
                        <option>VIII A</option>
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

            <div class="modal-actions">
                <button type="button" class="btn secondary" onclick="DashboardApp.closeEditUserModal()">Batal</button>
                <button type="submit" id="btnSubmitEditUser" class="btn">💾 Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>



