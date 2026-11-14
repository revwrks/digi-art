<?php
$pageTitle = 'Kuis Interaktif Kahoot — Digi-Art';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/auth_guard.php';
?>

<div class="page-header" style="text-align:center">
    <div class="kicker">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
        EVALUASI PEMAHAMAN SISWA
    </div>
    <h1>Kuis Interaktif Seni Rupa</h1>
    <p style="margin:0 auto">Jawab soal seputar menggambar ilustrasi dengan kecepatan dan ketepatan tinggi untuk mengumpulkan poin maksimal.</p>
</div>

<div class="kahoot-container">
    <div id="kahootGameZone"></div>
</div>

<script src="assets/js/quiz-kahoot.js?v=<?= @filemtime(__DIR__ . '/../assets/js/quiz-kahoot.js') ?>"></script>
<script>
    let quizApp = null;
    document.addEventListener('DOMContentLoaded', () => {
        quizApp = new KahootQuiz('kahootGameZone');
        quizApp.loadQuestions();
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
