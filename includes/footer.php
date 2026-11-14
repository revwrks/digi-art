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
                <small>Media Pembelajaran Digital Seni Rupa • SMP Kelas VIII</small>
            </div>
            <div class="footer-copy">
                &copy; 2026 Digi-Art • Revworks.
            </div>
        </div>
    </footer>

    <!-- Core Scripts -->
    <script src="assets/js/compressor.js?v=<?= @filemtime(__DIR__ . '/../assets/js/compressor.js') ?>"></script>
    <script src="assets/js/app.js?v=<?= @filemtime(__DIR__ . '/../assets/js/app.js') ?>"></script>
</body>
</html>
