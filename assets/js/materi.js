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
            const res = await fetch('api/materi.php?action=get_active');
            const data = await res.json();

            if (!data.success) {
                if (data.locked) {
                    container.innerHTML = `
                        <div class="locked-card">
                            <div class="lock-icon">🔒</div>
                            <h2>Materi Pembelajaran Terkunci</h2>
                            <p>${data.message}</p>
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
                    <h3>⚠️ Terjadi Kesalahan</h3>
                    <p>${err.message}</p>
                    <button class="btn small" onclick="MateriApp.init()">Coba Lagi</button>
                </div>
            `;
        }
    },

    render() {
        const container = document.getElementById('materiContainer');
        if (!container) return;

        const isTeacher = window.currentUser && (window.currentUser.role === 'guru' || window.currentUser.role === 'admin');

        container.innerHTML = this.sections.map((sec) => {
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
                                <div class="step-num">${it.step || (idx + 1)}</div>
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
                        <span class="source-icon">📚</span>
                        <div>${this.escapeHTML(sec.source_note)}</div>
                    </div>
                `;
            }

            // Quick Edit shortcut untuk Guru/Admin
            let teacherBadge = '';
            if (isTeacher) {
                teacherBadge = `
                    <div class="teacher-inline-ctrl">
                        <span class="mode-indicator">Mode: <strong>${sec.show_cards == 1 ? 'Kartu (Card Grid)' : sec.display_mode.toUpperCase()}</strong></span>
                        <a href="index.php?page=dashboard#materi-tab" class="btn-edit-sec">✏️ Atur Template di Dashboard</a>
                    </div>
                `;
            }

            return `
                <section class="lesson-section" id="section-${sec.section_number}">
                    <div class="lesson-header">
                        <span class="sec-number">Bagian ${sec.section_number}</span>
                        <h2>${this.escapeHTML(sec.title)}</h2>
                        ${sec.subtitle ? `<p class="sec-subtitle">${this.escapeHTML(sec.subtitle)}</p>` : ''}
                        ${teacherBadge}
                    </div>
                    <div class="lesson-body">
                        ${sec.image_url ? `
                            <div class="materi-section-banner" style="margin-bottom:20px;border-radius:var(--radius-md);overflow:hidden;border:1px solid var(--line);box-shadow:var(--shadow-sm);background:#0f172a">
                                <img src="${this.escapeHTML(sec.image_url)}" alt="${this.escapeHTML(sec.title)}" style="width:100%;max-height:320px;object-fit:cover;display:block" onerror="this.style.display='none'">
                            </div>
                        ` : ''}
                        ${bodyHTML}
                        ${sourceHTML}
                    </div>
                </section>
            `;
        }).join('');
    },

    bindSearch() {
        const searchInput = document.getElementById('materialSearch');
        if (!searchInput) return;

        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            const sections = document.querySelectorAll('.lesson-section');

            sections.forEach((sec) => {
                const text = sec.textContent.toLowerCase();
                if (!query || text.includes(query)) {
                    sec.style.display = 'block';
                    sec.style.opacity = '1';
                } else {
                    sec.style.display = 'none';
                }
            });
        });
    },

    escapeHTML(str) {
        return String(str).replace(/[&<>"']/g, m => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[m]));
    }
};

window.MateriApp = MateriApp;
