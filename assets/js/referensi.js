/**
 * Search Engine Referensi Gambar (Google Images & Web Proxy)
 * - Tampilan Enclosed Card seragam dan rapi (tidak ada gambar tabrakan)
 * - Modal Pratinjau Imersif hampir memenuhi layar (Orientation-Adaptive)
 * - Fitur paksa perbesar/isi kanvas meski resolusi gambar asli kecil
 */

const ReferensiApp = {
    currentResults: [],

    init() {
        const input = document.getElementById('refSearchInput');
        if (input) {
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    this.search();
                }
            });
        }
        // Auto search query bawaan saat pertama buka
        this.search('gunung berapi');
    },

    quickSearch(keyword) {
        const input = document.getElementById('refSearchInput');
        if (input) input.value = keyword;
        this.search(keyword);
    },

    async search(keyword = null, refresh = false) {
        const input = document.getElementById('refSearchInput');
        const q = keyword || (input ? input.value.trim() : '') || 'gunung berapi';
        const grid = document.getElementById('refResultsGrid');
        const status = document.getElementById('refSearchStatus');

        if (!grid) return;

        grid.innerHTML = `
            <div class="ref-loading-card" style="grid-column:1/-1;text-align:center;padding:50px 20px">
                <div class="kahoot-spinner" style="margin:0 auto 16px"></div>
                <p>Mengambil 25 referensi gambar edukasi untuk “<strong>${this.escapeHTML(q)}</strong>”…</p>
            </div>
        `;
        if (status) status.innerHTML = '<span>Menghubungkan ke search engine referensi edukasi…</span>';

        try {
            const url = `api/referensi.php?q=${encodeURIComponent(q)}${refresh ? '&refresh=1' : ''}`;
            const res = await fetch(url);
            const data = await res.json();

            if (!data.success) {
                if (data.locked) {
                    grid.innerHTML = `
                        <div class="locked-card" style="grid-column:1/-1">
                            <div class="lock-icon">🔒</div>
                            <h2>Fitur Referensi Terkunci</h2>
                            <p>${data.message}</p>
                            <button class="btn" onclick="openLoginModal()">Masuk Akun</button>
                        </div>
                    `;
                    return;
                }
                if (data.family_safe === false) {
                    grid.innerHTML = `
                        <div class="card" style="grid-column:1/-1;padding:40px 30px;text-align:center;border:2px dashed #f59e0b;background:#fffbeb;border-radius:var(--radius-lg)">
                            <div style="font-size:52px;margin-bottom:12px">🛡️</div>
                            <h2 style="color:#b45309;font-size:20px;font-weight:800;margin-bottom:8px">Pencarian Dibatasi oleh Filter Keamanan Siswa (Family Safe)</h2>
                            <p style="color:#78350f;max-width:640px;margin:0 auto 20px;font-size:14px;line-height:1.6">
                                ${this.escapeHTML(data.message || 'Pencarian ini mengandung kata kunci tidak pantas, NSFW, atau berbatas umur yang dilarang pada lingkungan sekolah SMP.')}
                            </p>
                            <div style="background:#ffffff;border:1px solid #fde68a;border-radius:var(--radius-md);padding:14px 20px;max-width:540px;margin:0 auto 20px;text-align:left;font-size:13px;color:#92400e">
                                <strong>💡 Anjuran Pembelajaran:</strong> Gunakan kata kunci materi pelajaran SMP, seperti nama bentang alam, diagram sains, ragam hias seni nusantara, atau fabel cerita rakyat.
                            </div>
                            <div style="display:inline-flex;gap:10px;flex-wrap:wrap;justify-content:center">
                                <button class="btn" style="background:#059669;border-color:#059669" onclick="ReferensiApp.quickSearch('gunung berapi')">🌋 Cari Gunung Berapi (IPS)</button>
                                <button class="btn secondary" onclick="ReferensiApp.quickSearch('siklus air')">💧 Cari Siklus Air (IPA)</button>
                                <button class="btn secondary" onclick="ReferensiApp.quickSearch('batik mega mendung')">🎨 Cari Ragam Hias Batik</button>
                            </div>
                        </div>
                    `;
                    if (status) status.innerHTML = '<span style="color:#b45309;font-weight:700">🛡️ Filter SafeSearch aktif: Kata kunci disaring demi perlindungan siswa.</span>';
                    return;
                }
                throw new Error(data.message || 'Pencarian gagal.');
            }

            this.currentResults = data.results || [];

            if (!this.currentResults.length) {
                grid.innerHTML = `
                    <div class="card ref-empty" style="grid-column:1/-1;padding:40px;text-align:center">
                        <div style="font-size:40px">🖼️</div>
                        <h3>Tidak ada gambar ditemukan</h3>
                        <p>Coba gunakan kata kunci materi SMP seperti “gunung berapi”, “siklus air”, “peta indonesia”, atau “sel tumbuhan”.</p>
                    </div>
                `;
                if (status) status.textContent = '0 referensi ditemukan.';
                return;
            }

            if (status) {
                const isCached = data.source_type === 'cache';
                status.innerHTML = `
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
                        <span>Menampilkan <strong>${this.currentResults.length} referensi gambar</strong> untuk “<strong>${this.escapeHTML(q)}</strong>” (Hasil konsisten & relevan).</span>
                        <button class="btn secondary small" style="padding:4px 10px;font-size:11px" onclick="ReferensiApp.search('${this.escapeHTML(q)}', true)" title="Segarkan data dari server">
                            🔄 Perbarui Pencarian
                        </button>
                    </div>
                `;
            }

            // TAMPILAN ENCLOSED CARD (Terisolasi rapi, tidak tabrakan)
            grid.innerHTML = this.currentResults.map((item, idx) => `
                <article class="card-enclosed">
                    <div class="card-enclosed-thumb" onclick="ReferensiApp.previewImmersive(${idx})">
                        <span class="card-enclosed-tag">${this.escapeHTML(item.source || 'Google Images')}</span>
                        <img src="${item.thumb || item.url}" 
                             alt="${this.escapeHTML(item.title)}" 
                             loading="lazy"
                             onerror="this.src='assets/img/sample-volcano.svg'">
                        <div class="card-enclosed-overlay">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="M11 8v6m-3-3h6"/></svg>
                            <span>Buka Layar Penuh</span>
                        </div>
                    </div>
                    <div class="card-enclosed-body">
                        <h3 class="card-enclosed-title" title="${this.escapeHTML(item.title)}">
                            ${this.escapeHTML(item.title)}
                        </h3>
                        <p class="card-enclosed-desc">
                            ${this.escapeHTML(item.description || 'Referensi visual untuk mengamati bentuk, warna, dan pencahayaan.')}
                        </p>
                        <div class="card-enclosed-footer">
                            <button class="btn small" style="flex:1" onclick="ReferensiApp.useInStudio(${idx})" aria-label="Buka dan gambar di studio">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m18 2 4 4-14 14H4v-4L18 2z"/></svg>
                                Ke Studio
                            </button>
                            <button class="btn secondary small" onclick="ReferensiApp.previewImmersive(${idx})" title="Lihat Layar Penuh" aria-label="Lihat Layar Penuh">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                            </button>
                            <a href="${item.page_url || item.url}" target="_blank" rel="noopener noreferrer" class="btn secondary small" title="Buka Sumber Asli" aria-label="Sumber Gambar Asli">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                            </a>
                        </div>
                    </div>
                </article>
            `).join('');

        } catch (err) {
            grid.innerHTML = `
                <div class="card ref-empty" style="grid-column:1/-1;padding:40px;text-align:center">
                    <div style="font-size:40px">⚠️</div>
                    <h3>Gagal mengambil referensi online</h3>
                    <p>${err.message}</p>
                    <button class="btn small" onclick="ReferensiApp.search()">Coba Lagi</button>
                </div>
            `;
            if (status) status.textContent = 'Koneksi pencarian terganggu.';
        }
    },

    useInStudio(index) {
        const item = this.currentResults[index];
        if (!item) return;

        sessionStorage.setItem('digiStudioImportImage', item.url);
        sessionStorage.setItem('digiStudioImportImageTitle', item.title || 'Referensi');
        window.location.href = 'index.php?page=studio';
    },

    /**
     * Modal Imersif Layar Penuh (Orientation Adaptive + Paksa Penuh)
     */
    previewImmersive(index) {
        const item = this.currentResults[index];
        if (!item) return;

        const overlay = document.createElement('div');
        overlay.className = 'immersive-preview-overlay';
        overlay.id = 'immersiveModalOverlay';
        overlay.innerHTML = `
            <div class="immersive-preview-modal" role="dialog" aria-modal="true" aria-label="${this.escapeHTML(item.title)}">
                <!-- Header -->
                <div class="immersive-header">
                    <h3>🔍 ${this.escapeHTML(item.title)}</h3>
                    <button class="btn-close" style="position:static" onclick="document.getElementById('immersiveModalOverlay').remove()" aria-label="Tutup Pratinjau">×</button>
                </div>

                <!-- Content Grid (Orientation Adaptive) -->
                <div class="immersive-content-grid">
                    <!-- Image Stage -->
                    <div class="immersive-image-stage" id="immersiveStage">
                        <img src="${item.url}" alt="${this.escapeHTML(item.title)}" id="immersiveImg" onerror="this.src='${item.thumb || 'assets/img/sample-volcano.svg'}'">
                        
                        <!-- Floating Toolbar: Paksa Penuh / Fit Switcher -->
                        <div class="immersive-stage-toolbar">
                            <button class="btn-stage-ctrl active" id="btnFitContain" onclick="ReferensiApp.setStageMode('contain')">
                                📐 Skala Proporsional
                            </button>
                            <button class="btn-stage-ctrl" id="btnFitStretch" onclick="ReferensiApp.setStageMode('fill')">
                                ↔️ Paksa Penuh (Fill/Stretch)
                            </button>
                        </div>
                    </div>

                    <!-- Sidebar Metadata -->
                    <div class="immersive-sidebar-panel">
                        <div>
                            <h4>${this.escapeHTML(item.title)}</h4>
                            <p style="font-size:13px;color:#94a3b8;margin-top:6px">${this.escapeHTML(item.description)}</p>
                        </div>

                        <div class="immersive-meta-box">
                            <div><strong>Sumber Web:</strong> ${this.escapeHTML(item.source)}</div>
                            <div style="margin-top:4px"><strong>Penyedia:</strong> Google Images / Web</div>
                            <div style="margin-top:4px"><strong>Resolusi Perkiraan:</strong> ${item.width || 800} × ${item.height || 600} px</div>
                        </div>

                        <div class="immersive-actions-stack">
                            <button class="btn-immersive-action primary" onclick="ReferensiApp.useInStudio(${index})">
                                🎨 Buka & Gambar di Studio Digital
                            </button>
                            <a href="${item.url}" download="referensi-digiart.jpg" target="_blank" class="btn-immersive-action secondary">
                                💾 Unduh Gambar Resolusi Asli
                            </a>
                            <a href="${item.page_url || item.url}" target="_blank" rel="noopener noreferrer" class="btn-immersive-action secondary">
                                ↗ Buka Halaman Sumber Asli
                            </a>
                            <button class="btn-immersive-action secondary" onclick="document.getElementById('immersiveModalOverlay').remove()">
                                ✕ Tutup Pratinjau
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Tutup jika tekan ESC
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
        const stage = document.getElementById('immersiveStage');
        const btnContain = document.getElementById('btnFitContain');
        const btnStretch = document.getElementById('btnFitStretch');

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

    escapeHTML(str) {
        return String(str).replace(/[&<>"']/g, m => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[m]));
    }
};

window.ReferensiApp = ReferensiApp;
