/**
 * Studio Digital (Ibis Paint & Procreate Inspired Digital Art Engine)
 * Fitur Utama:
 * 1. Flying Bar (Floating Icon Toolbar) & Flyout Popovers (Color, Brush, Layers)
 * 2. Kanvas Zoom In / Zoom Out (Pinch-to-zoom di HP, Wheel, Tombol +/−/Fit)
 * 3. Multi-Layer System dengan Reordering (Geser Naik-Turun ⬆/⬇ & Drag & Drop)
 * 4. Autosave Otomatis (Anti-refresh, draf tersimpan di localStorage, pemulihan instan)
 * 5. Variasi Kuas (Simple Pen, Sketch Pencil, Soft Brush), Bucket Fill & Text Tool
 */

class StudioEngine {
    constructor(canvasContainerId, width = 960, height = 600, options = {}) {
        this.container = document.getElementById(canvasContainerId);
        this.width = width;
        this.height = height;

        // Wrapper Transform untuk Zoom & Pan
        this.transformWrapper = document.getElementById('canvasTransformWrapper');
        if (!this.transformWrapper) {
            this.transformWrapper = document.createElement('div');
            this.transformWrapper.id = 'canvasTransformWrapper';
            this.transformWrapper.className = 'canvas-transform-wrapper';
            this.container.appendChild(this.transformWrapper);
        }

        // Display Canvas (Layar komposit gabungan layer)
        this.displayCanvas = document.getElementById('studioDisplayCanvas');
        if (!this.displayCanvas) {
            this.displayCanvas = document.createElement('canvas');
            this.displayCanvas.id = 'studioDisplayCanvas';
            this.transformWrapper.appendChild(this.displayCanvas);
        }
        this.displayCanvas.width = this.width;
        this.displayCanvas.height = this.height;
        this.displayCtx = this.displayCanvas.getContext('2d');

        // State Zoom & Pan
        this.scale = 1.0;
        this.panX = 0;
        this.panY = 0;
        this.minScale = 0.25;
        this.maxScale = 4.0;
        this.initialDistance = 0;
        this.initialScale = 1.0;
        this.isPinching = false;
        this.lastTouchMidpoint = null;

        // Scratchpad Canvas untuk Single Continuous Stroke (Menghilangkan titik-titik tumpang tindih saat opacity rendah)
        this.strokeCanvas = document.createElement('canvas');
        this.strokeCanvas.width = this.width;
        this.strokeCanvas.height = this.height;
        this.strokeCtx = this.strokeCanvas.getContext('2d');

        // Profil Pengaturan Masing-Masing Kuas (Independen untuk Setiap Kuas)
        const defaultProfiles = {
            'brush-simple': { size: 6, opacity: 1.0, stabilizer: 4, name: 'Simple Pen' },
            'brush-sketch': { size: 4, opacity: 0.85, stabilizer: 2, name: 'Sketch Pencil' },
            'brush-soft': { size: 18, opacity: 0.45, stabilizer: 5, name: 'Soft Brush (Cat Air)' },
            'brush-airbrush': { size: 32, opacity: 0.3, stabilizer: 4, name: 'Airbrush (Gradasi)' },
            'eraser': { size: 24, opacity: 1.0, stabilizer: 0, name: 'Penghapus (Eraser)' }
        };

        let savedProfiles = null;
        try {
            savedProfiles = JSON.parse(localStorage.getItem('digiart_brush_profiles'));
        } catch (e) {}

        this.brushProfiles = Object.assign({}, defaultProfiles, savedProfiles || {});

        // State Alat
        this.tool = 'brush-simple';
        const initProf = this.brushProfiles['brush-simple'];
        this.brushSize = initProf.size;
        this.brushOpacity = initProf.opacity;
        this.stabilizer = initProf.stabilizer;
        this.color = '#1f4be0';
        this.isDrawing = false;
        this.lastX = 0;
        this.lastY = 0;

        // Stabilizer Goresan Jari (Lazy Smoothing untuk Jari Tanpa Stylus)
        this.smoothX = 0;
        this.smoothY = 0;
        this.lastSmoothX = 0;
        this.lastSmoothY = 0;

        // Movable Text State & Riwayat Teks yang Ditempatkan (Presisi 1:1)
        this.activeMovableText = null;
        this.textObjects = [];
        this.textSpawnX = null;
        this.textSpawnY = null;
        this.isDraggingText = false;
        this.textDragStartPointerX = 0;
        this.textDragStartPointerY = 0;
        this.textDragInitialX = 0;
        this.textDragInitialY = 0;

        // Move Layer Tool State (Geser Isi Layer di Kanvas)
        this.isMovingLayer = false;
        this.moveLayerSnapshot = null;
        this.moveStartPointerX = 0;
        this.moveStartPointerY = 0;

        // Shape Tool State (Lingkaran, Kotak, Segitiga, Panah, Garis, Bintang, Hati)
        this.currentShape = 'circle';
        this.shapeStyle = 'stroke'; // 'stroke' | 'fill' | 'both'
        this.shapeStartX = 0;
        this.shapeStartY = 0;

        // Grid Bantu Komposisi (0 = Off, 1 = Rule of Thirds 3x3, 2 = Grid 8x8)
        this.gridMode = 0;

        // Layer System
        this.layers = [];
        this.activeLayerIndex = 0;
        this.layerCounter = 1;

        // Undo & Redo History
        this.history = [];
        this.future = [];
        this.maxHistory = 20;

        // Identitas Pengguna & Opsi Draf Cloud (Sync Per-Akun)
        this.user = options.user || window.currentUser || null;
        this.userId = this.user ? (this.user.id || this.user.username) : 0;
        this.userName = this.user ? (this.user.full_name || this.user.username) : 'Tamu';
        this.serverDraft = options.serverDraft || null;

        // Kunci autosave terisolasi per akun pengguna (bukan global per perangkat)
        this.autosaveStorageKey = this.userId 
            ? `digiart_studio_autosave_user_${this.userId}` 
            : 'digiart_studio_autosave_guest';

        // Bersihkan kunci draf global lama agar tidak bocor ke akun lain di perangkat yang sama
        try {
            if (localStorage.getItem('digiart_studio_autosave_v1')) {
                localStorage.removeItem('digiart_studio_autosave_v1');
            }
        } catch (e) {}

        // Autosave Timer Debounce
        this.autosaveTimer = null;
        this.isSyncingServer = false;

        // Inisialisasi: Coba pulihkan dari autosave akun (Cloud / Local), bila tidak ada baru buat layer kosong
        const restored = this.loadFromAutosave();
        if (!restored) {
            // Layer Pertama (Latar Putih)
            this.addLayer('Background (Putih)', true);
            // Layer Gambar Awal
            this.addLayer('Layer 1');
            this.renderComposite();
            this.saveState();
        }

        this.bindEvents();
        this.autoFitToScreen();

        // Auto-fit responsif pada resize layar, rotasi landscape HP, dan fullscreen
        window.addEventListener('resize', () => {
            clearTimeout(this._resizeTimer);
            this._resizeTimer = setTimeout(() => this.autoFitToScreen(), 150);
        });
        window.addEventListener('orientationchange', () => {
            setTimeout(() => this.autoFitToScreen(), 250);
        });
        document.addEventListener('fullscreenchange', () => {
            setTimeout(() => this.autoFitToScreen(), 200);
        });
        document.addEventListener('webkitfullscreenchange', () => {
            setTimeout(() => this.autoFitToScreen(), 200);
        });
    }

    /**
     * Hitung skala awal agar kanvas muat pas di layar perangkat (terutama layar HP)
     */
    autoFitToScreen() {
        const zone = this.container.closest('.studio-canvas-zone') || this.container;
        const availW = (zone.clientWidth || window.innerWidth) - 32;
        const availH = (zone.clientHeight || window.innerHeight) - 32;

        if (availW > 0 && availH > 0) {
            const fitScale = Math.min(availW / this.width, availH / this.height, 1.0);
            this.scale = Math.max(this.minScale, Math.min(1.0, fitScale));
            this.panX = 0;
            this.panY = 0;
            this.applyTransform();
        }
    }

    /* =========================================================
       ZOOM & PAN KANVAS
       ========================================================= */
    applyTransform() {
        if (!this.transformWrapper) return;
        this.transformWrapper.style.transform = `translate(${this.panX}px, ${this.panY}px) scale(${this.scale})`;
        this.transformWrapper.style.transformOrigin = 'center center';

        const display = document.getElementById('zoomLevelDisplay');
        if (display) {
            display.textContent = `${Math.round(this.scale * 100)}%`;
        }
    }

    zoomIn() {
        this.setZoom(this.scale * 1.25);
    }

    zoomOut() {
        this.setZoom(this.scale / 1.25);
    }

    setZoom(newScale) {
        this.scale = Math.max(this.minScale, Math.min(this.maxScale, newScale));
        this.applyTransform();
    }

    resetZoom() {
        this.autoFitToScreen();
    }

    /* =========================================================
       AUTOSAVE OTOMATIS (CLOUD & LOCALSTORAGE PER-AKUN PENGGUNA)
       ========================================================= */
    triggerAutosave() {
        this.updateAutosaveUI('saving');
        clearTimeout(this.autosaveTimer);
        this.autosaveTimer = setTimeout(() => {
            this.saveToAutosave();
        }, 800);
    }

