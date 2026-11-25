<?php
if (!defined('DIGIART_APP')) { http_response_code(403); exit; }
?>
    </main>

    <!-- Global Modals -->
    <?php include __DIR__ . '/modals.php'; ?>

    <!-- Global App Toast Notification -->
    <div id="appToast" class="app-toast" role="alert" aria-live="polite"></div>

    <!-- Bottom Footer -->
    <footer class="app-footer">
        <div class="footer-inner">
            <div class="footer-brand">
                Digi-Art
                <small>Media Pembelajaran Seni Rupa SMP Kelas VIII</small>
            </div>
            <nav aria-label="Tautan Cepat Footer" style="display:flex;gap:20px;flex-wrap:wrap;font-size:13px;font-weight:600;color:rgba(255,255,255,0.88)">
                <a href="index.php?page=home">Beranda</a>
                <a href="index.php?page=pameran">Pameran Karya</a>
                <a href="index.php?page=studio">Studio Digital</a>
            </nav>
            <div class="footer-copy">
                &copy; 2026 Digi-Art
            </div>
        </div>
    </footer>

    <!-- Core Scripts -->
    <script src="assets/js/compressor.js?v=<?= @filemtime(__DIR__ . '/../assets/js/compressor.js') ?>"></script>
    <script src="assets/js/app.js?v=<?= @filemtime(__DIR__ . '/../assets/js/app.js') ?>"></script>
</body>
</html>
