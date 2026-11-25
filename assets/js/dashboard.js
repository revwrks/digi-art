/**
 * Dashboard Guru & Admin Module
 * Fitur:
 * 1. Manajemen Approval Karya Siswa (Approve, Reject, Delete, & Kurator Resmi)
 * 2. Manajemen Materi Fleksibel (Tambah & Kurang Nomor, Ganti Isi, Pasang Gambar Opsional, Toggle Card On/Off)
 * 3. Manajemen Akun Pengguna (Admin & Guru: Buat Akun & Hapus Akun dengan Hirarki Role Ketat)
 * 4. Pengaturan Hiasan & Tampilan Beranda (Index) Fleksibel oleh Administrator
 * 5. Rekap Statistik dan Log Aktivitas Sistem
 */

const DashboardApp = {
    currentTab: 'pameran',
    currentUser: null,
    pendingWorks: [],
    materiList: [],

    // 100% Client-Side Pagination States
    usersList: [],
    usersFilteredList: [],
    usersPage: 1,
    usersPageSize: 15,
    usersSearchQuery: '',
    usersClassFilter: '',

    pameranWorks: [],
    pameranPage: 1,
    pameranPageSize: 10,

    quizQuestions: [],
    quizPage: 1,
    quizPageSize: 8,

    allActivityLogs: [],
    activityLogsPage: 1,
    activityLogsPageSize: 15,
    activityLogsTotalPages: 1,

    userViewMode: (window.innerWidth <= 768) ? 'cards' : 'table',

    getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    },

    async apiPost(url, payload) {
        const body = JSON.stringify(payload);
        if (window.App && typeof window.App.apiFetch === 'function') {
            return window.App.apiFetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body
            });
        }
        const headers = { 'Content-Type': 'application/json' };
        const csrf = this.getCsrfToken();
        if (csrf) {
            headers['X-CSRF-Token'] = csrf;
        }
        return fetch(url, {
            method: 'POST',
            headers,
            body
        });
    },

    safeUrl(url, fallback = '') {
        const raw = String(url || '').trim();
        if (!raw) return fallback;
        if (window.App && typeof window.App.safeUrl === 'function') {
            const res = window.App.safeUrl(raw);
            if (!res || res === '#') return fallback;
            return res;
        }
        if (/[\x00-\x1F\x7F]/.test(raw) || raw.includes('\\') || raw.includes('..')) {
            return fallback;
        }
        if (raw.startsWith('assets/') || raw.startsWith('uploads/')) {
            if (raw.includes('//') || /^[a-zA-Z][a-zA-Z0-9+.-]*:/.test(raw)) return fallback;
            return raw;
        }
        try {
            const parsed = new URL(raw, window.location.origin);
            if (parsed.protocol === 'https:') return parsed.href;
        } catch (e) {}
        return fallback;
    },

    init(user = null) {
        this.currentUser = user || window.currentUser;
        this.bindTabs();
        this.loadPameranApprovals();
        this.loadMateriManagement();
        this.loadQuizManagement();
        this.loadStudentsManagement();
        this.loadActivityLogs();
        this.loadStats();
    },

    switchTab(target) {
        const tabs = document.querySelectorAll('.dash-nav-btn');
        tabs.forEach(t => t.classList.toggle('active', t.dataset.tab === target));
        document.querySelectorAll('.dash-panel').forEach(p => p.classList.remove('active'));
        const panel = document.getElementById(`panel-${target}`);
        if (panel) panel.classList.add('active');
        this.currentTab = target;

        if (target === 'kuis') {
            this.loadQuizManagement();
        } else if (target === 'log') {
            this.loadActivityLogs();
        }
    },

    bindTabs() {
        const tabs = document.querySelectorAll('.dash-nav-btn');
        tabs.forEach(btn => {
            btn.addEventListener('click', () => {
                this.switchTab(btn.dataset.tab);
            });
        });

        const hash = window.location.hash.replace('#', '').replace('-tab', '');
        if (['pameran', 'materi', 'kuis', 'siswa', 'log', 'dekorasi'].includes(hash)) {
            this.switchTab(hash);
        }
    },

    /**
     * Komponen Generic Client-Side Pagination Bar
     */
    renderPaginationBar({
        containerId,
        currentPage,
        totalPages,
        totalItems,
        startIndex,
        endIndex,
        itemLabel = 'data',
        onPageChangeFnName
    }) {
        const container = document.getElementById(containerId);
        if (!container) return;

        if (totalItems <= 0) {
            container.style.display = 'none';
            container.innerHTML = '';
            return;
        }

        container.style.display = 'flex';

        const showingFrom = startIndex + 1;
        const showingTo = Math.min(endIndex, totalItems);
        const infoText = `Menampilkan <strong>${showingFrom}–${showingTo}</strong> dari <strong>${totalItems}</strong> ${itemLabel} (Halaman ${currentPage}/${totalPages})`;

        if (totalPages <= 1) {
            container.innerHTML = `
                <div class="pagination-info">${infoText}</div>
                <div class="pagination-btn-group">
                    <button type="button" class="btn-page active" disabled>1</button>
                </div>
            `;
            return;
        }

        let buttonsHtml = [];

        // Tombol Sebelumnya
        buttonsHtml.push(`
            <button type="button" class="btn-page" ${currentPage === 1 ? 'disabled' : ''} onclick="DashboardApp.${onPageChangeFnName}(${currentPage - 1})" title="Halaman Sebelumnya">
                « Sebelumnya
            </button>
        `);

        // Smart Page Numbers (Max 7 buttons)
        let pageNumbers = [];
        if (totalPages <= 7) {
            for (let i = 1; i <= totalPages; i++) pageNumbers.push(i);
        } else {
            pageNumbers.push(1);
            if (currentPage > 4) pageNumbers.push('...');
            const start = Math.max(2, currentPage - 1);
            const end = Math.min(totalPages - 1, currentPage + 1);
            for (let i = start; i <= end; i++) pageNumbers.push(i);
            if (currentPage < totalPages - 3) pageNumbers.push('...');
            pageNumbers.push(totalPages);
        }

        pageNumbers.forEach(p => {
            if (p === '...') {
                buttonsHtml.push(`<span style="padding:0 4px;color:var(--muted);font-weight:700">…</span>`);
            } else {
                buttonsHtml.push(`
                    <button type="button" class="btn-page ${p === currentPage ? 'active' : ''}" onclick="DashboardApp.${onPageChangeFnName}(${p})">
                        ${p}
                    </button>
                `);
            }
        });

        // Tombol Berikutnya
        buttonsHtml.push(`
            <button type="button" class="btn-page" ${currentPage === totalPages ? 'disabled' : ''} onclick="DashboardApp.${onPageChangeFnName}(${currentPage + 1})" title="Halaman Berikutnya">
                Berikutnya »
            </button>
        `);

        container.innerHTML = `
            <div class="pagination-info">${infoText}</div>
            <div class="pagination-btn-group">${buttonsHtml.join('')}</div>
        `;
    },

    // ==========================================
    // 1. MANAJEMEN APPROVAL KARYA PAMERAN (PAGINASI CLIENT-SIDE)
    // ==========================================
    async loadPameranApprovals(filterStatus = null) {
        const tableBody = document.getElementById('dashPameranTable');
        if (!tableBody) return;

        tableBody.innerHTML = '<tr><td colspan="6" style="text-align:center">Memuat antrean karya…</td></tr>';

        try {
            const url = filterStatus ? `api/pameran.php?action=get_all_teacher&status=${encodeURIComponent(filterStatus)}` : 'api/pameran.php?action=get_all_teacher';
            const res = await fetch(url);
            const data = await res.json();
            if (!data.success && data.message) {
                throw new Error(data.message);
            }
            this.pameranWorks = data.works || [];
            this.pameranPage = 1;
            this.renderPameranPage();
        } catch (err) {
            tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:#e74c3c">Gagal memuat karya: ${this.escapeHTML(err.message)}</td></tr>`;
        }
    },

    setPameranPage(page) {
        this.pameranPage = page;
        this.renderPameranPage();
    },

    renderPameranPage() {
        const tableBody = document.getElementById('dashPameranTable');
        if (!tableBody) return;

        const totalItems = this.pameranWorks.length;
        if (!totalItems) {
            tableBody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:24px;color:var(--muted)">Tidak ada karya dalam status ini.</td></tr>';
            this.renderPaginationBar({
                containerId: 'dashPameranPagination',
                currentPage: 1,
                totalPages: 0,
                totalItems: 0,
                startIndex: 0,
                endIndex: 0,
                itemLabel: 'karya',
                onPageChangeFnName: 'setPameranPage'
            });
            return;
        }

        const pageSize = this.pameranPageSize;
        const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
        if (this.pameranPage > totalPages) this.pameranPage = totalPages;
        if (this.pameranPage < 1) this.pameranPage = 1;

        const startIndex = (this.pameranPage - 1) * pageSize;
        const endIndex = Math.min(startIndex + pageSize, totalItems);
        const pageItems = this.pameranWorks.slice(startIndex, endIndex);

        tableBody.innerHTML = pageItems.map(w => {
            const workId = Number(w.id) || 0;
            const safeImg = this.safeUrl(w.image_path, 'assets/img/sample-volcano.svg');
            let statusBadge = '';
            if (w.status === 'pending') {
                statusBadge = '<span class="status-badge pending">Menunggu Persetujuan</span>';
            } else if (w.status === 'approved') {
                statusBadge = `<span class="status-badge approved">Disetujui</span><br><small style="color:var(--muted);font-size:10px">Kurator: ${this.escapeHTML(w.curator_name || 'Guru')}</small>`;
            } else {
                statusBadge = '<span class="status-badge rejected">Ditolak</span>';
            }

            return `
                <tr>
                    <td>
                        <img src="${this.escapeHTML(safeImg)}" alt="${this.escapeHTML(w.title || '')}" class="dash-thumb" onerror="this.src='assets/img/sample-volcano.svg'">
                    </td>
                    <td>
                        <strong>${this.escapeHTML(w.title || '')}</strong><br>
                        <small style="color:var(--muted)">${this.escapeHTML(w.description || '—')}</small>
                    </td>
                    <td>
                        <strong>${this.escapeHTML(w.creator_name || '')}</strong><br>
                        <small>${this.escapeHTML(w.kelas || 'VIII')}</small>
                    </td>
                    <td>${statusBadge}</td>
                    <td><small>${this.escapeHTML(this.formatDate(w.created_at))}</small></td>
                    <td>
                        <div class="dash-action-btns">
                            ${w.status === 'pending' ? `
                                <button class="btn-dash approve" onclick="DashboardApp.approveWork(${workId})">Setujui</button>
                                <button class="btn-dash reject" onclick="DashboardApp.rejectWork(${workId})">Tolak</button>
                            ` : ''}
                            ${w.status === 'rejected' ? `
                                <button class="btn-dash approve" onclick="DashboardApp.approveWork(${workId})">Pulihkan &amp; Setujui</button>
                            ` : ''}
                            <button class="btn-dash delete" onclick="DashboardApp.deleteWork(${workId})">Hapus</button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        this.renderPaginationBar({
            containerId: 'dashPameranPagination',
            currentPage: this.pameranPage,
            totalPages,
            totalItems,
            startIndex,
            endIndex,
            itemLabel: 'karya',
            onPageChangeFnName: 'setPameranPage'
        });
    },

    async approveWork(id) {
        try {
            const res = await this.apiPost('api/pameran.php?action=approve', { id });
            const data = await res.json();
            if (data.success) {
                window.showToast?.('Karya disetujui. Anda tercatat sebagai kurator resmi karya.');
                this.loadPameranApprovals();
                this.loadStats();
            } else {
                window.showToast?.(data.message || 'Gagal menyetujui karya.');
            }
        } catch (e) {
            window.showToast?.('Gagal menyetujui karya.');
        }
    },

    async rejectWork(id) {
        try {
            const res = await this.apiPost('api/pameran.php?action=reject', { id });
            const data = await res.json();
            if (data.success) {
                window.showToast?.('Karya ditolak.');
                this.loadPameranApprovals();
            } else {
                window.showToast?.(data.message || 'Gagal menolak karya.');
            }
        } catch (e) {
            window.showToast?.('Gagal menolak karya.');
        }
    },

    async deleteWork(id) {
        if (!confirm('Apakah Anda yakin ingin menghapus karya ini secara permanen dari pameran?')) return;
        try {
            const res = await this.apiPost('api/pameran.php?action=delete', { id });
            const data = await res.json();
            if (data.success) {
                window.showToast?.('Karya berhasil dihapus.');
                this.loadPameranApprovals();
                this.loadStats();
            } else {
                window.showToast?.(data.message || 'Gagal menghapus karya.');
            }
        } catch (e) {
            window.showToast?.('Gagal menghapus karya.');
        }
    },

    // ==========================================
    // 2. MANAJEMEN MATERI (TAMBAH/KURANG NOMOR, ISI, GAMBAR)
    // ==========================================
    async loadMateriManagement() {
        const container = document.getElementById('dashMateriList');
        if (!container) return;

        container.innerHTML = '<p>Memuat pengaturan urutan dan isi materi…</p>';

        try {
            const res = await fetch('api/materi.php?action=get_all');
            const data = await res.json();
            this.materiList = data.sections || [];

            // Isi nilai form header materi (Bab, Judul, Deskripsi) jika ada
            if (data.header) {
                const babInput = document.getElementById('materiBabInput');
                const titleInput = document.getElementById('materiTitleInput');
                const descInput = document.getElementById('materiDescInput');
                if (babInput && data.header.bab !== undefined) babInput.value = data.header.bab;
                if (titleInput && data.header.title !== undefined) titleInput.value = data.header.title;
                if (descInput && data.header.description !== undefined) descInput.value = data.header.description;
            }

            if (!this.materiList.length) {
                container.innerHTML = `
                    <div style="text-align:center;padding:40px;background:#f8fafc;border-radius:var(--radius-md);border:1px dashed var(--line)">
                        <p style="color:var(--muted)">Belum ada materi pembelajaran yang dibuat.</p>
                        <button class="btn small" onclick="DashboardApp.openAddMateriModal()">Buat Materi Pertama</button>
                    </div>
                `;
                return;
            }

            container.innerHTML = this.materiList.map(sec => {
                const secId = Number(sec.id) || 0;
                const secNum = Number(sec.section_number) || 0;
                const safeSecImg = this.safeUrl(sec.image_url, '');

                return `
                <div class="materi-manage-card" id="materiItem_${secId}">
                    <div class="materi-manage-header">
                        <div style="display:flex;align-items:center;gap:12px">
                            <span class="sec-badge">No. ${secNum}</span>
                            <div>
                                <h4 style="margin:0">${this.escapeHTML(sec.title || '')}</h4>
                                <small style="color:var(--muted)">${this.escapeHTML(sec.subtitle || '—')}</small>
                            </div>
                        </div>
                        <div class="materi-toggle-zone">
                            <!-- Toggle Card On/Off sesuai permintaan user -->
                            <label class="switch-toggle" title="Aktifkan atau Hilangkan Kartu pada nomor materi ini">
                                <input type="checkbox" ${sec.show_cards == 1 ? 'checked' : ''} 
                                       onchange="DashboardApp.toggleCard(${secId}, this.checked)">
                                <span class="slider"></span>
                            </label>
                            <span class="toggle-label">${sec.show_cards == 1 ? 'Tampilan Card AKTIF' : 'Card NONAKTIF'}</span>
                        </div>
                    </div>

                    <div class="materi-manage-body">
                        <!-- Pratinjau Gambar Banner Materi jika ada -->
                        ${sec.image_url ? `
                            <div style="margin-bottom:14px;display:flex;align-items:center;gap:12px;background:#f8fafc;padding:8px 12px;border-radius:var(--radius-sm);border:1px solid var(--line)">
                                ${safeSecImg ? `<img src="${this.escapeHTML(safeSecImg)}" alt="Banner Materi" style="width:60px;height:45px;object-fit:cover;border-radius:4px" onerror="this.style.display='none'">` : ''}
                                <div style="flex:1;font-size:12px;overflow:hidden;text-overflow:ellipsis">
                                    <strong>Gambar Materi:</strong> <code>${this.escapeHTML(sec.image_url)}</code>
                                </div>
                            </div>
                        ` : ''}

                        <div class="form-row">
                            <div class="form-group">
                                <label>Model Template Tampilan:</label>
                                <select class="field-select" id="mode_${secId}" onchange="DashboardApp.changeDisplayMode(${secId}, this.value)">
                                    <option value="cards" ${sec.display_mode === 'cards' ? 'selected' : ''}>Grid Kartu (Cards)</option>
                                    <option value="list" ${sec.display_mode === 'list' ? 'selected' : ''}>Daftar Terstruktur (List)</option>
                                    <option value="prose" ${sec.display_mode === 'prose' ? 'selected' : ''}>Paragraf Mengalir (Prose)</option>
                                    <option value="steps" ${sec.display_mode === 'steps' ? 'selected' : ''}>Langkah Berurutan (Steps)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Judul Bagian:</label>
                                <input type="text" class="field-input" id="title_${secId}" value="${this.escapeHTML(sec.title || '')}">
                            </div>
                        </div>

                        <div class="form-group" style="margin-top:10px">
                            <label>URL Gambar Ilustrasi Materi (Opsional):</label>
                            <input type="text" class="field-input" id="img_${secId}" value="${this.escapeHTML(sec.image_url || '')}" placeholder="Contoh: assets/img/sample-volcano.svg">
                        </div>

                        <div class="form-group" style="margin-top:10px">
                            <label>Catatan Sumber / Rujukan Belajar:</label>
                            <input type="text" class="field-input" id="source_${secId}" value="${this.escapeHTML(sec.source_note || '')}">
                        </div>

                        <div style="margin-top:14px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
                            <button class="btn-dash delete" style="padding:6px 12px" onclick="DashboardApp.deleteMateri(${secId})">
                                Hapus Nomor Ini
                            </button>
                            <div style="display:flex;gap:8px">
                                <button class="btn secondary small" onclick="DashboardApp.openEditMateriModal(${secId})">
                                    Edit Isi Lengkap
                                </button>
                                <button class="btn small" onclick="DashboardApp.saveMateri(${secId})">
                                    Simpan Perubahan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            }).join('');

        } catch (err) {
            container.innerHTML = `<p style="color:#e74c3c">Gagal memuat materi: ${this.escapeHTML(err.message)}</p>`;
        }
    },

    openAddMateriModal() {
        const modal = document.getElementById('addMateriModal');
        if (!modal) return;
        document.getElementById('addMateriForm')?.reset();
        
        // Nomor materi berikutnya
        const maxNum = this.materiList.reduce((max, s) => Math.max(max, parseInt(s.section_number, 10) || 0), 0);
        const numInput = document.getElementById('newMateriNum');
        if (numInput) numInput.value = maxNum + 1;

        modal.style.display = 'flex';
    },

    closeAddMateriModal() {
        const modal = document.getElementById('addMateriModal');
        if (modal) modal.style.display = 'none';
    },

    async submitAddMateri(e) {
        if (e) e.preventDefault();

        const section_number = parseInt(document.getElementById('newMateriNum')?.value, 10) || 1;
        const title = document.getElementById('newMateriTitle')?.value.trim();
        const subtitle = document.getElementById('newMateriSubtitle')?.value.trim();
        const display_mode = document.getElementById('newMateriMode')?.value || 'cards';
        const image_url = document.getElementById('newMateriImage')?.value.trim();
        const source_note = document.getElementById('newMateriSource')?.value.trim();
        const rawContent = document.getElementById('newMateriContent')?.value.trim();
        const btn = document.getElementById('btnSubmitAddMateri');

        if (!title || !rawContent) {
            window.showToast?.('Judul materi dan isi materi wajib diisi.');
            return;
        }

        // Parse isi materi ke bentuk item terstruktur
        const items = this.parseRawContent(rawContent, title);

        if (btn) btn.disabled = true;

        try {
            const res = await this.apiPost('api/materi.php?action=add_section', {
                section_number,
                title,
                subtitle,
                display_mode,
                image_url,
                source_note,
                content: { items }
            });
            const data = await res.json();
            if (data.success) {
                window.showToast?.(data.message || 'Materi baru berhasil ditambahkan.');
                this.closeAddMateriModal();
                this.loadMateriManagement();
            } else {
                window.showToast?.(data.message || 'Gagal menambahkan materi.');
            }
        } catch (err) {
            window.showToast?.('Gagal menambahkan materi: ' + err.message);
        } finally {
            if (btn) btn.disabled = false;
        }
    },

    openEditMateriModal(id) {
        const sec = this.materiList.find(s => s.id === id);
        if (!sec) return;

        const modal = document.getElementById('editMateriModal');
        if (!modal) return;

        document.getElementById('editMateriId').value = sec.id;
        document.getElementById('editMateriNum').value = sec.section_number;
        document.getElementById('editMateriTitle').value = sec.title;
        document.getElementById('editMateriSubtitle').value = sec.subtitle || '';
        document.getElementById('editMateriMode').value = sec.display_mode || 'cards';
        document.getElementById('editMateriImage').value = sec.image_url || '';
        document.getElementById('editMateriSource').value = sec.source_note || '';

        // Format isi materi untuk diedit di textarea
        const content = sec.content || {};
        let textContent = '';
        if (content.items && Array.isArray(content.items)) {
            textContent = content.items.map(it => `${it.title || ''}: ${it.desc || ''}`).join('\n');
        } else if (content.paragraphs && Array.isArray(content.paragraphs)) {
            textContent = content.paragraphs.join('\n\n');
        } else {
            textContent = JSON.stringify(content, null, 2);
        }
        document.getElementById('editMateriContent').value = textContent;

        modal.style.display = 'flex';
    },

    closeEditMateriModal() {
        const modal = document.getElementById('editMateriModal');
        if (modal) modal.style.display = 'none';
    },

    async submitEditMateri(e) {
        if (e) e.preventDefault();

        const id = parseInt(document.getElementById('editMateriId')?.value, 10);
        const section_number = parseInt(document.getElementById('editMateriNum')?.value, 10) || 1;
        const title = document.getElementById('editMateriTitle')?.value.trim();
        const subtitle = document.getElementById('editMateriSubtitle')?.value.trim();
        const display_mode = document.getElementById('editMateriMode')?.value;
        const image_url = document.getElementById('editMateriImage')?.value.trim();
        const source_note = document.getElementById('editMateriSource')?.value.trim();
        const rawContent = document.getElementById('editMateriContent')?.value.trim();
        const btn = document.getElementById('btnSubmitEditMateri');

        if (!title || !rawContent) {
            window.showToast?.('Judul dan isi materi tidak boleh kosong.');
            return;
        }

        const items = this.parseRawContent(rawContent, title);

        if (btn) btn.disabled = true;

        try {
            const res = await this.apiPost('api/materi.php?action=update_section', {
                id,
                section_number,
                title,
                subtitle,
                display_mode,
                image_url,
                source_note,
                content: { items }
            });
            const data = await res.json();
            if (data.success) {
                window.showToast?.('Materi berhasil diperbarui.');
                this.closeEditMateriModal();
                this.loadMateriManagement();
            } else {
                window.showToast?.(data.message || 'Gagal menyimpan materi.');
            }
        } catch (err) {
            window.showToast?.('Gagal menyimpan materi: ' + err.message);
        } finally {
            if (btn) btn.disabled = false;
        }
    },

    async deleteMateriFromModal() {
        const id = parseInt(document.getElementById('editMateriId')?.value, 10);
        const title = document.getElementById('editMateriTitle')?.value;
        const num = document.getElementById('editMateriNum')?.value;
        this.closeEditMateriModal();
        this.deleteMateri(id, title, num);
    },

    async deleteMateri(id, title = null, num = null) {
        const sec = this.materiList.find(s => Number(s.id) === Number(id));
        const secTitle = title !== null ? title : (sec?.title || '');
        const secNum = num !== null ? num : (sec?.section_number || id);
        if (!confirm(`Apakah Anda yakin ingin menghapus nomor materi ${secNum}: "${secTitle}"?`)) return;

        try {
            const res = await this.apiPost('api/materi.php?action=delete_section', { id });
            const data = await res.json();
            if (data.success) {
                window.showToast?.(data.message || 'Materi berhasil dihapus.');
                this.loadMateriManagement();
            } else {
                window.showToast?.(data.message || 'Gagal menghapus materi.');
            }
        } catch (e) {
            window.showToast?.('Gagal menghapus materi.');
        }
    },

    parseRawContent(raw, fallbackTitle) {
        const lines = raw.split('\n').map(l => l.trim()).filter(Boolean);
        const items = [];
        lines.forEach((line, idx) => {
            if (line.includes(':')) {
                const parts = line.split(':');
                const t = parts[0].trim();
                const d = parts.slice(1).join(':').trim();
                items.push({ tag: String(idx + 1).padStart(2, '0'), title: t, desc: d });
            } else {
                items.push({ tag: String(idx + 1).padStart(2, '0'), title: `Poin ${idx + 1}`, desc: line });
            }
        });
        return items.length ? items : [{ tag: '01', title: fallbackTitle, desc: raw }];
    },

    async toggleCard(id, isChecked) {
        try {
            const res = await this.apiPost('api/materi.php?action=toggle_cards', { id, show_cards: isChecked ? 1 : 0 });
            const data = await res.json();
            if (data.success) {
                window.showToast?.(data.message || 'Pengaturan kartu diperbarui.');
                this.loadMateriManagement();
            } else {
                window.showToast?.(data.message || 'Gagal mengubah pengaturan kartu.');
            }
        } catch (e) {
            window.showToast?.('Gagal mengubah pengaturan kartu.');
        }
    },

    async changeDisplayMode(id, newMode) {
        try {
            const res = await this.apiPost('api/materi.php?action=update_section', { id, display_mode: newMode });
            const data = await res.json();
            if (data.success) {
                window.showToast?.(`Model tampilan diubah ke: ${newMode}`);
            } else {
                window.showToast?.(data.message || 'Gagal mengubah model tampilan.');
            }
        } catch (e) {
            window.showToast?.('Gagal mengubah model tampilan.');
        }
    },

    async saveMateri(id) {
        const title = document.getElementById(`title_${id}`)?.value.trim();
        const source_note = document.getElementById(`source_${id}`)?.value.trim();
        const display_mode = document.getElementById(`mode_${id}`)?.value;
        const image_url = document.getElementById(`img_${id}`)?.value.trim();

        try {
            const res = await this.apiPost('api/materi.php?action=update_section', { id, title, source_note, display_mode, image_url });
            const data = await res.json();
            if (data.success) {
                window.showToast?.('Materi berhasil disimpan.');
                this.loadMateriManagement();
            } else {
                window.showToast?.(data.message || 'Gagal menyimpan materi.');
            }
        } catch (e) {
            window.showToast?.('Gagal menyimpan materi.');
        }
    },

    async saveMateriHeader() {
        const babInput = document.getElementById('materiBabInput');
        const titleInput = document.getElementById('materiTitleInput');
        const descInput = document.getElementById('materiDescInput');
        const btn = document.getElementById('btnSaveMateriHeader');

        const bab = babInput?.value.trim() || '';
        const title = titleInput?.value.trim() || '';
        const description = descInput?.value.trim() || '';

        if (!title) {
            window.showToast?.('Judul materi wajib diisi.');
            titleInput?.focus();
            return;
        }

        const originalText = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = 'Menyimpan…';
        }

        try {
            const res = await this.apiPost('api/materi.php?action=update_header', { bab, title, description });
            const data = await res.json();

            if (data.success) {
                window.showToast?.(data.message || 'Informasi materi berhasil diperbarui!');
                if (data.header) {
                    if (babInput) babInput.value = data.header.bab;
                    if (titleInput) titleInput.value = data.header.title;
                    if (descInput) descInput.value = data.header.description;
                }
            } else {
                window.showToast?.(data.message || 'Gagal menyimpan header materi.');
            }
        } catch (err) {
            window.showToast?.('Terjadi kesalahan: ' + err.message);
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }
    },

    // ==========================================
    // 3. MANAJEMEN AKUN PENGGUNA (ROLE & HAK HAPUS)
    // ==========================================
    async loadStudentsManagement() {
        const tableBody = document.getElementById('dashStudentsTable');
        if (!tableBody) return;

        try {
            const res = await fetch('api/users.php?action=list_users');
            const data = await res.json();
            if (!data.success && data.message) {
                throw new Error(data.message);
            }
            this.usersList = data.users || [];
            if (data.current_role) {
                this.currentUser = {
                    ...(this.currentUser || {}),
                    role: data.current_role,
                    id: data.current_user_id || this.currentUser?.id || 0
                };
            }
            this.applyUsersFilterAndRender();
        } catch (err) {
            tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:#e74c3c">Gagal memuat data pengguna: ${this.escapeHTML(err.message)}</td></tr>`;
        }
    },

    onUsersSearch(query) {
        this.usersSearchQuery = (query || '').trim();
        this.usersPage = 1;
        this.applyUsersFilterAndRender();
    },

    onUsersClassFilter(kelas) {
        this.usersClassFilter = (kelas || '').trim();
        this.usersPage = 1;
        this.applyUsersFilterAndRender();
    },

    onUsersPageSize(size) {
        this.usersPageSize = size;
        this.usersPage = 1;
        this.applyUsersFilterAndRender();
    },

    setUsersPage(page) {
        this.usersPage = page;
        this.applyUsersFilterAndRender();
        const main = document.querySelector('.dash-user-mgmt-main');
        if (main) main.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },

    applyUsersFilterAndRender() {
        const tableBody = document.getElementById('dashStudentsTable');
        const cardsContainer = document.getElementById('dashStudentsCardsGrid');
        if (!tableBody) return;

        const q = this.usersSearchQuery.toLowerCase();
        const cFilter = this.usersClassFilter;

        this.usersFilteredList = this.usersList.filter(u => {
            const nameMatch = !q || (u.full_name && u.full_name.toLowerCase().includes(q)) || (u.username && u.username.toLowerCase().includes(q));
            const classMatch = !cFilter || (u.kelas === cFilter);
            return nameMatch && classMatch;
        });

        const totalItems = this.usersFilteredList.length;

        if (!totalItems) {
            tableBody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:24px;color:var(--muted)">Tidak ada akun yang sesuai dengan pencarian atau filter kelas.</td></tr>';
            if (cardsContainer) cardsContainer.innerHTML = '<div style="text-align:center;padding:24px;color:var(--muted)">Tidak ada akun yang sesuai dengan pencarian atau filter kelas.</div>';
            this.renderPaginationBar({
                containerId: 'dashStudentsPagination',
                currentPage: 1,
                totalPages: 0,
                totalItems: 0,
                startIndex: 0,
                endIndex: 0,
                itemLabel: 'pengguna',
                onPageChangeFnName: 'setUsersPage'
            });
            return;
        }

        const pageSize = (this.usersPageSize === 'all') ? totalItems : parseInt(this.usersPageSize, 10);
        const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
        if (this.usersPage > totalPages) this.usersPage = totalPages;
        if (this.usersPage < 1) this.usersPage = 1;

        const startIndex = (this.usersPage - 1) * pageSize;
        const endIndex = Math.min(startIndex + pageSize, totalItems);
        const pageItems = (this.usersPageSize === 'all') ? this.usersFilteredList : this.usersFilteredList.slice(startIndex, endIndex);

        const currentRole = (this.currentUser?.role || window.currentUser?.role || 'guru').toLowerCase();
        const isCallerAdmin = (currentRole === 'admin');
        const isCallerGuru  = (currentRole === 'guru');
        const callerId      = Number(this.currentUser?.id || window.currentUser?.id || 0);

        // Render Tampilan 1: Tabel Desktop
        tableBody.innerHTML = pageItems.map((u, idx) => {
            const rowNumber = startIndex + idx + 1;
            const uid = Number(u.id) || 0;
            const roleSlug = ['admin', 'guru', 'murid'].includes(u.role) ? u.role : 'murid';
            const isSelf = Boolean(u.is_self || (callerId && uid === callerId));

            // Hak Akses Edit:
            // 1. Akun siswa (murid) SELALU bisa diedit di dashboard (baik oleh Guru maupun Admin)
            // 2. Akun sendiri SELALU bisa diedit oleh pemiliknya
            // 3. Admin bisa mengedit SEMUA akun (admin, guru, murid)
            // 4. Fallback jika flag can_edit dari server bernilai true
            const canEdit = isCallerAdmin || (roleSlug === 'murid') || isSelf || Boolean(u.can_edit);
            // Hak Akses Hapus: Admin bisa hapus selain diri sendiri; Guru bisa hapus murid selain diri sendiri
            const canDelete = !isSelf && (isCallerAdmin || (isCallerGuru && roleSlug === 'murid') || Boolean(u.can_delete));

            let actions = [];
            if (canEdit) {
                actions.push(`
                    <button type="button" class="btn-dash edit" onclick="DashboardApp.openEditUserModal(${uid})" title="Edit Nama, Username, Kelas, atau Password">
                        Edit
                    </button>
                `);
            }
            if (canDelete) {
                actions.push(`
                    <button type="button" class="btn-dash delete" onclick="DashboardApp.deleteUser(${uid})" title="Hapus Akun">
                        Hapus
                    </button>
                `);
            }

            let actionBtn = '';
            if (actions.length > 0) {
                actionBtn = `<div style="display:flex;gap:6px;justify-content:center">${actions.join('')}</div>`;
            } else if (isSelf) {
                actionBtn = '<span style="color:var(--muted);font-size:12px;font-weight:700">(Akun Anda)</span>';
            } else if (roleSlug === 'admin') {
                actionBtn = '<span class="status-badge" style="background:#f1f5f9;color:#64748b;font-size:11px" title="Guru tidak dapat mengubah Administrator">Admin</span>';
            } else if (roleSlug === 'guru') {
                actionBtn = '<span class="status-badge" style="background:#f1f5f9;color:#64748b;font-size:11px" title="Guru tidak dapat mengubah sesama Guru">Guru</span>';
            } else {
                actionBtn = '<span style="color:var(--muted);font-size:11px">—</span>';
            }

            return `
                <tr>
                    <td>${rowNumber}</td>
                    <td><strong>${this.escapeHTML(u.full_name || '')}</strong></td>
                    <td><code>${this.escapeHTML(u.username || '')}</code></td>
                    <td><span class="role-badge ${roleSlug}">${this.escapeHTML(roleSlug.toUpperCase())}</span></td>
                    <td><span class="kelas-badge">${this.escapeHTML(u.kelas || '—')}</span></td>
                    <td style="text-align:center">${actionBtn}</td>
                </tr>
            `;
        }).join('');

        // Render Tampilan 2: Kartu Pengguna Mobile (Responsive Cards)
        if (cardsContainer) {
            cardsContainer.innerHTML = pageItems.map((u) => {
                const uid = Number(u.id) || 0;
                const roleSlug = ['admin', 'guru', 'murid'].includes(u.role) ? u.role : 'murid';
                const initial = this.escapeHTML((u.full_name || 'U').trim().charAt(0).toUpperCase());
                const isSelf = Boolean(u.is_self || (callerId && uid === callerId));
                const canEdit = isCallerAdmin || (roleSlug === 'murid') || isSelf || Boolean(u.can_edit);
                const canDelete = !isSelf && (isCallerAdmin || (isCallerGuru && roleSlug === 'murid') || Boolean(u.can_delete));

                let cardActions = [];
                if (canEdit) {
                    cardActions.push(`
                        <button type="button" class="btn-dash edit" onclick="DashboardApp.openEditUserModal(${uid})">
                            Edit Akun
                        </button>
                    `);
                }
                if (canDelete) {
                    cardActions.push(`
                        <button type="button" class="btn-dash delete" onclick="DashboardApp.deleteUser(${uid})">
                            Hapus
                        </button>
                    `);
                }

                let cardActionBtn = '';
                if (cardActions.length > 0) {
                    cardActionBtn = cardActions.join('');
                } else if (isSelf) {
                    cardActionBtn = '<span style="color:var(--muted);font-size:12px;font-weight:700">Akun Anda</span>';
                } else {
                    cardActionBtn = `<span class="status-badge" style="background:#f1f5f9;color:#64748b;font-size:11px">${this.escapeHTML(roleSlug.toUpperCase())}</span>`;
                }

                return `
                    <div class="dash-user-card">
                        <div class="user-card-top">
                            <div class="user-card-avatar ${roleSlug}">${initial}</div>
                            <div class="user-card-body">
                                <div class="user-card-name-row">
                                    <strong class="user-name-text">${this.escapeHTML(u.full_name || '')}</strong>
                                    <span class="role-badge ${roleSlug}">${this.escapeHTML(roleSlug.toUpperCase())}</span>
                                </div>
                                <div class="user-card-info-row">
                                    <span class="user-uname-pill"><code>@${this.escapeHTML(u.username || '')}</code></span>
                                    ${u.kelas ? `<span class="kelas-badge">${this.escapeHTML(u.kelas)}</span>` : '<span class="kelas-badge-empty">—</span>'}
                                </div>
                            </div>
                        </div>
                        <div class="user-card-action">
                            ${cardActionBtn}
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Render Pagination Bar 100% Client-Side
        this.renderPaginationBar({
            containerId: 'dashStudentsPagination',
            currentPage: this.usersPage,
            totalPages,
            totalItems,
            startIndex,
            endIndex,
            itemLabel: 'pengguna',
            onPageChangeFnName: 'setUsersPage'
        });

        this.setUserView(this.userViewMode);
    },

    setUserView(mode) {
        this.userViewMode = mode;
        const grid = document.getElementById('dashStudentsCardsGrid');
        const tblWrap = document.getElementById('dashStudentsTableWrap');
        const btnCards = document.getElementById('btnViewCards');
        const btnTable = document.getElementById('btnViewTable');

        if (mode === 'cards') {
            if (grid) grid.style.display = 'grid';
            if (tblWrap) tblWrap.style.display = 'none';
            btnCards?.classList.add('active');
            btnTable?.classList.remove('active');
        } else {
            if (grid) grid.style.display = 'none';
            if (tblWrap) tblWrap.style.display = 'block';
            btnCards?.classList.remove('active');
            btnTable?.classList.add('active');
        }
    },

    toggleKelasField(role) {
        const group = document.getElementById('groupKelasInput');
        if (group) {
            group.style.display = (role === 'murid') ? 'block' : 'none';
        }
    },

    async createUser(event) {
        if (event) event.preventDefault();

        const fullName = document.getElementById('newStudentName')?.value.trim();
        const username = document.getElementById('newStudentUsername')?.value.trim();
        const password = document.getElementById('newStudentPassword')?.value.trim();
        const role = document.getElementById('newUserRole')?.value || 'murid';
        const kelas = document.getElementById('newStudentKelas')?.value.trim() || 'VIII A';
        const btn = document.getElementById('btnCreateStudent');

        if (!fullName || !username || !password) {
            window.showToast?.('Nama lengkap, username, dan password wajib diisi.');
            return;
        }

        if (btn) btn.disabled = true;

        try {
            const res = await this.apiPost('api/users.php?action=create_user', {
                full_name: fullName,
                username,
                password,
                role,
                kelas: (role === 'murid') ? kelas : null
            });
            const data = await res.json();

            if (data.success) {
                window.showToast?.(data.message || 'Akun berhasil dibuat.');
                document.getElementById('addStudentForm')?.reset();
                this.loadStudentsManagement();
                this.loadStats();
            } else {
                window.showToast?.(data.message || 'Gagal membuat akun.');
            }
        } catch (err) {
            window.showToast?.('Gagal membuat akun: ' + err.message);
        } finally {
            if (btn) btn.disabled = false;
        }
    },

    async deleteUser(id, name = null, role = null) {
        const userObj = this.usersList.find(u => Number(u.id) === Number(id));
        const userName = name !== null ? name : (userObj?.full_name || '');
        const userRole = (role !== null ? role : (userObj?.role || 'murid')).toUpperCase();
        if (!confirm(`Apakah Anda yakin ingin menghapus akun ${userRole} "${userName}" dari database?`)) return;

        try {
            const res = await this.apiPost('api/users.php?action=delete_user', { id });
            const data = await res.json();
            if (data.success) {
                window.showToast?.(data.message || 'Akun berhasil dihapus.');
                this.loadStudentsManagement();
                this.loadStats();
            } else {
                window.showToast?.(data.message || 'Gagal menghapus pengguna.');
            }
        } catch (e) {
            window.showToast?.('Gagal menghapus pengguna.');
        }
    },

    openCreateUserModal() {
        const modal = document.getElementById('createUserModal');
        const form = document.getElementById('modalAddUserForm');
        if (form) form.reset();
        this.toggleModalKelasField('murid');
        if (modal) modal.style.display = 'flex';
        document.getElementById('modalNewStudentName')?.focus();
    },

    closeCreateUserModal() {
        const modal = document.getElementById('createUserModal');
        if (modal) modal.style.display = 'none';
    },

    toggleModalKelasField(role) {
        const group = document.getElementById('modalGroupKelasInput');
        if (group) {
            group.style.display = (role === 'murid') ? 'block' : 'none';
        }
    },

    async createUserFromModal(event) {
        if (event) event.preventDefault();

        const fullName = document.getElementById('modalNewStudentName')?.value.trim();
        const username = document.getElementById('modalNewStudentUsername')?.value.trim();
        const password = document.getElementById('modalNewStudentPassword')?.value.trim();
        const role = document.getElementById('modalNewUserRole')?.value || 'murid';
        const kelas = document.getElementById('modalNewStudentKelas')?.value.trim() || 'VIII A';
        const btn = document.getElementById('btnModalCreateUser');

        if (!fullName || !username || !password) {
            window.showToast?.('Nama lengkap, username, dan password wajib diisi.');
            return;
        }

        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Menyimpan…';
        }

        try {
            const res = await this.apiPost('api/users.php?action=create_user', {
                full_name: fullName,
                username,
                password,
                role,
                kelas: (role === 'murid') ? kelas : null
            });
            const data = await res.json();

            if (data.success) {
                window.showToast?.(data.message || 'Akun berhasil dibuat.');
                this.closeCreateUserModal();
                this.loadStudentsManagement();
                this.loadStats();
            } else {
                window.showToast?.(data.message || 'Gagal membuat akun.');
            }
        } catch (err) {
            window.showToast?.('Gagal membuat akun: ' + err.message);
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Simpan Akun';
            }
        }
    },

    openEditUserModal(userId) {
        const user = this.usersList.find(u => Number(u.id) === Number(userId));
        if (!user) {
            window.showToast?.('Data akun tidak ditemukan.');
            return;
        }

        const modal = document.getElementById('editUserModal');
        if (!modal) return;

        document.getElementById('editUserId').value = user.id;
        document.getElementById('editFullName').value = user.full_name || '';
        document.getElementById('editUsername').value = user.username || '';
        document.getElementById('editPassword').value = '';

        const roleSelect = document.getElementById('editUserRole');
        const roleHint = document.getElementById('editRoleHint');

        const isCallerAdmin = (this.currentUser?.role === 'admin');

        if (roleSelect) {
            roleSelect.value = user.role;
            // Jika caller adalah guru, kunci role murid saja
            roleSelect.disabled = !isCallerAdmin;
            if (roleHint) {
                roleHint.style.display = isCallerAdmin ? 'none' : 'block';
            }
        }

        const kelasSelect = document.getElementById('editUserKelas');
        if (kelasSelect && user.kelas) {
            kelasSelect.value = user.kelas;
        }

        this.toggleEditKelasField(user.role);

        modal.style.display = 'flex';
        document.getElementById('editFullName')?.focus();
    },

    closeEditUserModal() {
        const modal = document.getElementById('editUserModal');
        if (modal) modal.style.display = 'none';
    },

    toggleEditKelasField(role) {
        const group = document.getElementById('editGroupKelas');
        if (group) {
            group.style.display = (role === 'murid') ? 'block' : 'none';
        }
    },

    async submitEditUser(event) {
        if (event) event.preventDefault();

        const id = parseInt(document.getElementById('editUserId')?.value || '0', 10);
        const fullName = document.getElementById('editFullName')?.value.trim();
        const username = document.getElementById('editUsername')?.value.trim();
        const password = document.getElementById('editPassword')?.value.trim();
        const roleSelect = document.getElementById('editUserRole');
        const role = roleSelect ? roleSelect.value : 'murid';
        const kelas = document.getElementById('editUserKelas')?.value.trim() || 'VIII A';
        const btn = document.getElementById('btnSubmitEditUser');

        if (!id || !fullName || !username) {
            window.showToast?.('Nama lengkap dan username wajib diisi.');
            return;
        }

        if (password && password.length < 4) {
            window.showToast?.('Password baru minimal 4 karakter.');
            return;
        }

        const originalBtnText = btn ? btn.textContent : '';
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Menyimpan…';
        }

        try {
            const res = await this.apiPost('api/users.php?action=update_user', {
                id,
                full_name: fullName,
                username,
                password: password || null,
                role,
                kelas: (role === 'murid') ? kelas : null
            });
            const data = await res.json();

            if (data.success) {
                window.showToast?.(data.message || 'Perubahan akun berhasil disimpan.');
                this.closeEditUserModal();
                this.loadStudentsManagement();
                this.loadStats();
            } else {
                window.showToast?.(data.message || 'Gagal menyimpan perubahan.');
            }
        } catch (err) {
            window.showToast?.('Gagal menyimpan: ' + err.message);
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.textContent = originalBtnText;
            }
        }
    },

    // ==========================================
    // 4. PENGATURAN DEKORASI BERANDA (ADMIN)
    // ==========================================
    toggleCustomArtInput(mode) {
        const group = document.getElementById('decorCustomArtGroup');
        if (group) {
            group.style.display = (mode === 'custom') ? 'block' : 'none';
        }
    },

    async saveHomeDecorations(e) {
        if (e) e.preventDefault();

        const kicker = document.getElementById('decorKicker')?.value.trim();
        const badge = document.getElementById('decorBadge')?.value.trim();
        const title = document.getElementById('decorTitle')?.value.trim();
        const highlight = document.getElementById('decorHighlight')?.value.trim();
        const description = document.getElementById('decorDescription')?.value.trim();
        const theme = document.getElementById('decorTheme')?.value || 'blue';
        const artMode = document.getElementById('decorArtMode')?.value || 'svg_default';
        const customArt = document.getElementById('decorCustomArt')?.value.trim();
        const btn = document.getElementById('btnSaveDecorations');

        if (!title || !kicker) {
            window.showToast?.('Judul dan Kicker wajib diisi.');
            return;
        }

        if (btn) btn.disabled = true;

        try {
            const res = await this.apiPost('api/settings.php?action=update_home', {
                home_kicker: kicker,
                home_badge: badge,
                home_title: title,
                home_highlight: highlight,
                home_description: description,
                home_theme: theme,
                home_art_mode: artMode,
                home_custom_art: customArt
            });
            const data = await res.json();

            if (data.success) {
                window.showToast?.('Hiasan dan dekorasi beranda berhasil diperbarui.');
            } else {
                window.showToast?.(data.message || 'Gagal menyimpan dekorasi.');
            }
        } catch (err) {
            window.showToast?.('Gagal menyimpan dekorasi: ' + err.message);
        } finally {
            if (btn) btn.disabled = false;
        }
    },

    // ==========================================
    // 5. MANAJEMEN SOAL KUIS (CRUD)
    // ==========================================
    async loadQuizManagement() {
        const container = document.getElementById('dashQuizQuestionsList');
        if (!container) return;

        container.innerHTML = '<div style="text-align:center;padding:24px;color:var(--muted)">Memuat bank soal kuis…</div>';

        try {
            const res = await fetch('api/quiz.php?action=admin_list');
            const data = await res.json();
            if (!data.success) throw new Error(data.message);

            this.quizQuestions = data.questions || [];
            this.quizPage = 1;
            this.renderQuizPage();
        } catch (err) {
            container.innerHTML = `<div style="padding:16px;color:#ef4444;text-align:center">Gagal memuat soal kuis: ${this.escapeHTML(err.message)}</div>`;
        }
    },

    setQuizPage(page) {
        this.quizPage = page;
        this.renderQuizPage();
        const el = document.getElementById('panel-kuis');
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },

    renderQuizPage() {
        const container = document.getElementById('dashQuizQuestionsList');
        if (!container) return;

        const totalItems = this.quizQuestions.length;
        if (!totalItems) {
            container.innerHTML = `
                <div style="text-align:center;padding:32px;background:#f8fafc;border:1px dashed var(--line);border-radius:var(--radius-md)">
                    <strong>Belum ada soal kuis yang dibuat.</strong>
                    <p style="color:var(--muted);font-size:13px;margin:6px 0 14px">Klik tombol "Tambah Soal Kuis Baru" untuk membuat soal interaktif pertama.</p>
                    <button class="btn small" onclick="DashboardApp.openAddQuizQuestionModal()">Buat Soal Sekarang</button>
                </div>
            `;
            this.renderPaginationBar({
                containerId: 'dashQuizPagination',
                currentPage: 1,
                totalPages: 0,
                totalItems: 0,
                startIndex: 0,
                endIndex: 0,
                itemLabel: 'soal kuis',
                onPageChangeFnName: 'setQuizPage'
            });
            return;
        }

        const pageSize = this.quizPageSize;
        const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
        if (this.quizPage > totalPages) this.quizPage = totalPages;
        if (this.quizPage < 1) this.quizPage = 1;

        const startIndex = (this.quizPage - 1) * pageSize;
        const endIndex = Math.min(startIndex + pageSize, totalItems);
        const pageItems = this.quizQuestions.slice(startIndex, endIndex);

        container.innerHTML = pageItems.map((q, idx) => {
            const qid = Number(q.id) || 0;
            const optA = this.escapeHTML(q.option_a || '');
            const optB = this.escapeHTML(q.option_b || '');
            const optC = this.escapeHTML(q.option_c || '');
            const optD = this.escapeHTML(q.option_d || '');
            const correct = parseInt(q.correct_index, 10);
            const ordNumber = startIndex + idx + 1;

            return `
                <div class="dash-quiz-card">
                    <div class="dash-quiz-card-header">
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                            <span class="quiz-ord-badge">#${Number(q.order_num) || ordNumber}</span>
                            <span class="quiz-timer-pill">${Number(q.time_limit) || 20} dtk</span>
                            <span class="quiz-points-pill">${Number(q.points) || 1000} Poin</span>
                        </div>
                        <div class="dash-quiz-actions">
                            <button class="btn-dash edit" onclick="DashboardApp.openEditQuizQuestionModal(${qid})" title="Edit Soal & Jawaban">Edit</button>
                            <button class="btn-dash delete" onclick="DashboardApp.deleteQuizQuestion(${qid})" title="Hapus Soal">Hapus</button>
                        </div>
                    </div>

                    <div class="dash-quiz-question-text">
                        ${this.escapeHTML(q.question || '')}
                    </div>

                    <div class="dash-quiz-options-grid">
                        <div class="quiz-opt-tag opt-red ${correct === 0 ? 'correct' : ''}">
                            <span>A. ${optA}</span>
                            ${correct === 0 ? '<strong class="correct-badge">Kunci Benar</strong>' : ''}
                        </div>
                        <div class="quiz-opt-tag opt-blue ${correct === 1 ? 'correct' : ''}">
                            <span>B. ${optB}</span>
                            ${correct === 1 ? '<strong class="correct-badge">Kunci Benar</strong>' : ''}
                        </div>
                        <div class="quiz-opt-tag opt-yellow ${correct === 2 ? 'correct' : ''}">
                            <span>C. ${optC}</span>
                            ${correct === 2 ? '<strong class="correct-badge">Kunci Benar</strong>' : ''}
                        </div>
                        <div class="quiz-opt-tag opt-green ${correct === 3 ? 'correct' : ''}">
                            <span>D. ${optD}</span>
                            ${correct === 3 ? '<strong class="correct-badge">Kunci Benar</strong>' : ''}
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        this.renderPaginationBar({
            containerId: 'dashQuizPagination',
            currentPage: this.quizPage,
            totalPages,
            totalItems,
            startIndex,
            endIndex,
            itemLabel: 'soal kuis',
            onPageChangeFnName: 'setQuizPage'
        });
    },

    openAddQuizQuestionModal() {
        const modal = document.getElementById('quizQuestionModal');
        const form = document.getElementById('quizQuestionForm');
        const title = document.getElementById('quizQuestionModalTitle');
        const hiddenId = document.getElementById('editQuizQuestionId');
        const delBtn = document.getElementById('btnDeleteQuizQuestion');

        if (form) form.reset();
        if (hiddenId) hiddenId.value = '';
        if (title) title.textContent = 'Tambah Soal Kuis Baru';
        if (delBtn) delBtn.style.display = 'none';

        const btn = document.getElementById('btnSubmitQuizQuestion');
        if (btn) btn.textContent = 'Simpan Soal Kuis';

        if (modal) modal.style.display = 'flex';
        document.getElementById('qqQuestion')?.focus();
    },

    async openEditQuizQuestionModal(questionId) {
        try {
            const qid = Number(questionId) || 0;
            const res = await fetch(`api/quiz.php?action=get_question&id=${qid}`);
            const data = await res.json();
            if (!data.success) throw new Error(data.message);

            const q = data.question;
            const modal = document.getElementById('quizQuestionModal');
            const title = document.getElementById('quizQuestionModalTitle');
            const hiddenId = document.getElementById('editQuizQuestionId');
            const delBtn = document.getElementById('btnDeleteQuizQuestion');

            if (title) title.textContent = `Edit Soal Kuis #${q.order_num || ''}`;
            if (hiddenId) hiddenId.value = q.id;
            if (delBtn) delBtn.style.display = 'inline-block';

            document.getElementById('qqQuestion').value = q.question || '';
            document.getElementById('qqOptionA').value = q.option_a || '';
            document.getElementById('qqOptionB').value = q.option_b || '';
            document.getElementById('qqOptionC').value = q.option_c || '';
            document.getElementById('qqOptionD').value = q.option_d || '';
            document.getElementById('qqCorrectIndex').value = String(q.correct_index ?? 0);
            document.getElementById('qqTimeLimit').value = String(q.time_limit || 20);
            document.getElementById('qqPoints').value = String(q.points || 1000);

            const btn = document.getElementById('btnSubmitQuizQuestion');
            if (btn) btn.textContent = 'Simpan Perubahan Soal';

            if (modal) modal.style.display = 'flex';
            document.getElementById('qqQuestion')?.focus();
        } catch (err) {
            window.showToast?.('Gagal memuat detail soal: ' + err.message);
        }
    },

    closeQuizQuestionModal() {
        const modal = document.getElementById('quizQuestionModal');
        if (modal) modal.style.display = 'none';
    },

    async submitQuizQuestion(event) {
        if (event) event.preventDefault();
        const hiddenId = document.getElementById('editQuizQuestionId')?.value.trim();
        const isEdit = !!hiddenId;

        const question = document.getElementById('qqQuestion')?.value.trim();
        const optionA = document.getElementById('qqOptionA')?.value.trim();
        const optionB = document.getElementById('qqOptionB')?.value.trim();
        const optionC = document.getElementById('qqOptionC')?.value.trim();
        const optionD = document.getElementById('qqOptionD')?.value.trim();
        const correctIndex = parseInt(document.getElementById('qqCorrectIndex')?.value, 10);
        const timeLimit = parseInt(document.getElementById('qqTimeLimit')?.value, 10) || 20;
        const points = parseInt(document.getElementById('qqPoints')?.value, 10) || 1000;

        if (!question || !optionA || !optionB || !optionC || !optionD) {
            window.showToast?.('Teks pertanyaan dan semua 4 opsi jawaban wajib diisi.');
            return;
        }

        const btn = document.getElementById('btnSubmitQuizQuestion');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Menyimpan…';
        }

        try {
            const url = isEdit ? 'api/quiz.php?action=update_question' : 'api/quiz.php?action=add_question';
            const payload = {
                id: isEdit ? parseInt(hiddenId, 10) : undefined,
                question,
                option_a: optionA,
                option_b: optionB,
                option_c: optionC,
                option_d: optionD,
                correct_index: correctIndex,
                time_limit: timeLimit,
                points
            };

            const res = await this.apiPost(url, payload);
            const data = await res.json();
            if (!data.success) throw new Error(data.message);

            window.showToast?.(data.message || 'Soal kuis berhasil disimpan.');
            this.closeQuizQuestionModal();
            this.loadQuizManagement();
        } catch (err) {
            window.showToast?.('Gagal menyimpan soal: ' + err.message);
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.textContent = isEdit ? 'Simpan Perubahan Soal' : 'Simpan Soal Kuis';
            }
        }
    },

    async deleteQuizQuestion(questionId) {
        if (!confirm('Apakah Anda yakin ingin menghapus soal kuis ini dari bank soal?')) return;

        try {
            const res = await this.apiPost('api/quiz.php?action=delete_question', { id: questionId });
            const data = await res.json();
            if (!data.success) throw new Error(data.message);

            window.showToast?.('Soal kuis berhasil dihapus.');
            this.loadQuizManagement();
        } catch (err) {
            window.showToast?.('Gagal menghapus soal: ' + err.message);
        }
    },

    deleteQuizQuestionFromModal() {
        const hiddenId = document.getElementById('editQuizQuestionId')?.value.trim();
        if (!hiddenId) return;
        this.closeQuizQuestionModal();
        this.deleteQuizQuestion(parseInt(hiddenId, 10));
    },

    // ==========================================
    // 6. STATISTIK
    // ==========================================
    async loadStats() {
        try {
            const res = await fetch('api/auth.php?action=status');
            const data = await res.json();
        } catch (e) {}
    },

    // ==========================================
    // 7. LOG AKTIVITAS SISTEM (100% CLIENT-SIDE PAGINATION)
    // ==========================================
    async loadActivityLogs() {
        const tableBody = document.getElementById('dashActivityLogsTable');
        if (!tableBody) return;
        tableBody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--muted)">Memuat catatan aktivitas…</td></tr>';

        try {
            // Muat hingga 250 log sekaligus untuk paginasi 100% client-side
            const res = await fetch('api/activity.php?action=list&page=1&per_page=250');
            const data = await res.json();
            if (!data.success) throw new Error(data.message);

            this.allActivityLogs = data.logs || [];
            this.activityLogsPage = 1;
            this.renderActivityLogsPage();
        } catch (err) {
            tableBody.innerHTML = `<tr><td colspan="4" style="text-align:center;color:#ef4444">Gagal memuat log: ${this.escapeHTML(err.message)}</td></tr>`;
        }
    },

    setActivityLogsPage(page) {
        this.activityLogsPage = page;
        this.renderActivityLogsPage();
        const panel = document.getElementById('panel-log');
        if (panel) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },

    renderActivityLogsPage() {
        const tableBody = document.getElementById('dashActivityLogsTable');
        if (!tableBody) return;

        const totalItems = this.allActivityLogs.length;
        if (!totalItems) {
            tableBody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:24px;color:var(--muted)">Belum ada aktivitas yang tercatat.</td></tr>';
            this.renderPaginationBar({
                containerId: 'dashActivityPagination',
                currentPage: 1,
                totalPages: 0,
                totalItems: 0,
                startIndex: 0,
                endIndex: 0,
                itemLabel: 'catatan aktivitas',
                onPageChangeFnName: 'setActivityLogsPage'
            });
            return;
        }

        const pageSize = this.activityLogsPageSize;
        const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
        if (this.activityLogsPage > totalPages) this.activityLogsPage = totalPages;
        if (this.activityLogsPage < 1) this.activityLogsPage = 1;

        const startIndex = (this.activityLogsPage - 1) * pageSize;
        const endIndex = Math.min(startIndex + pageSize, totalItems);
        const pageItems = this.allActivityLogs.slice(startIndex, endIndex);

        tableBody.innerHTML = pageItems.map(l => {
            let roleBadge = '<span class="status-badge" style="background:#f1f5f9;color:#475569">Pengunjung</span>';
            if (l.role === 'admin') {
                roleBadge = '<span class="status-badge" style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca">Admin</span>';
            } else if (l.role === 'guru') {
                roleBadge = '<span class="status-badge" style="background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe">Guru</span>';
            } else if (l.role === 'murid') {
                roleBadge = '<span class="status-badge" style="background:#f0fdf4;color:#166534;border:1px solid #bbf7d0">Siswa</span>';
            }

            return `
                <tr>
                    <td><small style="color:var(--muted);white-space:nowrap">${this.escapeHTML(this.formatDate(l.created_at))}</small></td>
                    <td><strong>${this.escapeHTML(l.user_name || 'Anonim')}</strong></td>
                    <td>${roleBadge}</td>
                    <td style="color:var(--navy);font-weight:600">${this.escapeHTML(l.action || '')}</td>
                </tr>
            `;
        }).join('');

        this.renderPaginationBar({
            containerId: 'dashActivityPagination',
            currentPage: this.activityLogsPage,
            totalPages,
            totalItems,
            startIndex,
            endIndex,
            itemLabel: 'catatan aktivitas',
            onPageChangeFnName: 'setActivityLogsPage'
        });
    },

    prevLogPage() {
        if (this.activityLogsPage > 1) {
            this.setActivityLogsPage(this.activityLogsPage - 1);
        }
    },

    nextLogPage() {
        const totalPages = Math.ceil(this.allActivityLogs.length / this.activityLogsPageSize);
        if (this.activityLogsPage < totalPages) {
            this.setActivityLogsPage(this.activityLogsPage + 1);
        }
    },

    formatDate(str) {
        if (!str) return '—';
        try {
            // Normalisasi format MySQL YYYY-MM-DD HH:MM:SS sebagai basis WIB (GMT+7) dari server
            let isoStr = str;
            if (typeof str === 'string' && /^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/.test(str)) {
                isoStr = str.replace(' ', 'T') + '+07:00';
            }
            const d = new Date(isoStr);
            if (isNaN(d.getTime())) return str;

            // Format tanggal dan jam sesuai zona waktu perangkat pengakses
            const datePart = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
            const timePart = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false });

            // Deteksi singkatan zona waktu lokal pengakses (WIB / WITA / WIT / GMT+X)
            let tzLabel = ' WIB';
            try {
                const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
                if (tz === 'Asia/Jakarta' || tz === 'Asia/Pontianak') tzLabel = ' WIB';
                else if (tz === 'Asia/Makassar' || tz === 'Asia/Ujung_Pandang') tzLabel = ' WITA';
                else if (tz === 'Asia/Jayapura') tzLabel = ' WIT';
                else {
                    const offset = -d.getTimezoneOffset() / 60;
                    tzLabel = offset === 7 ? ' WIB' : ` (GMT${offset >= 0 ? '+' : ''}${offset})`;
                }
            } catch (e) {
                tzLabel = ' WIB';
            }

            return `${datePart}, ${timePart}${tzLabel}`;
        } catch (e) {
            return str;
        }
    },

    async toggleDemoMode(enabled) {
        const toggleEl = document.getElementById('toggleDemoMode');
        const badgeEl = document.getElementById('demoModeBadge');
        const labelEl = document.getElementById('toggleDemoModeLabel');

        try {
            const res = await this.apiPost('api/settings.php?action=toggle_demo_mode', { enabled });
            const data = await res.json();
            if (data.success) {
                window.showToast?.(data.message);
                if (badgeEl) {
                    badgeEl.className = `role-badge ${enabled ? 'murid' : 'guru'}`;
                    badgeEl.textContent = enabled ? 'Akun Bawaan Aktif' : 'Khusus Akun Terdaftar';
                }
                if (labelEl) {
                    labelEl.textContent = enabled ? 'AKTIF' : 'NONAKTIF';
                    labelEl.style.color = enabled ? '#10b981' : '#ef4444';
                }
                // Muat ulang halaman dalam 1 detik untuk menyinkronkan seluruh tampilan navigasi & akses
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                window.showToast?.('Gagal mengubah pengaturan: ' + (data.message || 'Akses ditolak'));
                if (toggleEl) toggleEl.checked = !enabled;
            }
        } catch (err) {
            window.showToast?.('Gagal terhubung ke server.');
            if (toggleEl) toggleEl.checked = !enabled;
        }
    },

    escapeHTML(str) {
        return String(str).replace(/[&<>"']/g, m => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[m]));
    }
};

window.DashboardApp = DashboardApp;
