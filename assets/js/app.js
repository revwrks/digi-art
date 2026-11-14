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

    async checkAuthStatus() {
        try {
            const res = await fetch('api/auth.php?action=status');
            const data = await res.json();
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
            const res = await fetch('api/auth.php?action=login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, password })
            });
            const data = await res.json();

            if (data.success) {
                window.currentUser = data.user;
                this.closeLoginModal();
                this.showToast(`Selamat datang, ${data.user.full_name}!`);
                setTimeout(() => window.location.reload(), 500);
            } else {
                if (errBox) {
                    errBox.textContent = data.message;
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
            await fetch('api/auth.php?action=logout');
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
            const res = await fetch('api/auth.php?action=update_profile', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    username: newUsername,
                    new_password: newPassword || null,
                    current_password: currentPassword || null
                })
            });
            const data = await res.json();

            if (data.success) {
                this.showToast('✓ Profil dan keamanan berhasil diperbarui!');
                this.closeProfileModal();
                setTimeout(() => window.location.reload(), 600);
            } else {
                if (errBox) {
                    errBox.textContent = data.message;
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
            if (toggle) toggle.classList.add('active');
            if (overlay) overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    },

    closeMobileNav() {
        const drawer = document.getElementById('mobileNavDrawer');
        const toggle = document.getElementById('mobileNavToggle');
        const overlay = document.getElementById('mobileNavOverlay');
        if (drawer) drawer.classList.remove('open');
        if (toggle) toggle.classList.remove('active');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    },

    escapeHTML(str) {
        return String(str).replace(/[&<>"']/g, m => ({
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
