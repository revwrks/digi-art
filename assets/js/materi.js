/**
 * Materi Renderer & Flexible Template Engine
 * Mengatur tampilan materi yang fleksibel (cards, list, prose, steps, on/off card toggles)
 * Membaca konfigurasi dari database yang diatur oleh guru.
 */

const MateriApp = {
    sections: [],

    async init() {
        const container = document.getElementById('materiContainer');
        if (!container) return;

        container.innerHTML = `
            <div class="materi-loading">
                <div class="kahoot-spinner"></div>
                <p>Memuat materi pembelajaran seni rupa…</p>
            </div>
        `;

        try {
            const res = await App.apiFetch('api/materi.php?action=get_active');
            const data = await res.json();

            if (!data.success) {
                if (data.locked) {
                    container.innerHTML = `
                        <div class="locked-card">
                            <div class="lock-icon" aria-hidden="true">
                                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </div>
                            <h2>Materi Pembelajaran Terkunci</h2>
                            <p>${this.escapeHTML(data.message || 'Materi pembelajaran hanya dapat diakses oleh akun Siswa dan Guru.')}</p>
                            <button class="btn" onclick="openLoginModal()">Masuk Akun Siswa / Guru</button>
                        </div>
                    `;
                    return;
                }
                throw new Error(data.message || 'Gagal memuat materi.');
            }

            this.sections = data.sections || [];

            // Sinkronisasi info bab & judul jika diperbarui dari server
            if (data.header) {
                const kickerEl = document.getElementById('materiKicker');
                const titleEl  = document.getElementById('materiTitle');
                const descEl   = document.getElementById('materiDesc');
                if (kickerEl && data.header.bab) kickerEl.textContent = data.header.bab;
                if (titleEl && data.header.title) titleEl.textContent = data.header.title;
                if (descEl && data.header.description) descEl.textContent = data.header.description;
            }

            this.render();
            this.bindSearch();
        } catch (err) {
            container.innerHTML = `
                <div class="card" style="padding:30px;text-align:center">
                    <div style="display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:12px;background:#fef2f2;color:#b91c1c;margin-bottom:10px" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    </div>
                    <h3>Terjadi Kesalahan</h3>
                    <p>${this.escapeHTML(err.message || 'Gagal memuat materi.')}</p>
                    <button class="btn small" onclick="MateriApp.init()">Coba Lagi</button>
                </div>
            `;
        }
    },

    render() {
        const container = document.getElementById('materiContainer');
        if (!container) return;

        const isTeacher = window.currentUser && (window.currentUser.role === 'guru' || window.currentUser.role === 'admin');

        const sectionsHTML = this.sections.map((sec) => {
            const content = sec.content || {};
            let bodyHTML = '';

            // 1. Tampilan KARTU (Jika show_cards diaktifkan atau mode 'cards')
            if (sec.show_cards == 1 || sec.display_mode === 'cards') {
                const items = content.items || [];
                bodyHTML = `
                    <div class="materi-cards-grid">
                        ${items.map((it, idx) => `
                            <div class="materi-card-item">
                                <div class="materi-card-badge">${this.escapeHTML(it.tag || String(idx + 1).padStart(2, '0'))}</div>
                                <h3>${this.escapeHTML(it.title || '')}</h3>
                                <p>${this.escapeHTML(it.desc || '')}</p>
                            </div>
                        `).join('')}
                    </div>
                `;
            } 
            // 2. Tampilan LIST / DAFTAR (Jika card dimatikan oleh guru dan memilih list)
            else if (sec.display_mode === 'list') {
                const items = content.items || [];
                bodyHTML = `
                    <ul class="materi-list-clean">
                        ${items.map(it => `
                            <li>
                                <strong>${this.escapeHTML(it.title || '')}</strong>
                                <span>${this.escapeHTML(it.desc || '')}</span>
                            </li>
                        `).join('')}
                    </ul>
                `;
            } 
            // 3. Tampilan LANGKAH-LANGKAH (Steps)
            else if (sec.display_mode === 'steps') {
                const items = content.items || [];
                bodyHTML = `
                    <div class="materi-steps-list">
                        ${items.map((it, idx) => `
                            <div class="materi-step-item">
                                <div class="step-num">${this.escapeHTML(it.step || (idx + 1))}</div>
                                <div class="step-content">
                                    <h4>${this.escapeHTML(it.title || '')}</h4>
                                    <p>${this.escapeHTML(it.desc || '')}</p>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;
            } 
            // 4. Tampilan TEKS / PROSA (Paragraf mengalir)
            else {
                const paragraphs = content.paragraphs || (content.items ? content.items.map(i => `${i.title}: ${i.desc}`) : []);
                bodyHTML = `
                    <div class="materi-prose">
                        ${paragraphs.map(p => `<p>${this.escapeHTML(p)}</p>`).join('')}
                    </div>
                `;
            }

            // Catatan Rujukan / Sumber (Jika ada)
            let sourceHTML = '';
            if (sec.source_note) {
                sourceHTML = `
                    <div class="materi-source-callout">
                        <span class="source-icon" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10m-10 4h10"/></svg>
                        </span>
                        <div>${this.escapeHTML(sec.source_note)}</div>
                    </div>
                `;
            }

            // Quick Edit shortcut untuk Guru/Admin
            let teacherBadge = '';
            if (isTeacher) {
                const modeLabel = sec.show_cards == 1 ? 'Kartu (Card Grid)' : String(sec.display_mode || 'prose').toUpperCase();
                teacherBadge = `
                    <div class="teacher-inline-ctrl">
                        <span class="mode-indicator">Mode: <strong>${this.escapeHTML(modeLabel)}</strong></span>
                        <a href="index.php?page=dashboard#materi-tab" class="btn-edit-sec">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px" aria-hidden="true"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                            Atur Template di Dashboard
                        </a>
                    </div>
                `;
            }

            const safeImageUrl = sec.image_url ? App.safeUrl(sec.image_url, '') : '';
            const secNumber = this.escapeHTML(sec.section_number);

            return `
                <section class="lesson-section" id="section-${secNumber}">
                    <div class="lesson-header">
                        <span class="sec-number">Bagian ${secNumber}</span>
                        <h2>${this.escapeHTML(sec.title)}</h2>
                        ${sec.subtitle ? `<p class="sec-subtitle">${this.escapeHTML(sec.subtitle)}</p>` : ''}
                        ${teacherBadge}
                    </div>
                    <div class="lesson-body">
                        ${safeImageUrl ? `
                            <div class="materi-section-banner" style="margin-bottom:20px;border-radius:var(--radius-md);overflow:hidden;border:1px solid var(--line);box-shadow:var(--shadow-sm);background:#0f172a">
                                <img src="${this.escapeHTML(safeImageUrl)}" alt="${this.escapeHTML(sec.title)}" style="width:100%;max-height:320px;object-fit:cover;display:block" onerror="this.style.display='none'">
                            </div>
                        ` : ''}
                        ${bodyHTML}
                        ${sourceHTML}
                    </div>
                </section>
            `;
        }).join('');

        container.innerHTML = sectionsHTML + `
            <div id="materiSearchEmpty" class="card" style="display:none;padding:36px 24px;text-align:center;margin-top:16px">
                <div style="display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:12px;background:var(--primary-light);color:var(--primary);margin-bottom:10px" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </div>
                <h3>Materi Tidak Ditemukan</h3>
                <p style="color:var(--muted);margin-top:6px">Tidak ada bagian materi yang cocok dengan kata kunci pencarian.</p>
            </div>
        `;
    },

    bindSearch() {
        const searchInput = document.getElementById('materialSearch');
        if (!searchInput) return;

        const filterSections = () => {
            const query = searchInput.value.toLowerCase().trim();
            const sections = document.querySelectorAll('.lesson-section');
            let visibleCount = 0;

            sections.forEach((sec) => {
                const text = sec.textContent.toLowerCase();
                if (!query || text.includes(query)) {
                    sec.style.display = 'block';
                    sec.style.opacity = '1';
                    visibleCount++;
                } else {
                    sec.style.display = 'none';
                }
            });

            const emptyEl = document.getElementById('materiSearchEmpty');
            if (emptyEl) {
                emptyEl.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        };

        if (searchInput.dataset.bound !== '1') {
            searchInput.dataset.bound = '1';
            searchInput.addEventListener('input', filterSections);
        }
        filterSections();
    },

    escapeHTML(str) {
        return String(str ?? '').replace(/[&<>"']/g, m => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[m]));
    }
};

window.MateriApp = MateriApp;
