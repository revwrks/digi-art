/**
 * Digi-Art Core Application JavaScript
 * Mengatur sesi pengguna, router navigasi, modal login/profil, dan toast.
 */

window.currentUser = null;

const App = {
    async init() {
        await this.checkAuthStatus();
        this.bindEvents();
    },

    getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return (meta && meta.getAttribute('content')) || window.__csrfToken || '';
    },

    setCsrfToken(token) {
        if (!token) return;
        window.__csrfToken = token;
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            meta.setAttribute('content', token);
        }
    },

    apiFetch(url, options = {}) {
        const mergedOptions = { ...options };
        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
            ...(options.headers || {})
        };
        const method = (options.method || 'GET').toUpperCase();

        if (method !== 'GET' && method !== 'HEAD') {
            headers['X-CSRF-Token'] = this.getCsrfToken();
            const hasContentType = Object.keys(headers).some(
                (key) => key.toLowerCase() === 'content-type'
            );
            if (typeof options.body === 'string' && !hasContentType) {
                headers['Content-Type'] = 'application/json';
            }
        }

        mergedOptions.method = method;
        mergedOptions.headers = headers;
        return fetch(url, mergedOptions);
    },

    safeUrl(url, fallback = 'assets/img/sample-volcano-art.png') {
        if (typeof url !== 'string') return fallback;
        const u = url.trim();
        if (!u || /[\u0000-\u001F\u007F]/.test(u)) return fallback;

        if ((u.startsWith('assets/') || u.startsWith('uploads/')) && !u.includes('..') && !u.includes('\\')) {
            return u;
        }

        if (/^data:image\/(png|jpeg|jpg|webp|svg\+xml);base64,[A-Za-z0-9+/=\s]+$/i.test(u)) {
            return u;
        }

        if (/^https?:\/\//i.test(u)) {
            try {
                const parsed = new URL(u, window.location.origin);
                if (parsed.protocol === 'https:' || parsed.protocol === 'http:') {
                    return parsed.href;
                }
            } catch (e) {
                return fallback;
            }
        }

        return fallback;
    },

    async checkAuthStatus() {
        try {
            const res = await this.apiFetch('api/auth.php?action=status');
            const data = await res.json();
            if (data.csrf_token) {
                this.setCsrfToken(data.csrf_token);
            }
            window.currentUser = data.logged_in ? data.user : null;
            this.updateNavAuthUI(data);
        } catch (e) {
            window.currentUser = null;
        }
    },

    updateNavAuthUI(data) {
        const userBtn = document.getElementById('btnNavUser');
        const roleBadge = document.getElementById('navRoleBadge');
        const teacherLinks = document.querySelectorAll('.teacher-only-link');

        if (data.logged_in && data.user) {
            if (userBtn) {
                userBtn.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg><span>${this.escapeHTML(data.user.full_name)}</span>`;
                userBtn.title = 'Buka Pengaturan Profil';
                userBtn.onclick = () => this.openProfileModal();
            }
            if (roleBadge) {
                roleBadge.textContent = data.user.role.toUpperCase();
                roleBadge.className = `role-badge ${data.user.role}`;
                roleBadge.style.display = 'inline-block';
            }
            // Tampilkan link dashboard untuk guru / admin
            if (data.can_manage_materi) {
                teacherLinks.forEach(el => el.style.display = 'inline-flex');
            } else {
                teacherLinks.forEach(el => el.style.display = 'none');
            }

            // Update mobile drawer info
            const drawerName = document.getElementById('drawerUserName');
            const drawerBadge = document.getElementById('drawerUserBadge');
            if (drawerName) drawerName.textContent = data.user.full_name;
            if (drawerBadge) {
                drawerBadge.textContent = data.user.role.toUpperCase();
                drawerBadge.className = `role-badge ${data.user.role}`;
                drawerBadge.style.display = 'inline-block';
            }
        } else {
            if (userBtn) {
                userBtn.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg><span>Masuk</span>`;
                userBtn.title = 'Masuk Akun';
                userBtn.onclick = () => this.openLoginModal();
            }
            if (roleBadge) roleBadge.style.display = 'none';
            teacherLinks.forEach(el => el.style.display = 'none');

            const drawerName = document.getElementById('drawerUserName');
            const drawerBadge = document.getElementById('drawerUserBadge');
            if (drawerName) drawerName.textContent = 'Tamu (Belum Masuk)';
            if (drawerBadge) drawerBadge.style.display = 'none';
        }
    },

    async handleLogin(e) {
        if (e) e.preventDefault();
        const usernameInput = document.getElementById('loginUsername');
        const passwordInput = document.getElementById('loginPassword');
        const errBox = document.getElementById('loginError');
        const btn = document.getElementById('btnLoginSubmit');

        const username = usernameInput?.value.trim();
        const password = passwordInput?.value.trim();

        if (!username || !password) {
            if (errBox) {
                errBox.textContent = 'Username dan password wajib diisi.';
                errBox.style.display = 'block';
            }
            return;
        }

        if (btn) btn.disabled = true;
        if (errBox) errBox.style.display = 'none';

        try {
            const res = await this.apiFetch('api/auth.php?action=login', {
                method: 'POST',
                body: JSON.stringify({ username, password })
            });
            const data = await res.json();

            if (data.success) {
                if (data.csrf_token) {
                    this.setCsrfToken(data.csrf_token);
                }
                window.currentUser = data.user;
                this.closeLoginModal();
                this.showToast(`Selamat datang, ${data.user.full_name}!`);
                setTimeout(() => window.location.reload(), 500);
            } else {
                if (errBox) {
                    errBox.textContent = data.message || 'Gagal masuk akun.';
                    errBox.style.display = 'block';
                }
            }
        } catch (err) {
            if (errBox) {
                errBox.textContent = 'Gagal terhubung ke server autentikasi.';
                errBox.style.display = 'block';
            }
        } finally {
            if (btn) btn.disabled = false;
        }
    },

    async handleLogout() {
        try {
            await this.apiFetch('api/auth.php?action=logout', {
                method: 'POST',
                body: JSON.stringify({})
            });
            this.showToast('Berhasil keluar akun.');
            setTimeout(() => {
                window.location.href = 'index.php?page=home';
            }, 400);
        } catch (e) {
            window.location.reload();
        }
    },

    async handleUpdateProfile(e) {
        if (e) e.preventDefault();
        const newUsername = document.getElementById('profUsername')?.value.trim();
        const newPassword = document.getElementById('profNewPassword')?.value.trim();
        const currentPassword = document.getElementById('profCurrentPassword')?.value.trim();
        const errBox = document.getElementById('profileError');
        const btn = document.getElementById('btnUpdateProfile');

        if (!newUsername) {
            if (errBox) {
                errBox.textContent = 'Username tidak boleh kosong.';
                errBox.style.display = 'block';
            }
            return;
        }

        if (btn) btn.disabled = true;
        if (errBox) errBox.style.display = 'none';

        try {
            const res = await this.apiFetch('api/auth.php?action=update_profile', {
                method: 'POST',
                body: JSON.stringify({
                    username: newUsername,
                    new_password: newPassword || null,
                    current_password: currentPassword || null
                })
            });
            const data = await res.json();

            if (data.success) {
                this.showToast('Profil dan keamanan berhasil diperbarui.');
                this.closeProfileModal();
                setTimeout(() => window.location.reload(), 600);
            } else {
                if (errBox) {
                    errBox.textContent = data.message || 'Gagal memperbarui profil.';
                    errBox.style.display = 'block';
                }
            }
        } catch (err) {
            if (errBox) {
                errBox.textContent = 'Gagal memperbarui profil.';
                errBox.style.display = 'block';
            }
        } finally {
            if (btn) btn.disabled = false;
        }
    },

    openLoginModal() {
        const modal = document.getElementById('loginModal');
        if (modal) {
            modal.style.display = 'flex';
            const err = document.getElementById('loginError');
            if (err) err.style.display = 'none';
        }
    },

    closeLoginModal() {
        const modal = document.getElementById('loginModal');
        if (modal) modal.style.display = 'none';
    },

    openProfileModal() {
        if (!window.currentUser) {
            this.openLoginModal();
            return;
        }
        const modal = document.getElementById('profileModal');
        if (modal) {
            document.getElementById('profFullName').textContent = window.currentUser.full_name;
            document.getElementById('profRoleTag').textContent = window.currentUser.role.toUpperCase();
            document.getElementById('profUsername').value = window.currentUser.username;
            document.getElementById('profKelas').textContent = window.currentUser.kelas || '—';
            modal.style.display = 'flex';
            const err = document.getElementById('profileError');
            if (err) err.style.display = 'none';
        }
    },

    closeProfileModal() {
        const modal = document.getElementById('profileModal');
        if (modal) modal.style.display = 'none';
    },

    showToast(msg) {
        const toast = document.getElementById('appToast');
        if (!toast) return;
        toast.textContent = msg;
        toast.style.display = 'block';
        clearTimeout(window.__toastTimer);
        window.__toastTimer = setTimeout(() => {
            toast.style.display = 'none';
        }, 3200);
    },

    closeModalOverlay(modal) {
        if (!modal) return;
        const staticModalIds = [
            'loginModal', 'profileModal', 'uploadModal',
            'addMateriModal', 'editMateriModal', 'quizQuestionModal',
            'createUserModal', 'editUserModal', 'textToolModal',
            'newCanvasConfirmModal'
        ];
        if (modal.id === 'uploadModal' && window.PameranApp?.closeUploadModal) {
            window.PameranApp.closeUploadModal();
        } else if (modal.id && staticModalIds.includes(modal.id)) {
            modal.style.display = 'none';
        } else {
            modal.remove();
        }
    },

    bindEvents() {
        // Quick demo login fill
        window.fillDemoLogin = (user, pass) => {
            const u = document.getElementById('loginUsername');
            const p = document.getElementById('loginPassword');
            if (u && p) {
                u.value = user;
                p.value = pass;
            }
        };

        if (this._eventsBound) return;
        this._eventsBound = true;

        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape') return;
            const drawer = document.getElementById('mobileNavDrawer');
            if (drawer && drawer.classList.contains('open')) {
                this.closeMobileNav();
            }
            document.querySelectorAll('.modal-overlay').forEach((overlay) => {
                if (getComputedStyle(overlay).display !== 'none') {
                    this.closeModalOverlay(overlay);
                }
            });
        });

        document.addEventListener('click', (e) => {
            const target = e.target;
            if (target && target.classList && target.classList.contains('modal-overlay')) {
                this.closeModalOverlay(target);
            }
        });
    },

    toggleMobileNav() {
        const drawer = document.getElementById('mobileNavDrawer');
        const toggle = document.getElementById('mobileNavToggle');
        const overlay = document.getElementById('mobileNavOverlay');
        if (!drawer) return;
        const isOpen = drawer.classList.contains('open');
        if (isOpen) {
            this.closeMobileNav();
        } else {
            drawer.classList.add('open');
            if (toggle) {
                toggle.classList.add('active');
                toggle.setAttribute('aria-expanded', 'true');
            }
            if (overlay) overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    },

    closeMobileNav() {
        const drawer = document.getElementById('mobileNavDrawer');
        const toggle = document.getElementById('mobileNavToggle');
        const overlay = document.getElementById('mobileNavOverlay');
        if (drawer) drawer.classList.remove('open');
        if (toggle) {
            toggle.classList.remove('active');
            toggle.setAttribute('aria-expanded', 'false');
        }
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    },

    escapeHTML(str) {
        return String(str ?? '').replace(/[&<>"']/g, m => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[m]));
    }
};

window.App = App;
window.showToast = (msg) => App.showToast(msg);
window.openLoginModal = () => App.openLoginModal();
window.closeLoginModal = () => App.closeLoginModal();
window.toggleMobileNav = () => App.toggleMobileNav();
window.closeMobileNav = () => App.closeMobileNav();

document.addEventListener('DOMContentLoaded', () => {
    App.init();
});
