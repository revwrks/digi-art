/**
 * Pameran Karya Frontend Module
 * - Tampilan Enclosed Card Seragam (identik dengan Referensi)
 * - Modal Pratinjau Imersif Hampir Memenuhi Layar (Orientation-Adaptive)
 * - Mode paksa perbesar/isi ruang meski resolusi kecil
 * - Kompresi gambar client-side sebelum upload
 */

const PameranApp = {
    works: [],
    compressedFilePayload: null,

    async init() {
        await this.loadWorks();
    },

    async loadWorks() {
        const grid = document.getElementById('worksGrid');
        if (!grid) return;

        grid.innerHTML = `
            <div class="kahoot-loader" style="grid-column:1/-1;text-align:center;padding:50px 20px">
                <div class="kahoot-spinner" style="margin:0 auto 16px"></div>
                <p>Memuat karya pameran siswa…</p>
            </div>
        `;

        try {
            const res = await App.apiFetch('api/pameran.php?action=get_approved');
            const data = await res.json();
            this.works = data.works || [];

            if (!this.works.length) {
                grid.innerHTML = `
                    <div class="card" style="grid-column:1/-1;padding:50px 20px;text-align:center">
                        <div style="display:inline-flex;align-items:center;justify-content:center;width:56px;height:56px;border-radius:14px;background:var(--primary-light);color:var(--primary);margin-bottom:12px" aria-hidden="true">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        </div>
                        <h3>Belum ada karya yang dipamerkan</h3>
                        <p>Jadilah siswa pertama yang mengirim karya ilustrasi ke pameran kelas!</p>
                        <button class="btn small" style="margin-top:14px" onclick="PameranApp.openUploadModal()">Unggah Karya Sekarang</button>
                    </div>
                `;
                return;
            }

            // TAMPILAN ENCLOSED CARD (Seragam & Terisolasi, tidak ada tabrakan)
            grid.innerHTML = this.works.map((w, idx) => {
                const safeImg = App.safeUrl(w.image_path, 'assets/img/sample-volcano-art.png');
                const safeId = parseInt(w.id, 10) || 0;
                const likesCount = parseInt(w.likes_count, 10) || 0;
                return `
                    <article class="card-enclosed">
                        <div class="card-enclosed-thumb" onclick="PameranApp.previewImmersive(${idx})">
                            <span class="card-enclosed-tag">${this.escapeHTML(w.creator_name)} (${this.escapeHTML(w.kelas || 'VIII')})</span>
                            <img src="${this.escapeHTML(safeImg)}" 
                                 alt="${this.escapeHTML(w.title)}" 
                                 loading="lazy"
                                 onerror="this.src='assets/img/sample-volcano-art.png'">
                            <div class="card-enclosed-overlay">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="M11 8v6m-3-3h6"/></svg>
                                <span>Buka Layar Penuh</span>
                            </div>
                        </div>
                        <div class="card-enclosed-body">
                            <h3 class="card-enclosed-title" title="${this.escapeHTML(w.title)}">
                                ${this.escapeHTML(w.title)}
                            </h3>
                            <p class="card-enclosed-desc">
                                ${this.escapeHTML(w.description || 'Karya ilustrasi digital siswa Digi-Art.')}
                            </p>
                            <div class="card-enclosed-footer">
                                <button class="pameran-like-btn" onclick="PameranApp.likeWork(${safeId}, this)" aria-label="Sukai karya">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" stroke="none" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                                    <span class="like-count">${likesCount}</span>
                                </button>
                                <button class="btn small" onclick="PameranApp.previewImmersive(${idx})" aria-label="Lihat Penuh">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                                    Detail Karya
                                </button>
                            </div>
                        </div>
                    </article>
                `;
            }).join('');

        } catch (err) {
            grid.innerHTML = `
                <div class="card" style="grid-column:1/-1;padding:30px;text-align:center">
                    <p>Gagal memuat galeri pameran: ${this.escapeHTML(err.message || 'Kesalahan jaringan.')}</p>
                </div>
            `;
        }
    },

    openUploadModal() {
        if (!window.currentUser) {
            App.showToast('Masuk dengan akun siswa atau guru terlebih dahulu untuk mengirim karya.');
            App.openLoginModal();
            return;
        }
        const modal = document.getElementById('uploadModal');
        if (modal) {
            modal.style.display = 'flex';
            const nameInput = document.getElementById('workCreatorName');
            const classInput = document.getElementById('workKelas');
            if (nameInput && !nameInput.value) nameInput.value = window.currentUser.full_name;
            if (classInput && !classInput.value) classInput.value = window.currentUser.kelas || 'VIII A';
        }
    },

    closeUploadModal() {
        const modal = document.getElementById('uploadModal');
        if (modal) modal.style.display = 'none';
        const compressInfo = document.getElementById('compressInfo');
        if (compressInfo) compressInfo.style.display = 'none';
    },

    async handleFileSelect(e) {
        const file = e.target.files[0];
        if (!file) return;

        const infoBox = document.getElementById('compressInfo');
        if (infoBox) {
            infoBox.style.display = 'block';
            infoBox.textContent = 'Mengompres gambar di browser Anda…';
        }

        try {
            const compressed = await ImageCompressor.compress(file, {
                maxWidth: 1600,
                maxHeight: 1200,
                quality: 0.8
            });

            this.compressedFilePayload = compressed.dataUrl;

            if (infoBox) {
                infoBox.innerHTML = `
                    <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;padding:8px 12px;border-radius:6px;font-size:12px">
                        <strong>Kompresi Berhasil:</strong> 
                        ${this.escapeHTML(compressed.originalFormatted)} ke <strong>${this.escapeHTML(compressed.compressedFormatted)}</strong> 
                        <span>(Hemat ${this.escapeHTML(compressed.savedPercent)}%)</span>
                    </div>
                `;
            }
        } catch (err) {
            if (infoBox) {
                infoBox.innerHTML = `<span style="color:#ef4444">${this.escapeHTML(err.message || 'Gagal memproses gambar.')}</span>`;
            }
        }
    },

    async submitWork(event) {
        if (event) event.preventDefault();

        const title = document.getElementById('workTitle')?.value.trim();
        const creatorName = document.getElementById('workCreatorName')?.value.trim();
        const kelas = document.getElementById('workKelas')?.value.trim() || 'VIII';
        const description = document.getElementById('workDesc')?.value.trim();
        const btn = document.getElementById('btnSubmitWork');

        if (!title || !creatorName) {
            window.showToast?.('Judul karya dan nama pembuat wajib diisi.');
            return;
        }

        if (!this.compressedFilePayload) {
            window.showToast?.('Pilih file gambar karya terlebih dahulu.');
            return;
        }

        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Mengunggah Karya…';
        }

        try {
            // 1. Upload file gambar yang sudah dikompresi
            const uploadRes = await App.apiFetch('api/upload.php', {
                method: 'POST',
                body: JSON.stringify({ image_base64: this.compressedFilePayload })
            });
            const uploadData = await uploadRes.json();
            if (!uploadData.success) throw new Error(uploadData.message || 'Gagal mengunggah gambar.');

            // 2. Submit data karya ke database (status otomatis 'pending')
            const submitRes = await App.apiFetch('api/pameran.php?action=submit', {
                method: 'POST',
                body: JSON.stringify({
                    title,
                    creator_name: creatorName,
                    kelas,
                    description,
                    image_path: uploadData.file_path
                })
            });
            const submitData = await submitRes.json();
            if (!submitData.success) throw new Error(submitData.message || 'Gagal menyimpan karya.');

            this.closeUploadModal();
            this.compressedFilePayload = null;
            document.getElementById('workForm')?.reset();

            // Tampilkan dialog informasi approval guru
            this.showApprovalNotice();
        } catch (err) {
            window.showToast?.('Gagal mengunggah karya: ' + (err.message || 'Kesalahan server.'));
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Kirim Karya';
            }
        }
    },

    showApprovalNotice() {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.style.display = 'flex';
        overlay.innerHTML = `
            <div class="modal" style="text-align:center;max-width:440px">
                <div style="display:inline-flex;align-items:center;justify-content:center;width:56px;height:56px;border-radius:14px;background:#ecfdf5;color:#059669;margin-bottom:14px" aria-hidden="true">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <h2>Karya Berhasil Terkirim!</h2>
                <p style="color:var(--muted);font-size:14px;line-height:1.6">
                    Karyamu telah masuk ke antrean verifikasi. Karya akan dipublikasikan di halaman Pameran setelah 
                    <strong>disetujui oleh Guru</strong> melalui Dashboard Guru.
                </p>
                <button class="btn" style="margin-top:16px;width:100%" onclick="this.closest('.modal-overlay').remove()">
                    Mengerti &amp; Kembali ke Galeri
                </button>
            </div>
        `;
        document.body.appendChild(overlay);
    },

    async likeWork(id, btn) {
        if (!btn || btn.dataset.liking === '1') return;
        btn.dataset.liking = '1';
        const countEl = btn.querySelector('.like-count');
        const prevCount = parseInt(countEl?.textContent, 10) || 0;

        try {
            const res = await App.apiFetch('api/pameran.php?action=like', {
                method: 'POST',
                body: JSON.stringify({ id })
            });
            const data = await res.json();

            if (data.success) {
                const updatedCount = typeof data.likes !== 'undefined' ? (parseInt(data.likes, 10) || 0) : prevCount;
                if (countEl) countEl.textContent = updatedCount;
                btn.classList.add('liked');

                const work = this.works.find(w => Number(w.id) === Number(id));
                if (work) work.likes_count = updatedCount;

                if (data.already_liked) {
                    App.showToast('Kamu sudah menyukai karya ini.');
                }
            } else {
                if (countEl) countEl.textContent = prevCount;
                if (data.message) App.showToast(data.message);
            }
        } catch (e) {
            if (countEl) countEl.textContent = prevCount;
        } finally {
            delete btn.dataset.liking;
        }
    },

    closeImmersivePreview() {
        const overlay = document.getElementById('pameranImmersiveOverlay');
        if (overlay) overlay.remove();
        if (this._immersiveEscHandler) {
            window.removeEventListener('keydown', this._immersiveEscHandler);
            this._immersiveEscHandler = null;
        }
    },

    /**
     * Modal Imersif Layar Penuh (Orientation Adaptive + Paksa Penuh)
     */
    previewImmersive(index) {
        const item = this.works[index];
        if (!item) return;

        this.closeImmersivePreview();

        const safeImg = App.safeUrl(item.image_path, 'assets/img/sample-volcano-art.png');
        const safeId = parseInt(item.id, 10) || 0;
        const likesCount = parseInt(item.likes_count, 10) || 0;

        const overlay = document.createElement('div');
        overlay.className = 'immersive-preview-overlay';
        overlay.id = 'pameranImmersiveOverlay';
        overlay.innerHTML = `
            <div class="immersive-preview-modal" role="dialog" aria-modal="true" aria-label="${this.escapeHTML(item.title)}">
                <!-- Header -->
                <div class="immersive-header">
                    <h3 style="display:flex;align-items:center;gap:8px">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <span>${this.escapeHTML(item.title)}</span>
                    </h3>
                    <button class="btn-close" style="position:static" onclick="PameranApp.closeImmersivePreview()" aria-label="Tutup Pratinjau">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Content Grid (Orientation Adaptive) -->
                <div class="immersive-content-grid">
                    <!-- Image Stage -->
                    <div class="immersive-image-stage" id="pameranStage">
                        <img src="${this.escapeHTML(safeImg)}" alt="${this.escapeHTML(item.title)}" id="pameranStageImg" onerror="this.src='assets/img/sample-volcano-art.png'">
                        
                        <!-- Floating Toolbar: Paksa Penuh / Fit Switcher -->
                        <div class="immersive-stage-toolbar">
                            <button class="btn-stage-ctrl active" id="btnPamContain" onclick="PameranApp.setStageMode('contain')">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                                Skala Proporsional
                            </button>
                            <button class="btn-stage-ctrl" id="btnPamStretch" onclick="PameranApp.setStageMode('fill')">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
                                Paksa Penuh (Fill/Stretch)
                            </button>
                        </div>
                    </div>

                    <!-- Sidebar Metadata -->
                    <div class="immersive-sidebar-panel">
                        <div>
                            <h4>${this.escapeHTML(item.title)}</h4>
                            <p style="font-size:14px;color:#cbd5e1;margin-top:8px;line-height:1.6">
                                ${this.escapeHTML(item.description || 'Karya gambar ilustrasi siswa Digi-Art.')}
                            </p>
                        </div>

                        <div class="immersive-meta-box">
                            <div><strong>Pembuat Karya:</strong> ${this.escapeHTML(item.creator_name)}</div>
                            <div style="margin-top:4px"><strong>Kelas:</strong> ${this.escapeHTML(item.kelas || 'VIII')}</div>
                            <div style="margin-top:4px"><strong>Tanggal Terbit:</strong> ${this.escapeHTML(this.formatDate(item.created_at))}</div>
                            <div style="margin-top:6px;padding-top:6px;border-top:1px dashed rgba(255,255,255,0.15)">
                                <strong>Dikurasi &amp; Disetujui:</strong> 
                                <span style="color:#6ee7b7;font-weight:700">${this.escapeHTML(item.curator_name || 'Ibu Nurul Hidayah, S.Pd')}</span>
                                <small style="display:block;color:#94a3b8;font-size:11px">Kurator Resmi Seni Rupa Digi-Art</small>
                            </div>
                            <div style="margin-top:6px"><strong>Apresiasi:</strong> ${likesCount} Suka</div>
                        </div>

                        <div class="immersive-actions-stack">
                            <button class="btn-immersive-action primary" onclick="PameranApp.useInStudio(${index})">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m18 2 4 4-14 14H4v-4L18 2z"/></svg>
                                Buka &amp; Kembangkan di Studio Digital
                            </button>
                            <a href="${this.escapeHTML(safeImg)}" download="karya-${safeId}.png" class="btn-immersive-action secondary">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                Unduh Karya Seni
                            </a>
                            <button class="btn-immersive-action secondary" onclick="PameranApp.closeImmersivePreview()">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                Tutup Pratinjau
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        this._immersiveEscHandler = (e) => {
            if (e.key === 'Escape') {
                this.closeImmersivePreview();
            }
        };
        window.addEventListener('keydown', this._immersiveEscHandler);

        document.body.appendChild(overlay);
    },

    setStageMode(mode) {
        const stage = document.getElementById('pameranStage');
        const btnContain = document.getElementById('btnPamContain');
        const btnStretch = document.getElementById('btnPamStretch');

        if (mode === 'fill') {
            stage?.classList.add('forced-fill');
            btnContain?.classList.remove('active');
            btnStretch?.classList.add('active');
        } else {
            stage?.classList.remove('forced-fill');
            btnContain?.classList.add('active');
            btnStretch?.classList.remove('active');
        }
    },

    useInStudio(index) {
        const work = this.works[index];
        if (!work) return;
        const safeImg = App.safeUrl(work.image_path, '');
        if (!safeImg) return;
        sessionStorage.setItem('digiStudioImportImage', safeImg);
        window.location.href = 'index.php?page=studio';
    },

    formatDate(str) {
        if (!str) return '—';
        try {
            let isoStr = str;
            if (typeof str === 'string' && /^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/.test(str)) {
                isoStr = str.replace(' ', 'T') + '+07:00';
            }
            const d = new Date(isoStr);
            if (isNaN(d.getTime())) return str;
            return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        } catch (e) {
            return str;
        }
    },

    escapeHTML(str) {
        return String(str ?? '').replace(/[&<>"']/g, m => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[m]));
    }
};

window.PameranApp = PameranApp;
