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
            const res = await fetch('api/pameran.php?action=get_approved');
            const data = await res.json();
            this.works = data.works || [];

            if (!this.works.length) {
                grid.innerHTML = `
                    <div class="card" style="grid-column:1/-1;padding:50px 20px;text-align:center">
                        <div style="font-size:48px">🎨</div>
                        <h3>Belum ada karya yang dipamerkan</h3>
                        <p>Jadilah siswa pertama yang mengirim karya ilustrasi ke pameran kelas!</p>
                        <button class="btn small" style="margin-top:14px" onclick="PameranApp.openUploadModal()">Unggah Karya Sekarang</button>
                    </div>
                `;
                return;
            }

            // TAMPILAN ENCLOSED CARD (Seragam & Terisolasi, tidak ada tabrakan)
            grid.innerHTML = this.works.map((w, idx) => `
                <article class="card-enclosed">
                    <div class="card-enclosed-thumb" onclick="PameranApp.previewImmersive(${idx})">
                        <span class="card-enclosed-tag">${this.escapeHTML(w.creator_name)} (${this.escapeHTML(w.kelas || 'VIII')})</span>
                        <img src="${w.image_path}" 
                             alt="${this.escapeHTML(w.title)}" 
                             loading="lazy"
                             onerror="this.src='assets/img/sample-volcano.svg'">
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
                            <button class="pameran-like-btn" onclick="PameranApp.likeWork(${w.id}, this)" aria-label="Sukai karya">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" stroke="none" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                                <span class="like-count">${w.likes_count || 0}</span>
                            </button>
                            <button class="btn small" onclick="PameranApp.previewImmersive(${idx})" aria-label="Lihat Penuh">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                                Detail Karya
                            </button>
                        </div>
                    </div>
                </article>
            `).join('');

        } catch (err) {
            grid.innerHTML = `
                <div class="card" style="grid-column:1/-1;padding:30px;text-align:center">
                    <p>Gagal memuat galeri pameran: ${err.message}</p>
                </div>
            `;
        }
    },

    openUploadModal() {
        const modal = document.getElementById('uploadModal');
        if (modal) {
            modal.style.display = 'flex';
            if (window.currentUser) {
                const nameInput = document.getElementById('workCreatorName');
                const classInput = document.getElementById('workKelas');
                if (nameInput && !nameInput.value) nameInput.value = window.currentUser.full_name;
                if (classInput && !classInput.value) classInput.value = window.currentUser.kelas || 'VIII A';
            }
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
            infoBox.innerHTML = '⏳ Mengompres gambar di browser Anda…';
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
                        <strong>✓ Kompresi Berhasil:</strong> 
                        ${compressed.originalFormatted} → <strong>${compressed.compressedFormatted}</strong> 
                        <span>(Hemat ${compressed.savedPercent}%)</span>
                    </div>
                `;
            }
        } catch (err) {
            if (infoBox) {
                infoBox.innerHTML = `<span style="color:#ef4444">⚠️ ${err.message}</span>`;
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
            const uploadRes = await fetch('api/upload.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ image_base64: this.compressedFilePayload })
            });
            const uploadData = await uploadRes.json();
            if (!uploadData.success) throw new Error(uploadData.message);

            // 2. Submit data karya ke database (status otomatis 'pending')
            const submitRes = await fetch('api/pameran.php?action=submit', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    title,
                    creator_name: creatorName,
                    kelas,
                    description,
                    image_path: uploadData.file_path
                })
            });
            const submitData = await submitRes.json();
            if (!submitData.success) throw new Error(submitData.message);

            this.closeUploadModal();
            this.compressedFilePayload = null;
            document.getElementById('workForm')?.reset();

            // Tampilkan dialog informasi approval guru
            this.showApprovalNotice();
        } catch (err) {
            window.showToast?.('Gagal mengunggah karya: ' + err.message);
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
                <div style="font-size:52px;margin-bottom:12px">🎉</div>
                <h2>Karya Berhasil Terkirim!</h2>
                <p style="color:var(--muted);font-size:14px;line-height:1.6">
                    Karyamu telah masuk ke antrean verifikasi. Karya akan dipublikasikan di halaman Pameran setelah 
                    <strong>disetujui oleh Guru</strong> melalui Dashboard Guru.
                </p>
                <button class="btn" style="margin-top:16px;width:100%" onclick="this.closest('.modal-overlay').remove()">
                    Mengerti & Kembali ke Galeri
                </button>
            </div>
        `;
        document.body.appendChild(overlay);
    },

    async likeWork(id, btn) {
        try {
            const countEl = btn.querySelector('.like-count');
            const current = parseInt(countEl.textContent, 10) || 0;
            countEl.textContent = current + 1;
            btn.classList.add('liked');

            await fetch('api/pameran.php?action=like', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
        } catch (e) {}
    },

    /**
     * Modal Imersif Layar Penuh (Orientation Adaptive + Paksa Penuh)
     */
    previewImmersive(index) {
        const item = this.works[index];
        if (!item) return;

        const overlay = document.createElement('div');
        overlay.className = 'immersive-preview-overlay';
        overlay.id = 'pameranImmersiveOverlay';
        overlay.innerHTML = `
            <div class="immersive-preview-modal" role="dialog" aria-modal="true" aria-label="${this.escapeHTML(item.title)}">
                <!-- Header -->
                <div class="immersive-header">
                    <h3>🖼️ ${this.escapeHTML(item.title)}</h3>
                    <button class="btn-close" style="position:static" onclick="document.getElementById('pameranImmersiveOverlay').remove()" aria-label="Tutup Pratinjau">×</button>
                </div>

                <!-- Content Grid (Orientation Adaptive) -->
                <div class="immersive-content-grid">
                    <!-- Image Stage -->
                    <div class="immersive-image-stage" id="pameranStage">
                        <img src="${item.image_path}" alt="${this.escapeHTML(item.title)}" id="pameranStageImg">
                        
                        <!-- Floating Toolbar: Paksa Penuh / Fit Switcher -->
                        <div class="immersive-stage-toolbar">
                            <button class="btn-stage-ctrl active" id="btnPamContain" onclick="PameranApp.setStageMode('contain')">
                                📐 Skala Proporsional
                            </button>
                            <button class="btn-stage-ctrl" id="btnPamStretch" onclick="PameranApp.setStageMode('fill')">
                                ↔️ Paksa Penuh (Fill/Stretch)
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
                            <div style="margin-top:4px"><strong>Tanggal Terbit:</strong> ${this.formatDate(item.created_at)}</div>
                            <div style="margin-top:6px;padding-top:6px;border-top:1px dashed rgba(255,255,255,0.15)">
                                <strong>🎨 Dikurasi & Disetujui:</strong> 
                                <span style="color:#6ee7b7;font-weight:700">✓ ${this.escapeHTML(item.curator_name || 'Ibu Nurul Hidayah, S.Pd')}</span>
                                <small style="display:block;color:#94a3b8;font-size:11px">Kurator Resmi Seni Rupa Digi-Art</small>
                            </div>
                            <div style="margin-top:6px"><strong>Apresiasi:</strong> ❤️ ${item.likes_count || 0} Suka</div>
                        </div>

                        <div class="immersive-actions-stack">
                            <button class="btn-immersive-action primary" onclick="PameranApp.useInStudio('${item.image_path}')">
                                🎨 Buka & Kembangkan di Studio Digital
                            </button>
                            <a href="${item.image_path}" download="karya-${item.id}.png" class="btn-immersive-action secondary">
                                💾 Unduh Karya Seni
                            </a>
                            <button class="btn-immersive-action secondary" onclick="document.getElementById('pameranImmersiveOverlay').remove()">
                                ✕ Tutup Pratinjau
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        const escHandler = (e) => {
            if (e.key === 'Escape') {
                overlay.remove();
                window.removeEventListener('keydown', escHandler);
            }
        };
        window.addEventListener('keydown', escHandler);

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

    useInStudio(imagePath) {
        sessionStorage.setItem('digiStudioImportImage', imagePath);
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
        return String(str).replace(/[&<>"']/g, m => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[m]));
    }
};

window.PameranApp = PameranApp;