    saveToAutosave() {
        try {
            const snapshot = {
                timestamp: Date.now(),
                userId: this.userId,
                username: this.user ? this.user.username : null,
                width: this.width,
                height: this.height,
                activeLayerIndex: this.activeLayerIndex,
                tool: this.tool,
                color: this.color,
                brushSize: this.brushSize,
                brushOpacity: this.brushOpacity,
                layers: this.layers.map(l => {
                    let dataUrl = '';
                    try { dataUrl = l.canvas.toDataURL('image/png'); } catch (e) {}
                    return {
                        name: l.name,
                        visible: l.visible,
                        opacity: l.opacity,
                        isReference: !!l.isReference || (typeof l.name === 'string' && (l.name.startsWith('Ref:') || l.name.toLowerCase().includes('panduan tracing') || l.name.toLowerCase().includes('referensi'))),
                        dataUrl
                    };
                })
            };

            const jsonStr = JSON.stringify(snapshot);

            // 1. Simpan di LocalStorage Akun (Instant offline / anti-refresh di perangkat aktif)
            localStorage.setItem(this.autosaveStorageKey, jsonStr);
            this.updateAutosaveUI('saved');

            // 2. Sinkronkan ke Cloud Server Database per akun (Bukan per perangkat)
            if (this.userId) {
                this.saveToServerDraft(jsonStr);
            }
        } catch (e) {
            console.warn('Autosave quota reached or disabled:', e);
            this.updateAutosaveUI('error');
        }
    }

    getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    async apiPost(url, rawBody = '{}') {
        const headers = { 'Content-Type': 'application/json' };
        if (window.App && typeof window.App.apiFetch === 'function') {
            return window.App.apiFetch(url, {
                method: 'POST',
                headers,
                body: rawBody
            });
        }
        const csrf = this.getCsrfToken();
        if (csrf) {
            headers['X-CSRF-Token'] = csrf;
        }
        return fetch(url, {
            method: 'POST',
            headers,
            body: rawBody
        });
    }

    async saveToServerDraft(jsonStr) {
        if (!this.userId || this.isSyncingServer) return;
        this.isSyncingServer = true;
        try {
            const res = await this.apiPost('api/studio.php?action=save_draft', jsonStr);
            if (res.ok) {
                const data = await res.json();
                if (data.success) {
                    this.updateAutosaveUI('synced');
                }
            }
        } catch (err) {
            console.warn('Sync cloud draf tertunda:', err);
        } finally {
            this.isSyncingServer = false;
        }
    }

    loadFromAutosave() {
        try {
            let localData = null;
            const raw = localStorage.getItem(this.autosaveStorageKey);
            if (raw) {
                try {
                    const parsed = JSON.parse(raw);
                    // Validasi: hanya muat jika draf lokal memang terikat ke akun ini
                    if (parsed && (!parsed.userId || String(parsed.userId) === String(this.userId))) {
                        localData = parsed;
                    }
                } catch (e) {
                    localData = null;
                }
            }

            // Ambil serverDraft yang di-inject dari database via PHP
            let serverData = this.serverDraft || null;

            // Tentukan sumber data terbaik (Local vs Server Cloud)
            let dataToLoad = null;
            if (localData && serverData) {
                const localTime = Number(localData.timestamp) || 0;
                let serverTime = Number(serverData.timestamp) || 0;
                if (!serverTime && serverData.updated_at) {
                    serverTime = new Date(serverData.updated_at).getTime();
                }

                // Jika draf di server lebih baru daripada di perangkat ini (misal siswa baru menggambar di HP/perangkat lain)
                if (serverTime > localTime + 2000) {
                    dataToLoad = serverData;
                } else {
                    dataToLoad = localData;
                }
            } else {
                dataToLoad = localData || serverData;
            }

            if (!dataToLoad || !Array.isArray(dataToLoad.layers) || dataToLoad.layers.length === 0) {
                return false;
            }

            this.restoreCanvasFromData(dataToLoad);
            return true;
        } catch (e) {
            console.warn('Gagal memuat autosave akun:', e);
            this.layers = [];
            return false;
        }
    }

    restoreCanvasFromData(data) {
        this.layers = [];
        let loadedCount = 0;

        data.layers.forEach((lData, idx) => {
            const canvas = document.createElement('canvas');
            canvas.width = this.width;
            canvas.height = this.height;
            const ctx = canvas.getContext('2d');

            const img = new Image();
            img.onload = () => {
                ctx.drawImage(img, 0, 0);
                loadedCount++;
                if (loadedCount === data.layers.length) {
                    this.renderComposite();
                    this.renderLayersUI();
                }
            };
            img.src = lData.dataUrl;

            this.layers.push({
                id: 'layer_auto_' + idx + '_' + Date.now(),
                name: lData.name || `Layer ${idx + 1}`,
                canvas,
                ctx,
                visible: lData.visible !== false,
                opacity: typeof lData.opacity === 'number' ? lData.opacity : 1.0,
                isReference: !!lData.isReference || (typeof lData.name === 'string' && (lData.name.startsWith('Ref:') || lData.name.toLowerCase().includes('panduan tracing') || lData.name.toLowerCase().includes('referensi')))
            });
        });

        this.activeLayerIndex = Math.min(data.activeLayerIndex || 0, this.layers.length - 1);
        if (data.color) this.color = data.color;
        if (data.brushSize) this.brushSize = data.brushSize;
        if (data.brushOpacity) this.brushOpacity = data.brushOpacity;
        if (data.tool) this.tool = data.tool;

        this.renderComposite();
        this.renderLayersUI();
        this.saveState();
        this.updateAutosaveUI('saved');

        setTimeout(() => {
            const who = this.userName !== 'Tamu' ? `Akun ${this.userName}` : 'Sesi Anda';
            window.showToast?.(`Draf gambaran untuk ${who} berhasil dipulihkan.`);
        }, 300);
    }

    async clearAutosave() {
        localStorage.removeItem(this.autosaveStorageKey);
        this.serverDraft = null;
        this.updateAutosaveUI('cleared');

        // Hapus draf di cloud database akun
        if (this.userId) {
            try {
                await this.apiPost('api/studio.php?action=clear_draft', '{}');
            } catch (e) {
                console.warn('Gagal menghapus draf cloud akun:', e);
            }
        }
    }

    updateAutosaveUI(status) {
        const badge = document.getElementById('autosaveStatus');
        if (!badge) return;

        const nameShort = this.escapeHTML(this.userName ? (this.userName.split(' ')[0]) : 'Akun');

        if (status === 'saving') {
            badge.className = 'autosave-pill saving';
            badge.innerHTML = `<span class="dot-indicator"></span> Menyimpan draf ${nameShort}...`;
        } else if (status === 'synced') {
            const now = new Date();
            const timeStr = this.escapeHTML(now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }));
            badge.className = 'autosave-pill';
            badge.innerHTML = `<span class="dot-indicator"></span> Draf Akun: ${nameShort} (Cloud ${timeStr})`;
        } else if (status === 'saved') {
            const now = new Date();
            const timeStr = this.escapeHTML(now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }));
            badge.className = 'autosave-pill';
            badge.innerHTML = `<span class="dot-indicator"></span> Draf Akun: ${nameShort} (${timeStr})`;
        } else if (status === 'error') {
            badge.className = 'autosave-pill saving';
            badge.innerHTML = '<span class="dot-indicator" style="background:#ef4444"></span> Gagal simpan draf';
        } else {
            badge.className = 'autosave-pill';
            badge.innerHTML = `<span class="dot-indicator"></span> Lembar Baru (${nameShort})`;
        }
    }

    newCanvas(confirmFirst = false) {
        if (confirmFirst && !confirm('Mulai lembar kanvas baru? Karya dan coretan saat ini yang belum diunduh akan dibersihkan.')) {
            return;
        }
        this.clearAutosave();
        this.layers = [];
        this.history = [];
        this.future = [];
        this.layerCounter = 1;
        this.activeMovableText = null;
        document.getElementById('movableTextToolbar')?.remove();

        // 1. Layer Latar Putih Bersih
        this.addLayer('Background (Putih)', true);
        // 2. Layer Gambar Utama (Transparan)
        this.addLayer('Layer 1', false);
        this.activeLayerIndex = 1;

        this.renderComposite();
        this.renderLayersUI();
        this.saveState();
        this.resetZoom();
        this.updateAutosaveUI('cleared');
        window.showToast?.('Lembar kanvas baru siap digunakan.');
    }

    /* =========================================================
       LAYER MANAGEMENT & REORDERING (NAIK/TURUN/DRAG)
       ========================================================= */
    addLayer(name = null, fillWhite = false) {
        const id = 'layer_' + Date.now() + '_' + Math.random().toString(36).substr(2, 4);
        const canvas = document.createElement('canvas');
        canvas.width = this.width;
        canvas.height = this.height;
        const ctx = canvas.getContext('2d');

        if (fillWhite) {
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, this.width, this.height);
        }

        const layerName = name || `Layer ${this.layerCounter++}`;
        const layer = {
            id,
            name: layerName,
            canvas,
            ctx,
            visible: true,
            opacity: 1.0
        };

        this.layers.push(layer);
        this.activeLayerIndex = this.layers.length - 1;
        this.renderLayersUI();
        this.renderComposite();
        this.triggerAutosave();
        return layer;
    }

    deleteActiveLayer() {
        this.deleteLayerByIndex(this.activeLayerIndex);
    }

    /**
     * Hapus layer berdasarkan indeks spesifik
     */
    deleteLayerByIndex(index) {
        if (this.layers.length <= 1) {
            window.showToast?.('Minimal harus ada 1 layer aktif di kanvas.');
            return;
        }
        const name = this.layers[index]?.name || 'Layer';
        this.layers.splice(index, 1);
        if (this.activeLayerIndex >= this.layers.length) {
            this.activeLayerIndex = this.layers.length - 1;
        } else if (this.activeLayerIndex > index) {
            this.activeLayerIndex--;
        }
        this.renderLayersUI();
        this.renderComposite();
        this.saveState();
        this.triggerAutosave();
        window.showToast?.(`Layer "${name}" berhasil dihapus.`);
    }

    /**
     * Aktifkan mode Geser Layer (Move Tool) untuk layer tertentu
     */
    activateMoveLayerTool(index = null) {
        if (index !== null && index >= 0 && index < this.layers.length) {
            this.setActiveLayer(index);
        }
        this.tool = 'move';
        window.setStudioTool?.('move');
        window.closeAllFlyouts?.();
        const activeName = this.getActiveLayer()?.name || 'Layer';
        window.showToast?.(`Mode Geser Aktif: Seret di kanvas atau gunakan tombol panah untuk memindahkan isi "${activeName}".`);
    }

    /**
     * Geser isi layer aktif dengan tombol keyboard (Fine-tuning Nudge)
     */
    nudgeActiveLayer(deltaX, deltaY) {
        const layer = this.getActiveLayer();
        if (!layer) return;
        const temp = document.createElement('canvas');
        temp.width = this.width;
        temp.height = this.height;
        const tCtx = temp.getContext('2d');
        tCtx.drawImage(layer.canvas, 0, 0);

        layer.ctx.clearRect(0, 0, this.width, this.height);
        layer.ctx.drawImage(temp, deltaX, deltaY);

        this.renderComposite();
        this.renderLayersUI();
        this.saveState();
        this.triggerAutosave();
    }

    mergeDownActiveLayer() {
        if (this.activeLayerIndex <= 0) {
            window.showToast?.('Tidak dapat menggabungkan layer terbawah.');
            return;
        }
        const topLayer = this.layers[this.activeLayerIndex];
        const bottomLayer = this.layers[this.activeLayerIndex - 1];

        bottomLayer.ctx.save();
        bottomLayer.ctx.globalAlpha = topLayer.opacity;
        bottomLayer.ctx.drawImage(topLayer.canvas, 0, 0);
        bottomLayer.ctx.restore();

        this.layers.splice(this.activeLayerIndex, 1);
        this.activeLayerIndex--;
        this.renderLayersUI();
        this.renderComposite();
        this.saveState();
        this.triggerAutosave();
        window.showToast?.('Layer berhasil digabungkan ke bawah.');
    }

    /**
     * Geser Layer Naik (Ke arah atas tumpukan z-index)
     */
    moveLayerUp(index) {
        if (index >= this.layers.length - 1) return;
        const temp = this.layers[index];
        this.layers[index] = this.layers[index + 1];
        this.layers[index + 1] = temp;

        if (this.activeLayerIndex === index) {
            this.activeLayerIndex = index + 1;
        } else if (this.activeLayerIndex === index + 1) {
            this.activeLayerIndex = index;
        }

        this.renderLayersUI();
        this.renderComposite();
        this.saveState();
        this.triggerAutosave();
    }

    /**
     * Geser Layer Turun (Ke arah bawah tumpukan z-index)
     */
    moveLayerDown(index) {
        if (index <= 0) return;
        const temp = this.layers[index];
        this.layers[index] = this.layers[index - 1];
        this.layers[index - 1] = temp;

        if (this.activeLayerIndex === index) {
            this.activeLayerIndex = index - 1;
        } else if (this.activeLayerIndex === index - 1) {
            this.activeLayerIndex = index;
        }

        this.renderLayersUI();
        this.renderComposite();
        this.saveState();
        this.triggerAutosave();
    }

    /**
     * Reorder layer dari Drag & Drop
     */
    reorderLayers(fromIndex, toIndex) {
        if (fromIndex === toIndex || fromIndex < 0 || toIndex < 0 || fromIndex >= this.layers.length || toIndex >= this.layers.length) return;
        const [moved] = this.layers.splice(fromIndex, 1);
        this.layers.splice(toIndex, 0, moved);
        this.activeLayerIndex = toIndex;

        this.renderLayersUI();
        this.renderComposite();
        this.saveState();
        this.triggerAutosave();
    }

    getActiveLayer() {
        return this.layers[this.activeLayerIndex] || this.layers[0];
    }

    renderComposite() {
        this.displayCtx.clearRect(0, 0, this.width, this.height);
        for (let idx = 0; idx < this.layers.length; idx++) {
            const layer = this.layers[idx];
            if (layer.visible) {
                this.displayCtx.save();
                this.displayCtx.globalAlpha = layer.opacity;
                this.displayCtx.drawImage(layer.canvas, 0, 0);

                // Jika sedang menggambar di layer aktif, tampilkan live preview goresan kuas dengan opacity yang dipilih
                if (idx === this.activeLayerIndex && this.isDrawing) {
                    if (this.tool === 'eraser') {
                        this.displayCtx.globalCompositeOperation = 'destination-out';
                        this.displayCtx.globalAlpha = this.brushOpacity;
                    } else {
                        this.displayCtx.globalCompositeOperation = 'source-over';
                        this.displayCtx.globalAlpha = this.brushOpacity * layer.opacity;
                    }
                    this.displayCtx.drawImage(this.strokeCanvas, 0, 0);
                }

                this.displayCtx.restore();
            }
        }

        // Gambar live preview teks interaktif 1:1 tepat di atas kanvas
        if (this.activeMovableText) {
            this.drawTextPreviewOverlay(this.displayCtx);
        }

        // Gambar grid bantu komposisi seni rupa jika diaktifkan (tidak diekspor ke file karya)
        if (this.gridMode > 0) {
            this.drawCompositionGrid(this.displayCtx);
        }
    }

    renderLayersUI() {
        const container = document.getElementById('layersList');
        const badge = document.getElementById('flyLayerBadge');
        if (badge) {
            badge.textContent = this.layers.length;
        }

        if (!container) return;

        // Render layer teratas di bagian atas daftar (reverse visual)
        const total = this.layers.length;
        const iconEye = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
        const iconEyeOff = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
        const iconMove = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="5 9 2 12 5 15"/><polyline points="9 5 12 2 15 5"/><polyline points="15 19 12 22 9 19"/><polyline points="19 9 22 12 19 15"/><line x1="2" y1="12" x2="22" y2="12"/><line x1="12" y1="2" x2="12" y2="22"/></svg>';
        const iconUp = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="18 15 12 9 6 15"/></svg>';
        const iconDown = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>';
        const iconTrash = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>';
        const iconImage = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>';

        container.innerHTML = this.layers.map((layer, idx) => `
            <div class="layer-item ${idx === this.activeLayerIndex ? 'active' : ''}" 
                 draggable="true" 
                 data-index="${idx}"
                 onclick="studio.setActiveLayer(${idx})">
                <button class="layer-vis-btn" onclick="event.stopPropagation(); studio.toggleLayerVisibility(${idx})" title="Sembunyikan/Tampilkan" aria-label="Sembunyikan atau tampilkan layer">
                    ${layer.visible ? iconEye : iconEyeOff}
                </button>
                <div class="layer-info">
                    <span class="layer-name" title="${this.escapeHTML(layer.name)}">${this.escapeHTML(layer.name)}</span>
                    <div style="display:flex;align-items:center;gap:4px;flex-wrap:wrap">
                        <small>Opacity: ${Math.round(layer.opacity * 100)}%</small>
                        ${layer.isReference ? '<span class="badge-layer-ref" title="Lapisan ini dari referensi internet dan tidak akan diikutsertakan saat mengirim ke Pameran Karya Siswa" style="background:#fef3c7;color:#92400e;font-size:9px;font-weight:700;padding:1px 5px;border-radius:4px;border:1px solid #fde68a">Ref (Anti-Plagiasi)</span>' : ''}
                    </div>
                </div>
                <div class="layer-reorder-btns" onclick="event.stopPropagation()">
                    <button class="btn-reorder btn-layer-move" onclick="studio.activateMoveLayerTool(${idx})" title="Geser Isi Layer di Kanvas" aria-label="Geser Isi Layer">${iconMove}</button>
                    <button class="btn-reorder" onclick="studio.moveLayerUp(${idx})" ${idx === total - 1 ? 'disabled' : ''} title="Geser Urutan ke Atas" aria-label="Naikkan Layer">${iconUp}</button>
                    <button class="btn-reorder" onclick="studio.moveLayerDown(${idx})" ${idx === 0 ? 'disabled' : ''} title="Geser Urutan ke Bawah" aria-label="Turunkan Layer">${iconDown}</button>
                    <button class="btn-reorder btn-layer-del" onclick="studio.deleteLayerByIndex(${idx})" title="Hapus Layer Ini" aria-label="Hapus Layer">${iconTrash}</button>
                </div>
                <div class="layer-preview">
                    ${(() => {
                        let previewSrc = '';
                        try { previewSrc = layer.canvas.toDataURL('image/png'); } catch (e) {}
                        return previewSrc ? `<img src="${previewSrc}" alt="Preview" width="34" height="24">` : iconImage;
                    })()}
                </div>
            </div>
        `).reverse().join('');

        // Pasang drag-and-drop listener pada elemen layer
        this.bindLayerDragEvents(container);

        const opacityInput = document.getElementById('layerOpacity');
        if (opacityInput && this.getActiveLayer()) {
            opacityInput.value = Math.round(this.getActiveLayer().opacity * 100);
        }
    }

    bindLayerDragEvents(container) {
        let draggedIdx = null;

        container.querySelectorAll('.layer-item').forEach(item => {
            item.addEventListener('dragstart', (e) => {
                draggedIdx = parseInt(item.getAttribute('data-index'), 10);
                item.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });

            item.addEventListener('dragend', () => {
                item.classList.remove('dragging');
            });

            item.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
            });

            item.addEventListener('drop', (e) => {
                e.preventDefault();
                const targetIdx = parseInt(item.getAttribute('data-index'), 10);
                if (draggedIdx !== null && targetIdx !== null && draggedIdx !== targetIdx) {
                    this.reorderLayers(draggedIdx, targetIdx);
                }
            });
        });
    }

    setActiveLayer(index) {
        if (index >= 0 && index < this.layers.length) {
            this.activeLayerIndex = index;
            this.renderLayersUI();
        }
    }

    toggleLayerVisibility(index) {
        if (this.layers[index]) {
            this.layers[index].visible = !this.layers[index].visible;
            this.renderLayersUI();
            this.renderComposite();
            this.triggerAutosave();
        }
    }

    setLayerOpacity(valPercent) {
        const layer = this.getActiveLayer();
        if (layer) {
            layer.opacity = Math.max(0, Math.min(1, valPercent / 100));
            this.renderComposite();
            this.triggerAutosave();
        }
    }

    /* =========================================================
       EVENT LISTENERS (GAMBAR, PINCH ZOOM, PAN & WHEEL)
       ========================================================= */
    bindEvents() {
        const c = this.displayCanvas;
        const zone = this.container.closest('.studio-canvas-zone') || this.container;

        const getPos = (e) => {
            const rect = c.getBoundingClientRect();
            const clientX = (e.touches && e.touches.length > 0) ? e.touches[0].clientX : (e.clientX || 0);
            const clientY = (e.touches && e.touches.length > 0) ? e.touches[0].clientY : (e.clientY || 0);
            return {
                x: (clientX - rect.left) * (this.width / rect.width),
                y: (clientY - rect.top) * (this.height / rect.height)
            };
        };

        const onStart = (e) => {
            // Cek jika multi-touch (Pinch to Zoom / Two-finger Pan)
            if (e.touches && e.touches.length >= 2) {
                this.isDrawing = false;
                this.isPinching = true;
                const t1 = e.touches[0];
                const t2 = e.touches[1];
                this.initialDistance = Math.hypot(t2.clientX - t1.clientX, t2.clientY - t1.clientY);
                this.initialScale = this.scale;
                this.lastTouchMidpoint = {
                    x: (t1.clientX + t2.clientX) / 2,
                    y: (t1.clientY + t2.clientY) / 2
                };
                return;
            }

            e.preventDefault();
            const pos = getPos(e);

            // 1. Cek jika klik pada Teks Aktif untuk mulai menggeser
            if (this.activeMovableText) {
                const pad = 12;
                const bx = this.activeMovableText.canvasX - pad;
                const by = this.activeMovableText.canvasY - pad;
                const bw = this.activeMovableText.width + pad * 2;
                const bh = this.activeMovableText.height + pad * 2;

                if (pos.x >= bx && pos.x <= bx + bw && pos.y >= by && pos.y <= by + bh) {
                    this.isDraggingText = true;
                    this.textDragStartPointerX = pos.x;
                    this.textDragStartPointerY = pos.y;
                    this.textDragInitialX = this.activeMovableText.canvasX;
                    this.textDragInitialY = this.activeMovableText.canvasY;
                    this.isDrawing = false;
                    return;
                }
            }

            // 2. Mode Geser Layer (Move Tool)
            if (this.tool === 'move') {
                const activeLayer = this.getActiveLayer();
                if (!activeLayer) return;
                this.isMovingLayer = true;
                this.moveStartPointerX = pos.x;
                this.moveStartPointerY = pos.y;
                this.moveLayerSnapshot = document.createElement('canvas');
                this.moveLayerSnapshot.width = this.width;
                this.moveLayerSnapshot.height = this.height;
                const sCtx = this.moveLayerSnapshot.getContext('2d');
                sCtx.drawImage(activeLayer.canvas, 0, 0);
                this.isDrawing = false;
                return;
            }

            this.isDrawing = true;
            this.lastX = pos.x;
            this.lastY = pos.y;
            this.smoothX = pos.x;
            this.smoothY = pos.y;
            this.lastSmoothX = pos.x;
            this.lastSmoothY = pos.y;

            // Bersihkan scratchpad canvas sebelum memulai goresan baru
            this.strokeCtx.clearRect(0, 0, this.width, this.height);

            if (this.tool === 'shape') {
                this.shapeStartX = pos.x;
                this.shapeStartY = pos.y;
                return;
            }

            if (this.tool === 'bucket') {
                this.floodFill(Math.round(pos.x), Math.round(pos.y), this.color);
                this.isDrawing = false;
                this.saveState();
                this.triggerAutosave();
                return;
            }

            if (this.tool === 'text') {
                this.isDrawing = false;
                // Simpan koordinat tempat klik pengguna secara presisi 1:1
                this.textSpawnX = Math.round(pos.x);
                this.textSpawnY = Math.round(pos.y);
                window.openTextModal?.();
                return;
            }

            if (this.tool.startsWith('brush') || this.tool === 'eraser') {
                this.drawStroke(pos.x, pos.y, true, pos.x, pos.y);
            }
        };

        const onMove = (e) => {
            // Multi-touch Pinch to Zoom / Pan
            if (e.touches && e.touches.length >= 2) {
                e.preventDefault();
                const t1 = e.touches[0];
                const t2 = e.touches[1];
                const dist = Math.hypot(t2.clientX - t1.clientX, t2.clientY - t1.clientY);

                if (this.initialDistance > 0) {
                    const factor = dist / this.initialDistance;
                    this.scale = Math.max(this.minScale, Math.min(this.maxScale, this.initialScale * factor));
                }

                // Two-finger Pan
                const midX = (t1.clientX + t2.clientX) / 2;
                const midY = (t1.clientY + t2.clientY) / 2;
                if (this.lastTouchMidpoint) {
                    this.panX += (midX - this.lastTouchMidpoint.x);
                    this.panY += (midY - this.lastTouchMidpoint.y);
                }
                this.lastTouchMidpoint = { x: midX, y: midY };

                this.applyTransform();
                return;
            }

            const pos = getPos(e);

            // Geser Teks Aktif di Kanvas (Presisi 1:1)
            if (this.isDraggingText && this.activeMovableText) {
                e.preventDefault();
                const deltaX = Math.round(pos.x - this.textDragStartPointerX);
                const deltaY = Math.round(pos.y - this.textDragStartPointerY);
                this.activeMovableText.canvasX = this.textDragInitialX + deltaX;
                this.activeMovableText.canvasY = this.textDragInitialY + deltaY;
                this.renderComposite();
                this.updateMovableToolbarPosition();
                return;
            }

            // Geser Isi Layer Aktif di Kanvas (Move Tool)
            if (this.isMovingLayer && this.moveLayerSnapshot) {
                e.preventDefault();
                const deltaX = Math.round(pos.x - this.moveStartPointerX);
                const deltaY = Math.round(pos.y - this.moveStartPointerY);
                const activeLayer = this.getActiveLayer();
                if (activeLayer) {
                    activeLayer.ctx.clearRect(0, 0, this.width, this.height);
                    activeLayer.ctx.drawImage(this.moveLayerSnapshot, deltaX, deltaY);
                    this.renderComposite();
                }
                return;
            }

            if (!this.isDrawing) return;
            e.preventDefault();

            if (this.tool === 'shape') {
                this.strokeCtx.clearRect(0, 0, this.width, this.height);
                this.drawShapePreview(this.shapeStartX, this.shapeStartY, pos.x, pos.y);
                this.renderComposite();
                return;
            }

            if (this.stabilizer > 0 && (this.tool.startsWith('brush') || this.tool === 'eraser')) {
                // Exponential Moving Average filter untuk melunakkan getaran jari di layar sentuh HP
                const weight = 1 / (1 + this.stabilizer * 0.65);
                this.smoothX += (pos.x - this.smoothX) * weight;
                this.smoothY += (pos.y - this.smoothY) * weight;

                this.drawStroke(this.smoothX, this.smoothY, false, this.lastSmoothX, this.lastSmoothY);
                this.lastSmoothX = this.smoothX;
                this.lastSmoothY = this.smoothY;
            } else {
                this.drawStroke(pos.x, pos.y, false, this.lastX, this.lastY);
            }

            this.lastX = pos.x;
            this.lastY = pos.y;
        };

        const onEnd = (e) => {
            if (e.touches && e.touches.length < 2) {
                this.isPinching = false;
                this.initialDistance = 0;
                this.lastTouchMidpoint = null;
            }

            if (this.isDraggingText) {
                this.isDraggingText = false;
            }

            if (this.isMovingLayer) {
                this.isMovingLayer = false;
                this.moveLayerSnapshot = null;
                this.saveState();
                this.triggerAutosave();
                this.renderLayersUI();
            }

            if (this.isDrawing) {
                if (this.stabilizer > 0 && (this.tool.startsWith('brush') || this.tool === 'eraser')) {
                    // Tarik garis final ke posisi ujung sentuhan
                    this.drawStroke(this.lastX, this.lastY, false, this.lastSmoothX, this.lastSmoothY);
                }
                this.isDrawing = false;

                // Panggang goresan dari scratchpad ke layer aktif secara permanen (BEBAS DARI TITIK-TITIK OVERLAP)
                const layer = this.getActiveLayer();
                if (layer && layer.visible) {
                    layer.ctx.save();
                    if (this.tool === 'eraser') {
                        layer.ctx.globalCompositeOperation = 'destination-out';
                        layer.ctx.globalAlpha = this.brushOpacity;
                    } else {
                        layer.ctx.globalCompositeOperation = 'source-over';
                        layer.ctx.globalAlpha = this.brushOpacity;
                    }
                    layer.ctx.drawImage(this.strokeCanvas, 0, 0);
                    layer.ctx.restore();
                }

                // Bersihkan scratchpad untuk goresan berikutnya
                this.strokeCtx.clearRect(0, 0, this.width, this.height);

                this.renderLayersUI();
                this.renderComposite();
                this.saveState();
                this.triggerAutosave();
            }
        };

        // Mouse Events
        c.addEventListener('mousedown', onStart);
        window.addEventListener('mousemove', onMove);
        window.addEventListener('mouseup', onEnd);

        // Touch Events
        c.addEventListener('touchstart', onStart, { passive: false });
        window.addEventListener('touchmove', onMove, { passive: false });
        window.addEventListener('touchend', onEnd);
        window.addEventListener('touchcancel', onEnd);

        // Mouse Wheel Zoom di Canvas Zone
        zone.addEventListener('wheel', (e) => {
            if (e.ctrlKey || e.metaKey || !this.isDrawing) {
                e.preventDefault();
                const delta = e.deltaY < 0 ? 1.15 : 0.87;
                this.setZoom(this.scale * delta);
            }
        }, { passive: false });

        // Keyboard Controls: Nudge posisi teks atau layer dengan tombol panah (Arrow Keys), Enter, dan Escape
        window.addEventListener('keydown', (e) => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;

            // 1. Teks Aktif: Geser 1:1 dengan panah, Enter untuk terapkan, Esc untuk batal
            if (this.activeMovableText) {
                const step = e.shiftKey ? 10 : 1;
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    this.activeMovableText.canvasX -= step;
                    this.renderComposite();
                    this.updateMovableToolbarPosition();
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    this.activeMovableText.canvasX += step;
                    this.renderComposite();
                    this.updateMovableToolbarPosition();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    this.activeMovableText.canvasY -= step;
                    this.renderComposite();
                    this.updateMovableToolbarPosition();
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    this.activeMovableText.canvasY += step;
                    this.renderComposite();
                    this.updateMovableToolbarPosition();
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    this.commitMovableText();
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    this.cancelMovableText();
                }
                return;
            }

            // 2. Mode Geser Layer: Nudge layer dengan panah
            if (this.tool === 'move') {
                const step = e.shiftKey ? 10 : 1;
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    this.nudgeActiveLayer(-step, 0);
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    this.nudgeActiveLayer(step, 0);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    this.nudgeActiveLayer(0, -step);
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    this.nudgeActiveLayer(0, step);
                }
            }
        });
    }

    setShape(shapeType) {
        this.currentShape = shapeType;
        this.tool = 'shape';
    }

    setShapeStyle(style) {
        this.shapeStyle = style;
    }

    /* =========================================================
       DRAW STROKES ON SCRATCHPAD CANVAS (BEBAS TITIK OVERLAP)
       ========================================================= */
    drawStroke(x, y, isStart = false, fromX = null, fromY = null) {
        const startPtX = fromX !== null ? fromX : (isStart ? x : this.lastX);
        const startPtY = fromY !== null ? fromY : (isStart ? y : this.lastY);
        const ctx = this.strokeCtx;

        if (this.tool === 'eraser') {
            ctx.save();
            ctx.globalCompositeOperation = 'source-over';
            ctx.strokeStyle = '#000000';
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.lineWidth = this.brushSize * 1.5;
            ctx.beginPath();
            ctx.moveTo(startPtX, startPtY);
            ctx.lineTo(x, y);
            ctx.stroke();
            ctx.restore();
        } else if (this.tool === 'brush-simple') {
            // Simple Pen: digambar solid 1.0 pada scratchpad agar sambungan lineCap tidak bertumpuk jadi titik gelap
            ctx.save();
            ctx.globalCompositeOperation = 'source-over';
            ctx.strokeStyle = this.color;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.lineWidth = this.brushSize;
            ctx.globalAlpha = 1.0;
            ctx.beginPath();
            ctx.moveTo(startPtX, startPtY);
            ctx.lineTo(x, y);
            ctx.stroke();
            ctx.restore();
        } else if (this.tool === 'brush-sketch') {
            // Sketch / Pencil: textured jitter strokes
            ctx.save();
            ctx.globalCompositeOperation = 'source-over';
            ctx.strokeStyle = this.color;
            ctx.lineCap = 'round';
            ctx.lineWidth = Math.max(1, this.brushSize * 0.7);

            for (let i = 0; i < 3; i++) {
                const ox = (Math.random() - 0.5) * (this.brushSize * 0.5);
                const oy = (Math.random() - 0.5) * (this.brushSize * 0.5);
                ctx.globalAlpha = 0.5 + Math.random() * 0.3;
                ctx.beginPath();
                ctx.moveTo(startPtX + ox, startPtY + oy);
                ctx.lineTo(x + ox, y + oy);
                ctx.stroke();
            }
            ctx.restore();
        } else if (this.tool === 'brush-soft') {
            // Soft Brush / Watercolor
            ctx.save();
            ctx.globalCompositeOperation = 'source-over';
            ctx.strokeStyle = this.color;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.lineWidth = this.brushSize * 1.5;
            ctx.globalAlpha = 0.85;
            ctx.shadowBlur = this.brushSize * 0.35;
            ctx.shadowColor = this.color;
            ctx.beginPath();
            ctx.moveTo(startPtX, startPtY);
            ctx.lineTo(x, y);
            ctx.stroke();
            ctx.restore();
        } else if (this.tool === 'brush-airbrush') {
            // Airbrush / Transparent Gradient Spray
            ctx.save();
            ctx.globalCompositeOperation = 'source-over';
            const dx = x - startPtX;
            const dy = y - startPtY;
            const dist = Math.hypot(dx, dy);
            const radius = Math.max(4, this.brushSize * 1.5);
            const step = Math.max(3, this.brushSize * 0.3);
            const count = isStart ? 1 : Math.max(1, Math.floor(dist / step));

            for (let i = 0; i <= count; i++) {
                const t = count === 0 ? 0 : i / count;
                const px = startPtX + dx * t;
                const py = startPtY + dy * t;
                const grad = ctx.createRadialGradient(px, py, 0, px, py, radius);
                grad.addColorStop(0, this.hexToRgba(this.color, 0.4));
                grad.addColorStop(0.4, this.hexToRgba(this.color, 0.12));
                grad.addColorStop(1, 'rgba(0,0,0,0)');

                ctx.fillStyle = grad;
                ctx.beginPath();
                ctx.arc(px, py, radius, 0, Math.PI * 2);
                ctx.fill();
            }
            ctx.restore();
        }

        this.renderComposite();
    }

    /* =========================================================
       SHAPE GEOMETRY DRAWING & PREVIEW
       ========================================================= */
    drawShapePreview(x0, y0, x1, y1) {
        const ctx = this.strokeCtx;
        ctx.save();
        ctx.lineWidth = Math.max(1, this.brushSize);
        ctx.strokeStyle = this.color;
        ctx.fillStyle = this.color;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';

        const minX = Math.min(x0, x1);
        const maxX = Math.max(x0, x1);
        const minY = Math.min(y0, y1);
        const maxY = Math.max(y0, y1);
        const w = Math.max(1, Math.abs(x1 - x0));
        const h = Math.max(1, Math.abs(y1 - y0));

        switch (this.currentShape) {
            case 'rectangle':
                ctx.beginPath();
                ctx.rect(minX, minY, w, h);
                this.applyShapeStyle(ctx);
                break;

            case 'circle':
                ctx.beginPath();
                const rx = w / 2;
                const ry = h / 2;
                const cx = minX + rx;
                const cy = minY + ry;
                ctx.ellipse(cx, cy, rx, ry, 0, 0, Math.PI * 2);
                this.applyShapeStyle(ctx);
                break;

            case 'triangle':
                ctx.beginPath();
                const topX = (minX + maxX) / 2;
                const topY = y1 >= y0 ? minY : maxY;
                const baseY = y1 >= y0 ? maxY : minY;
                ctx.moveTo(topX, topY);
                ctx.lineTo(maxX, baseY);
                ctx.lineTo(minX, baseY);
                ctx.closePath();
                this.applyShapeStyle(ctx);
                break;

            case 'arrow':
                const angle = Math.atan2(y1 - y0, x1 - x0);
                const dist = Math.hypot(x1 - x0, y1 - y0);
                const headLen = Math.max(12, Math.min(42, dist * 0.28, ctx.lineWidth * 3.5));

                ctx.beginPath();
                ctx.moveTo(x0, y0);
                ctx.lineTo(x1 - (headLen * 0.6) * Math.cos(angle), y1 - (headLen * 0.6) * Math.sin(angle));
                ctx.stroke();

                ctx.beginPath();
                ctx.moveTo(x1, y1);
                ctx.lineTo(x1 - headLen * Math.cos(angle - Math.PI / 6), y1 - headLen * Math.sin(angle - Math.PI / 6));
                ctx.lineTo(x1 - (headLen * 0.45) * Math.cos(angle), y1 - (headLen * 0.45) * Math.sin(angle));
                ctx.lineTo(x1 - headLen * Math.cos(angle + Math.PI / 6), y1 - headLen * Math.sin(angle + Math.PI / 6));
                ctx.closePath();
                ctx.fill();
                if (this.shapeStyle === 'both' || this.shapeStyle === 'stroke') {
                    ctx.stroke();
                }
                break;

            case 'line':
                ctx.beginPath();
                ctx.moveTo(x0, y0);
                ctx.lineTo(x1, y1);
                ctx.stroke();
                break;

            case 'star':
                const starCx = (x0 + x1) / 2;
                const starCy = (y0 + y1) / 2;
                const outerR = Math.max(w, h) / 2;
                const innerR = outerR * 0.42;
                const spikes = 5;
                let rot = (Math.PI / 2) * 3;
                let sx = starCx;
                let sy = starCy;
                const step = Math.PI / spikes;

                ctx.beginPath();
                ctx.moveTo(starCx, starCy - outerR);
                for (let i = 0; i < spikes; i++) {
                    sx = starCx + Math.cos(rot) * outerR;
                    sy = starCy + Math.sin(rot) * outerR;
                    ctx.lineTo(sx, sy);
                    rot += step;

                    sx = starCx + Math.cos(rot) * innerR;
                    sy = starCy + Math.sin(rot) * innerR;
                    ctx.lineTo(sx, sy);
                    rot += step;
                }
                ctx.lineTo(starCx, starCy - outerR);
                ctx.closePath();
                this.applyShapeStyle(ctx);
                break;

            case 'heart':
                ctx.beginPath();
                const topCurveH = h * 0.3;
                ctx.moveTo(minX + w / 2, minY + topCurveH);
                ctx.bezierCurveTo(minX + w / 2, minY, minX, minY, minX, minY + topCurveH);
                ctx.bezierCurveTo(minX, minY + (h + topCurveH) / 2, minX + w / 2, minY + (h + topCurveH) / 2, minX + w / 2, minY + h);
                ctx.bezierCurveTo(minX + w / 2, minY + (h + topCurveH) / 2, minX + w, minY + (h + topCurveH) / 2, minX + w, minY + topCurveH);
                ctx.bezierCurveTo(minX + w, minY, minX + w / 2, minY, minX + w / 2, minY + topCurveH);
                ctx.closePath();
                this.applyShapeStyle(ctx);
                break;
        }
        ctx.restore();
    }

    applyShapeStyle(ctx) {
        if (this.shapeStyle === 'fill') {
            ctx.fill();
        } else if (this.shapeStyle === 'both') {
            ctx.fill();
            ctx.stroke();
        } else {
            ctx.stroke();
        }
    }

    /* =========================================================
       BUCKET FILL (4-WAY BFS 32-BIT)
       ========================================================= */
    floodFill(startX, startY, fillHexColor, tolerance = 36) {
        const layer = this.getActiveLayer();
        if (!layer || !layer.visible) return;
        const ctx = layer.ctx;
        const imgData = ctx.getImageData(0, 0, this.width, this.height);
        const data = imgData.data;

        const targetIdx = (startY * this.width + startX) * 4;
        const targetR = data[targetIdx];
        const targetG = data[targetIdx + 1];
        const targetB = data[targetIdx + 2];
        const targetA = data[targetIdx + 3];

        const fillRgb = this.hexToRgb(fillHexColor);
        if (!fillRgb) return;

        if (Math.abs(targetR - fillRgb.r) < 5 &&
            Math.abs(targetG - fillRgb.g) < 5 &&
            Math.abs(targetB - fillRgb.b) < 5 &&
            targetA === 255) {
            return;
        }

        const colorMatch = (idx) => {
            const diff = Math.abs(data[idx] - targetR) +
                         Math.abs(data[idx + 1] - targetG) +
                         Math.abs(data[idx + 2] - targetB) +
                         Math.abs(data[idx + 3] - targetA);
            return diff <= tolerance * 4;
        };

        const queue = [startX, startY];
        const visited = new Uint8Array(this.width * this.height);

        while (queue.length > 0) {
            const cy = queue.pop();
            const cx = queue.pop();

            if (cx < 0 || cx >= this.width || cy < 0 || cy >= this.height) continue;
            const pIdx = cy * this.width + cx;
            if (visited[pIdx]) continue;
            visited[pIdx] = 1;

            const idx = pIdx * 4;
            if (!colorMatch(idx)) continue;

            data[idx] = fillRgb.r;
            data[idx + 1] = fillRgb.g;
            data[idx + 2] = fillRgb.b;
            data[idx + 3] = 255;

            if (cx > 0) { queue.push(cx - 1, cy); }
            if (cx < this.width - 1) { queue.push(cx + 1, cy); }
            if (cy > 0) { queue.push(cx, cy - 1); }
            if (cy < this.height - 1) { queue.push(cx, cy + 1); }
        }

        ctx.putImageData(imgData, 0, 0);
        this.renderComposite();
        this.renderLayersUI();
    }

    /* =========================================================
       TEXT TOOL (PIXEL-PERFECT 1:1 CANVAS POSITIONING)
       ========================================================= */
    startMovableText(text, options = {}) {
        if (!text) return;
        this.cancelMovableText();

        const size = options.fontSize || 32;
        const font = options.fontFamily || 'Outfit, sans-serif';
        const style = options.fontStyle || 'normal';
        const color = options.color || this.color;
        const bgColor = options.bgColor || 'transparent';

        // Ukur dimensi teks secara tepat menggunakan displayCtx
        this.displayCtx.save();
        this.displayCtx.font = `${style} ${size}px ${font}`;
        this.displayCtx.textBaseline = 'top';
        this.displayCtx.textAlign = 'left';
        const metrics = this.displayCtx.measureText(text);
        const textWidth = Math.ceil(metrics.width);
        const textHeight = Math.ceil(size);
        this.displayCtx.restore();

        // Tentukan posisi awal (gunakan koordinat klik pengguna jika ada)
        const startCanvasX = options.x !== undefined ? options.x : Math.max(20, Math.round((this.width - textWidth) / 2));
        const startCanvasY = options.y !== undefined ? options.y : Math.max(20, Math.round((this.height - textHeight) / 2));

        this.activeMovableText = {
            text,
            options: { ...options, fontSize: size, fontFamily: font, fontStyle: style, color, bgColor },
            canvasX: startCanvasX,
            canvasY: startCanvasY,
            width: textWidth,
            height: textHeight,
            layerIndex: this.activeLayerIndex
        };

        // Buat floating toolbar ringkas di atas teks (hanya toolbar pengontrol, teks dirender langsung di kanvas 1:1)
        const tb = document.createElement('div');
        tb.id = 'studioMovableTextToolbar';
        tb.className = 'movable-floating-toolbar';
        tb.innerHTML = `
            <span class="movable-drag-tag" id="movableDragTag" title="Seret di sini atau seret langsung pada teks di kanvas">Geser Teks</span>
            <button type="button" class="btn-movable-action apply" onclick="studio.commitMovableText()" title="Bubuhkan teks permanen pada posisi ini">Terapkan Teks</button>
            <button type="button" class="btn-movable-action edit" onclick="studio.editMovableText()" title="Ubah isi teks atau ukuran">Ubah</button>
            <button type="button" class="btn-movable-action cancel" onclick="studio.cancelMovableText()" title="Batalkan teks ini">Batal</button>
        `;

        this.transformWrapper.appendChild(tb);
        this.updateMovableToolbarPosition();

        // Event listener geser dari toolbar drag tag
        let isTbDragging = false;
        let startClientX = 0;
        let startClientY = 0;
        let initialTextX = startCanvasX;
        let initialTextY = startCanvasY;

        const onTbDragStart = (e) => {
            if (e.target.closest('button')) return;
            e.preventDefault();
            e.stopPropagation();
            isTbDragging = true;
            const p = e.touches ? e.touches[0] : e;
            startClientX = p.clientX;
            startClientY = p.clientY;
            initialTextX = this.activeMovableText.canvasX;
            initialTextY = this.activeMovableText.canvasY;
        };

        const onTbDragMove = (e) => {
            if (!isTbDragging || !this.activeMovableText) return;
            e.preventDefault();
            const p = e.touches ? e.touches[0] : e;
            const deltaX = Math.round((p.clientX - startClientX) / this.scale);
            const deltaY = Math.round((p.clientY - startClientY) / this.scale);

            this.activeMovableText.canvasX = initialTextX + deltaX;
            this.activeMovableText.canvasY = initialTextY + deltaY;

            this.renderComposite();
            this.updateMovableToolbarPosition();
        };

        const onTbDragEnd = () => {
            isTbDragging = false;
        };

        tb.addEventListener('mousedown', onTbDragStart);
        window.addEventListener('mousemove', onTbDragMove);
        window.addEventListener('mouseup', onTbDragEnd);

        tb.addEventListener('touchstart', onTbDragStart, { passive: false });
        window.addEventListener('touchmove', onTbDragMove, { passive: false });
        window.addEventListener('touchend', onTbDragEnd);

        this.renderComposite();
        window.showToast?.('Teks siap digeser. Sentuh & seret langsung pada teks di kanvas atau gunakan tombol panah.');
    }

    updateMovableToolbarPosition() {
        const tb = document.getElementById('studioMovableTextToolbar');
        if (!tb || !this.activeMovableText) return;

        const pad = 6;
        let tbX = this.activeMovableText.canvasX - pad;
        let tbY = this.activeMovableText.canvasY - pad - 44;

        if (tbY < 8) {
            tbY = this.activeMovableText.canvasY + this.activeMovableText.height + pad + 8;
        }

        tb.style.left = `${tbX}px`;
        tb.style.top = `${tbY}px`;
    }

    drawTextPreviewOverlay(ctx) {
        if (!this.activeMovableText) return;
        const textObj = this.activeMovableText;

        // 1. Gambar teks persis dengan fungsi drawTextObject
        this.drawTextObject(textObj, ctx);

        // 2. Gambar dashed selection box dan 4 corner handles langsung di kanvas
        const pad = 6;
        const bx = textObj.canvasX - pad;
        const by = textObj.canvasY - pad;
        const bw = textObj.width + pad * 2;
        const bh = textObj.height + pad * 2;

        ctx.save();
        ctx.strokeStyle = '#2563eb';
        ctx.lineWidth = 1.5;
        ctx.setLineDash([5, 5]);
        ctx.strokeRect(bx, by, bw, bh);

        // Handles sudut
        ctx.fillStyle = '#2563eb';
        ctx.setLineDash([]);
        const hs = 6;
        ctx.fillRect(bx - 3, by - 3, hs, hs);
        ctx.fillRect(bx + bw - 3, by - 3, hs, hs);
        ctx.fillRect(bx - 3, by + bh - 3, hs, hs);
        ctx.fillRect(bx + bw - 3, by + bh - 3, hs, hs);
        ctx.restore();
    }

    commitMovableText() {
        if (!this.activeMovableText) return;
        const textObj = {
            id: 'txt_' + Date.now(),
            text: this.activeMovableText.text,
            options: { ...this.activeMovableText.options },
            canvasX: this.activeMovableText.canvasX,
            canvasY: this.activeMovableText.canvasY,
            layerIndex: this.activeLayerIndex
        };

        const targetLayer = this.layers[textObj.layerIndex] || this.getActiveLayer();
        this.drawTextObject(textObj, targetLayer.ctx);
        this.textObjects.push(textObj);

        this.cancelMovableText();

        this.renderComposite();
        this.renderLayersUI();
        this.saveState();
        this.triggerAutosave();

        window.showToast?.('Teks berhasil dibubuhkan tepat 1:1 sesuai posisi yang Anda letakkan.');
    }

    drawTextObject(textObj, targetCtx = null) {
        const layer = this.layers[textObj.layerIndex] || this.getActiveLayer();
        if (!layer) return;
        const ctx = targetCtx || layer.ctx;
        const { text, options, canvasX, canvasY } = textObj;
        const size = options.fontSize || 32;
        const font = options.fontFamily || 'Outfit, sans-serif';
        const style = options.fontStyle || 'normal';
        const color = options.color || this.color;
        const bgColor = options.bgColor || 'transparent';

        ctx.save();
        ctx.font = `${style} ${size}px ${font}`;
        ctx.textBaseline = 'top';
        ctx.textAlign = 'left';

        const metrics = ctx.measureText(text);
        const textWidth = Math.ceil(metrics.width);
        const textHeight = Math.ceil(size);

        if (bgColor && bgColor !== 'transparent') {
            const pad = 6;
            ctx.fillStyle = bgColor;
            ctx.fillRect(canvasX - pad, canvasY - pad, textWidth + pad * 2, textHeight + pad * 2);
        }

        ctx.fillStyle = color;
        ctx.fillText(text, canvasX, canvasY);
        ctx.restore();

        if (!targetCtx) {
            this.renderComposite();
            this.renderLayersUI();
            this.saveState();
            this.triggerAutosave();
        }
    }

    reopenMovableText(index = -1) {
        if (!this.textObjects.length) {
            window.showToast?.('Belum ada teks yang ditaruh untuk dipindahkan.');
            return;
        }

        const targetIdx = index === -1 ? this.textObjects.length - 1 : index;
        const textObj = this.textObjects.splice(targetIdx, 1)[0];

        // Undo stamp sebelumnya dari history agar kanvas bersih sebelum dipindah
        this.undo();

        // Buka kembali movable box di posisi persis teks sebelumnya
        this.startMovableText(textObj.text, {
            ...textObj.options,
            x: textObj.canvasX,
            y: textObj.canvasY
        });

        window.showToast?.('Teks aktif kembali untuk digeser / dipindah.');
    }

    editMovableText() {
        if (!this.activeMovableText) return;
        const { text, options } = this.activeMovableText;
        const strInput = document.getElementById('textInputString');
        const sizeInput = document.getElementById('textFontSize');
        if (strInput) strInput.value = text;
        if (sizeInput) sizeInput.value = options.fontSize || 32;
        window.openTextModal?.();
    }

    cancelMovableText() {
        const tb = document.getElementById('studioMovableTextToolbar');
        if (tb) tb.remove();
        const oldBox = document.getElementById('studioMovableTextBox');
        if (oldBox) oldBox.remove();
        this.activeMovableText = null;
        this.isDraggingText = false;
        this.renderComposite();
    }

    addText(text, options = {}) {
        if (!text) return;
        const canvasX = options.x !== undefined ? options.x : 60;
        const canvasY = options.y !== undefined ? options.y : 100;
        const textObj = {
            id: 'txt_' + Date.now(),
            text,
            options,
            canvasX,
            canvasY,
            layerIndex: this.activeLayerIndex
        };
        this.textObjects.push(textObj);
        this.drawTextObject(textObj);
    }

    /**
     * Impor Gambar Referensi ke Layer Tersendiri (Modular, Dapat Digeser, Dihapus & Diatur Opacity)
     */
    importImageAsDedicatedLayer(imgSource, title = 'Referensi') {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => {
            // Buat layer tersendiri khusus untuk gambar referensi
            const cleanTitle = (title || 'Referensi').substring(0, 18);
            const layerName = `Ref: ${cleanTitle}`;
            const targetLayer = this.addLayer(layerName, false);
            targetLayer.isReference = true; // Tandai sebagai gambar referensi agar otomatis diblokir dari Pameran

            // Skala proporsional agar gambar muat nyaman di kanvas 960x600 (maksimal 80% area agar mudah digeser)
            const maxW = this.width * 0.8;
            const maxH = this.height * 0.8;
            const ratio = Math.min(maxW / img.width, maxH / img.height, 1.0);
            const w = Math.round(img.width * ratio);
            const h = Math.round(img.height * ratio);
            const x = Math.round((this.width - w) / 2);
            const y = Math.round((this.height - h) / 2);

            targetLayer.ctx.drawImage(img, x, y, w, h);

            this.renderComposite();
            this.renderLayersUI();
            this.saveState();
            this.triggerAutosave();

            // Otomatis aktifkan tool Geser Layer agar siswa langsung dapat memindahkan posisi gambar di kanvas
            this.activateMoveLayerTool();

            window.showToast?.(`"${cleanTitle}" dimuat di layer tersendiri. Anda dapat menggeser posisi gambar di kanvas, mengatur transparansi di panel Lapisan, atau menghapusnya.`);
        };
        img.onerror = () => {
            window.showToast?.('Gagal memuat gambar referensi.');
        };
        img.src = imgSource;
    }

    loadImageToLayer(imgSource, asTracingGuide = false) {
        if (asTracingGuide) {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => {
                const targetLayer = this.addLayer('Panduan Tracing (Sketsa)', false);
                targetLayer.isReference = true;
                targetLayer.opacity = 0.35;
                const ratio = Math.min(this.width / img.width, this.height / img.height);
                const w = Math.round(img.width * ratio);
                const h = Math.round(img.height * ratio);
                const x = Math.round((this.width - w) / 2);
                const y = Math.round((this.height - h) / 2);
                targetLayer.ctx.drawImage(img, x, y, w, h);

                this.addLayer('Layer Gambar Sketsa', false);
                this.renderComposite();
                this.renderLayersUI();
                this.saveState();
                this.triggerAutosave();
                window.showToast?.('Panduan tracing transparan siap dijiplak.');
            };
            img.src = imgSource;
            return;
        }

        this.importImageAsDedicatedLayer(imgSource, 'Gambar Impor');
    }

    /* =========================================================
       GRID BANTU KOMPOSISI SENI RUPA (RULE OF THIRDS & GRID KOTAK)
       ========================================================= */
    drawCompositionGrid(ctx) {
        ctx.save();
        if (this.gridMode === 1) {
            // Mode 1: Rule of Thirds (3x3 Grid Komposisi Emas)
            ctx.strokeStyle = 'rgba(59, 130, 246, 0.5)';
            ctx.lineWidth = 1.5;
            ctx.setLineDash([6, 6]);

            // Garis vertikal 1/3 dan 2/3
            for (let i = 1; i <= 2; i++) {
                const x = (this.width / 3) * i;
                ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, this.height); ctx.stroke();
            }
            // Garis horizontal 1/3 dan 2/3
            for (let j = 1; j <= 2; j++) {
                const y = (this.height / 3) * j;
                ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(this.width, y); ctx.stroke();
            }

            // Titik fokus perpotongan (Golden Ratio intersection dots)
            ctx.setLineDash([]);
            ctx.fillStyle = 'rgba(37, 99, 235, 0.75)';
            for (let i = 1; i <= 2; i++) {
                for (let j = 1; j <= 2; j++) {
                    ctx.beginPath();
                    ctx.arc((this.width / 3) * i, (this.height / 3) * j, 4.5, 0, Math.PI * 2);
                    ctx.fill();
                }
            }
        } else if (this.gridMode === 2) {
            // Mode 2: Grid Kotak Simetris (8x8)
            ctx.strokeStyle = 'rgba(148, 163, 184, 0.4)';
            ctx.lineWidth = 1;
            ctx.setLineDash([4, 4]);

            for (let i = 1; i < 8; i++) {
                const x = (this.width / 8) * i;
                ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, this.height); ctx.stroke();
            }
            for (let j = 1; j < 6; j++) {
                const y = (this.height / 6) * j;
                ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(this.width, y); ctx.stroke();
            }
        }
        ctx.restore();
    }

    toggleGrid() {
        this.gridMode = (this.gridMode + 1) % 3;
        this.renderComposite();

        const statusText = document.getElementById('gridStatusText');
        if (statusText) {
            if (this.gridMode === 1) statusText.textContent = '3x3';
            else if (this.gridMode === 2) statusText.textContent = '8x8';
            else statusText.textContent = 'Grid';
        }

        const labels = ['Grid Dinonaktifkan', 'Grid Komposisi 3x3 (Rule of Thirds)', 'Grid Kotak Simetris (8x8)'];
        window.showToast?.(labels[this.gridMode]);
    }

    /* =========================================================
       PER-BRUSH INDEPENDENT PROFILES
       ========================================================= */
    setBrushTool(t) {
        if (!this.brushProfiles[t]) return;
        this.tool = t;
        const p = this.brushProfiles[t];
        this.brushSize = p.size;
        this.brushOpacity = p.opacity;
        this.stabilizer = p.stabilizer;

        this.syncToolSettingsUI();
    }

    syncToolSettingsUI() {
        const p = this.brushProfiles[this.tool];
        if (!p) return;

        const rangeSize = document.getElementById('brushSizeRange');
        const valSize = document.getElementById('brushSizeVal');
        if (rangeSize) rangeSize.value = p.size;
        if (valSize) valSize.textContent = `${p.size} px`;

        const rangeOpacity = document.getElementById('brushOpacityRange');
        const valOpacity = document.getElementById('brushOpacityVal');
        const opPercent = Math.round(p.opacity * 100);
        if (rangeOpacity) rangeOpacity.value = opPercent;
        if (valOpacity) valOpacity.textContent = `${opPercent}%`;

        const rangeStab = document.getElementById('brushStabilizerRange');
        const valStab = document.getElementById('brushStabilizerVal');
        if (rangeStab) rangeStab.value = p.stabilizer;
        if (valStab) {
            const num = p.stabilizer;
            if (num === 0) valStab.textContent = 'Mati (0)';
            else if (num <= 3) valStab.textContent = `${num} (Ringan)`;
            else if (num <= 7) valStab.textContent = `${num} (Sedang)`;
            else valStab.textContent = `${num} (Sangat Halus)`;
        }

        const nameEl = document.getElementById('currentToolName');
        if (nameEl) nameEl.textContent = p.name || this.tool;
    }

    saveCurrentToolSetting(key, val) {
        if (!this.brushProfiles[this.tool]) return;
        this.brushProfiles[this.tool][key] = val;
        try {
            localStorage.setItem('digiart_brush_profiles', JSON.stringify(this.brushProfiles));
        } catch (e) {}
    }

    hexToRgb(hex) {
        const shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
        hex = hex.replace(shorthandRegex, (m, r, g, b) => r + r + g + g + b + b);
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : null;
    }

    hexToRgba(hex, alpha = 1.0) {
        const rgb = this.hexToRgb(hex);
        if (!rgb) return `rgba(31, 75, 224, ${alpha})`;
        return `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${alpha})`;
    }

    /* =========================================================
       UNDO & REDO HISTORY SYSTEM
       ========================================================= */
    saveState() {
        if (!this.layers || !this.layers.length) return;
        const snapshot = {
            activeLayerIndex: this.activeLayerIndex,
            layers: this.layers.map(l => {
                const copyCanvas = document.createElement('canvas');
                copyCanvas.width = this.width;
                copyCanvas.height = this.height;
                const copyCtx = copyCanvas.getContext('2d');
                copyCtx.drawImage(l.canvas, 0, 0);
                return {
                    id: l.id,
                    name: l.name,
                    visible: l.visible,
                    opacity: l.opacity,
                    isReference: !!l.isReference,
                    canvas: copyCanvas
                };
            })
        };

        this.history.push(snapshot);
        if (this.history.length > this.maxHistory) {
            this.history.shift();
        }
        this.future = [];
    }

    undo() {
        if (this.history.length <= 1) {
            window.showToast?.('Tidak ada aksi yang bisa dibatalkan.');
            return;
        }

        const currentState = this.history.pop();
        this.future.push(currentState);

        const prevState = this.history[this.history.length - 1];
        this.restoreState(prevState);
        window.showToast?.('Undo');
    }

    redo() {
        if (!this.future.length) {
            window.showToast?.('Tidak ada aksi yang bisa diulangi.');
            return;
        }

        const nextState = this.future.pop();
        this.history.push(nextState);
        this.restoreState(nextState);
        window.showToast?.('Redo');
    }

    restoreState(state) {
        if (!state || !state.layers) return;
        this.layers = state.layers.map(sl => {
            const canvas = document.createElement('canvas');
            canvas.width = this.width;
            canvas.height = this.height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(sl.canvas, 0, 0);
            return {
                id: sl.id,
                name: sl.name,
                canvas,
                ctx,
                visible: sl.visible,
                opacity: sl.opacity,
                isReference: !!sl.isReference
            };
        });

        this.activeLayerIndex = Math.min(state.activeLayerIndex || 0, this.layers.length - 1);
        this.renderLayersUI();
        this.renderComposite();
        this.triggerAutosave();
    }

    /* =========================================================
       EXPORT, DOWNLOAD (PNG / JPG) & NEW CANVAS
       ========================================================= */
    /**
     * Ekspor kanvas khusus untuk Pameran Karya Siswa:
     * 1. Blokir & hilangkan seluruh layer yang berasal dari gambar referensi internet / tracing (isReference === true).
     * 2. Periksa apakah siswa sudah menggambar karya orisinal sendiri pada layer gambar non-referensi.
     * 3. Jika hanya ada layer referensi dan karya siswa kosong, tolak ekspor dan tampilkan edukasi.
     */
    exportForPameran() {
        let referenceLayersCount = 0;
        let nonReferenceDrawnLayersCount = 0;

        const exportCanvas = document.createElement('canvas');
        exportCanvas.width = this.width;
        exportCanvas.height = this.height;
        const ctx = exportCanvas.getContext('2d');

        for (let idx = 0; idx < this.layers.length; idx++) {
            const layer = this.layers[idx];
            const isRef = !!layer.isReference || 
                          (typeof layer.name === 'string' && (
                              layer.name.startsWith('Ref:') || 
                              layer.name.toLowerCase().includes('panduan tracing') ||
                              layer.name.toLowerCase().includes('referensi')
                          ));

            if (isRef) {
                referenceLayersCount++;
                continue; // SINGKIRKAN DARI HASIL PAMERAN
            }

            // Cek apakah layer ini memiliki goresan gambar dari siswa (bukan background putih bawaan)
            const isDefaultBg = idx === 0 && (
                layer.name.toLowerCase().includes('background') || 
                layer.name.toLowerCase().includes('latar')
            );

            if (!isDefaultBg && layer.canvas) {
                try {
                    const lCtx = layer.canvas.getContext('2d');
                    const imgData = lCtx.getImageData(0, 0, this.width, this.height);
                    const pixels = imgData.data;
                    let strokeFound = 0;
                    // Sampling step 64 untuk mendeteksi goresan kuas secara instan
                    for (let p = 3; p < pixels.length; p += 64) {
                        if (pixels[p] > 15) { // alpha > 15
                            strokeFound++;
                            if (strokeFound >= 30) break;
                        }
                    }
                    if (strokeFound >= 30) {
                        nonReferenceDrawnLayersCount++;
                    }
                } catch (e) {}
            }

            if (layer.visible) {
                ctx.save();
                ctx.globalAlpha = layer.opacity;
                ctx.drawImage(layer.canvas, 0, 0);
                ctx.restore();
            }
        }

        // Jika ada teks aktif buatan siswa
        if (this.activeMovableText) {
            this.drawTextPreviewOverlay(ctx);
            nonReferenceDrawnLayersCount++;
        }

        if (nonReferenceDrawnLayersCount === 0) {
            return {
                success: false,
                reason: 'NO_ORIGINAL_ARTWORK',
                referenceLayersCount,
                message: referenceLayersCount > 0
                    ? 'Lapisan gambar referensi dari internet otomatis disembunyikan. Silakan buat gambar ilustrasi karya Anda sendiri di atas kanvas sebelum mengirim ke Pameran.'
                    : 'Kanvas masih kosong. Silakan gambar karya Anda sendiri terlebih dahulu sebelum mengirim ke Pameran Karya Siswa.'
            };
        }

        return {
            success: true,
            dataUrl: exportCanvas.toDataURL('image/png'),
            strippedReferenceCount: referenceLayersCount
        };
    }

    exportPNG() {
        const exportCanvas = document.createElement('canvas');
        exportCanvas.width = this.width;
        exportCanvas.height = this.height;
        const ctx = exportCanvas.getContext('2d');

        for (let idx = 0; idx < this.layers.length; idx++) {
            const layer = this.layers[idx];
            if (layer.visible) {
                ctx.save();
                ctx.globalAlpha = layer.opacity;
                ctx.drawImage(layer.canvas, 0, 0);
                ctx.restore();
            }
        }

        return exportCanvas.toDataURL('image/png');
    }

    exportJPG(quality = 0.92) {
        const exportCanvas = document.createElement('canvas');
        exportCanvas.width = this.width;
        exportCanvas.height = this.height;
        const ctx = exportCanvas.getContext('2d');

        // Latar putih bersih solid agar area transparan tidak hitam pekat di format JPG
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, this.width, this.height);

        for (let idx = 0; idx < this.layers.length; idx++) {
            const layer = this.layers[idx];
            if (layer.visible) {
                ctx.save();
                ctx.globalAlpha = layer.opacity;
                ctx.drawImage(layer.canvas, 0, 0);
                ctx.restore();
            }
        }

        return exportCanvas.toDataURL('image/jpeg', quality);
    }

    downloadImage(format = 'png') {
        const isJpg = format.toLowerCase() === 'jpg' || format.toLowerCase() === 'jpeg';
        const exportCanvas = document.createElement('canvas');
        exportCanvas.width = this.width;
        exportCanvas.height = this.height;
        const ctx = exportCanvas.getContext('2d');

        if (isJpg) {
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, this.width, this.height);
        }

        for (let idx = 0; idx < this.layers.length; idx++) {
            const layer = this.layers[idx];
            if (layer.visible) {
                ctx.save();
                ctx.globalAlpha = layer.opacity;
                ctx.drawImage(layer.canvas, 0, 0);
                ctx.restore();
            }
        }

        const mime = isJpg ? 'image/jpeg' : 'image/png';
        const ext = isJpg ? 'jpg' : 'png';
        const filename = `karya-digiart-${Date.now()}.${ext}`;

        // Menggunakan blob URL asli agar browser menyimpan ekstensi .png / .jpg presisi 100%
        exportCanvas.toBlob((blob) => {
            if (!blob) {
                window.showToast?.('Gagal memproses unduhan gambar.');
                return;
            }
            const blobUrl = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = blobUrl;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            setTimeout(() => URL.revokeObjectURL(blobUrl), 2000);
            window.showToast?.(`Karya berhasil diunduh dalam format ${ext.toUpperCase()} (${filename}).`);
        }, mime, 0.95);
    }

    downloadPNG() {
        this.downloadImage('png');
    }

    downloadJPG() {
        this.downloadImage('jpg');
    }

    toggleFullscreen() {
        const el = document.querySelector('.studio-wrapper') || document.documentElement;
        if (!document.fullscreenElement && !document.webkitFullscreenElement) {
            if (el.requestFullscreen) {
                el.requestFullscreen().catch(e => console.warn('Fullscreen request failed:', e));
            } else if (el.webkitRequestFullscreen) {
                el.webkitRequestFullscreen();
            }
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen().catch(e => console.warn('Exit fullscreen failed:', e));
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            }
        }
    }

    escapeHTML(str) {
        return String(str).replace(/[&<>"']/g, m => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[m]));
    }
}

window.StudioEngine = StudioEngine;
