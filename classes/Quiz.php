<?php
/**
 * Quiz Management (OOP Deep Module)
 * Mengatur pertanyaan dan leaderboard kuis interaktif bergaya Kahoot!.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Activity.php';

class Quiz {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Ambil seluruh pertanyaan kuis (untuk pengelolaan Guru/Admin, termasuk correct_index)
     */
    public function getQuestions(): array {
        return $this->db->fetchAll(
            "SELECT id, order_num, question, option_a, option_b, option_c, option_d, correct_index, time_limit, points 
             FROM quiz_questions 
             ORDER BY order_num ASC"
        );
    }

    /**
     * Ambil pertanyaan kuis untuk mode bermain (TANPA correct_index) dan inisialisasi sesi kuis terverifikasi
     */
    public function getQuestionsForPlay(): array {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $qs = $this->db->fetchAll(
            "SELECT id, order_num, question, option_a, option_b, option_c, option_d, time_limit, points 
             FROM quiz_questions 
             ORDER BY order_num ASC"
        );

        $_SESSION['quiz_attempt'] = [
            'question_ids'   => array_map('intval', array_column($qs, 'id')),
            'answered'       => [],
            'score'          => 0,
            'streak'         => 0,
            'highest_streak' => 0,
            'correct_count'  => 0,
            'started_at'     => time()
        ];

        return $qs;
    }

    /**
     * Verifikasi jawaban siswa di sisi server dan hitung poin secara aman
     */
    public function checkAnswer(int $questionId, int $chosenIndex, int $timeLeft): array {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $attempt = $_SESSION['quiz_attempt'] ?? null;
        if (!is_array($attempt) || empty($attempt['question_ids'])) {
            return ['success' => false, 'message' => 'Sesi kuis tidak ditemukan atau sudah berakhir.'];
        }

        if ($questionId <= 0 || !in_array($questionId, $attempt['question_ids'], true)) {
            return ['success' => false, 'message' => 'Soal tidak termasuk dalam sesi kuis aktif.'];
        }

        if (!empty($attempt['answered'][$questionId])) {
            return ['success' => false, 'message' => 'Soal ini sudah dijawab dalam sesi ini.'];
        }

        $q = $this->db->fetch(
            "SELECT correct_index, time_limit, points FROM quiz_questions WHERE id = :id LIMIT 1",
            [':id' => $questionId]
        );
        if (!$q) {
            return ['success' => false, 'message' => 'Soal kuis tidak ditemukan.'];
        }

        $timeLimit = max(1, (int)$q['time_limit']);
        $timeLeft = max(0, min($timeLimit, $timeLeft));
        $correctIndex = (int)$q['correct_index'];
        $isCorrect = ($chosenIndex >= 0 && $chosenIndex === $correctIndex);

        if ($isCorrect) {
            $attempt['correct_count']++;
            $attempt['streak']++;
            if ($attempt['streak'] > $attempt['highest_streak']) {
                $attempt['highest_streak'] = $attempt['streak'];
            }
            $speedRatio = max(0.1, $timeLeft / max(1, $timeLimit));
            $base = 500;
            $speedBonus = round(500 * $speedRatio);
            $streakBonus = min(500, ($attempt['streak'] - 1) * 100);
            $scale = max(1, (int)$q['points']) / 1000;
            $pointsEarned = (int)round(($base + $speedBonus + $streakBonus) * $scale);
            $attempt['score'] += $pointsEarned;
        } else {
            $attempt['streak'] = 0;
            $pointsEarned = 0;
        }

        $attempt['answered'][$questionId] = true;
        $_SESSION['quiz_attempt'] = $attempt;

        return [
            'success'        => true,
            'is_correct'     => $isCorrect,
            'correct_index'  => $correctIndex,
            'points_earned'  => $pointsEarned,
            'total_score'    => $attempt['score'],
            'streak'         => $attempt['streak'],
            'highest_streak' => $attempt['highest_streak'],
            'correct_count'  => $attempt['correct_count']
        ];
    }

    /**
     * Simpan hasil kuis terverifikasi dari $_SESSION['quiz_attempt'] ke tabel skor & leaderboard
     */
    public function submitVerifiedScore(): array {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $attempt = $_SESSION['quiz_attempt'] ?? null;
        if (!is_array($attempt) || empty($attempt['answered']) || count($attempt['answered']) === 0) {
            return ['success' => false, 'message' => 'Tidak ada sesi kuis aktif yang valid.'];
        }

        $user = Auth::user();
        $userId = $user['id'] ?? null;
        $studentName = trim((string)($user['full_name'] ?? 'Siswa'));
        if ($studentName === '') {
            $studentName = 'Siswa';
        }
        $kelas = trim((string)($user['kelas'] ?? 'VIII'));
        if ($kelas === '') {
            $kelas = 'VIII';
        }

        $totalScore = (int)($attempt['score'] ?? 0);
        $correctCount = (int)($attempt['correct_count'] ?? 0);
        $totalQuestions = max(count($attempt['question_ids'] ?? []), count($attempt['answered']));
        $highestStreak = (int)($attempt['highest_streak'] ?? 0);

        $id = $this->db->insert('quiz_scores', [
            'user_id'         => $userId,
            'student_name'    => $studentName,
            'kelas'           => $kelas,
            'total_score'     => $totalScore,
            'correct_count'   => $correctCount,
            'total_questions' => $totalQuestions,
            'highest_streak'  => $highestStreak
        ]);

        Activity::log("Menyelesaikan Kuis Kahoot: skor {$totalScore} pts ({$correctCount}/{$totalQuestions})", $userId, $studentName);

        unset($_SESSION['quiz_attempt']);

        // Ambil ranking terkini siswa
        $rankQuery = $this->db->fetch(
            "SELECT COUNT(*) + 1 AS rank_pos FROM quiz_scores WHERE total_score > :score",
            [':score' => $totalScore]
        );
        $rank = (int)($rankQuery['rank_pos'] ?? 1);

        return [
            'success'     => true,
            'score_id'    => $id,
            'rank'        => $rank,
            'total_score' => $totalScore
        ];
    }

    /**
     * Simpan hasil kuis siswa (didelegasikan ke submitVerifiedScore agar skor selalu terverifikasi server)
     */
    public function submitScore(array $data = []): array {
        return $this->submitVerifiedScore();
    }

    /**
     * Ambil papan peringkat (Leaderboard) teratas
     */
    public function getLeaderboard(int $limit = 20): array {
        return $this->db->fetchAll(
            "SELECT id, student_name, kelas, total_score, correct_count, total_questions, highest_streak, completed_at
             FROM quiz_scores 
             ORDER BY total_score DESC, completed_at ASC 
             LIMIT :limit",
            [':limit' => $limit]
        );
    }

    /**
     * Ambil satu soal berdasarkan ID (untuk form edit guru)
     */
    public function getQuestionById(int $id): ?array {
        return $this->db->fetch(
            "SELECT * FROM quiz_questions WHERE id = :id",
            [':id' => $id]
        );
    }

    /**
     * Tambah soal kuis baru (Guru & Admin)
     */
    public function addQuestion(array $data): int {
        if (!Auth::canManageMateri()) {
            throw new Exception("Hanya Guru atau Administrator yang berhak menambah soal kuis.");
        }

        $question = trim($data['question'] ?? '');
        $optionA = trim($data['option_a'] ?? '');
        $optionB = trim($data['option_b'] ?? '');
        $optionC = trim($data['option_c'] ?? '');
        $optionD = trim($data['option_d'] ?? '');
        $correctIndex = (int)($data['correct_index'] ?? 0);
        $timeLimit = (int)($data['time_limit'] ?? 20);
        $points = (int)($data['points'] ?? 1000);

        if (empty($question) || empty($optionA) || empty($optionB) || empty($optionC) || empty($optionD)) {
            throw new Exception("Teks pertanyaan dan semua 4 pilihan jawaban (A, B, C, D) wajib diisi.");
        }

        if ($correctIndex < 0 || $correctIndex > 3) {
            $correctIndex = 0;
        }

        // Tentukan nomor urut berikutnya
        $maxOrder = $this->db->fetch("SELECT MAX(order_num) as max_ord FROM quiz_questions");
        $nextOrder = ((int)($maxOrder['max_ord'] ?? 0)) + 1;

        $newId = $this->db->insert('quiz_questions', [
            'order_num'     => $nextOrder,
            'question'      => $question,
            'option_a'      => $optionA,
            'option_b'      => $optionB,
            'option_c'      => $optionC,
            'option_d'      => $optionD,
            'correct_index' => $correctIndex,
            'time_limit'    => $timeLimit,
            'points'        => $points
        ]);

        Activity::log("Menambahkan soal kuis baru nomor #{$nextOrder}: " . mb_substr($question, 0, 50));
        return $newId;
    }

    /**
     * Perbarui soal dan jawaban kuis (Guru & Admin)
     */
    public function updateQuestion(int $id, array $data): bool {
        if (!Auth::canManageMateri()) {
            throw new Exception("Hanya Guru atau Administrator yang berhak mengubah soal kuis.");
        }

        $existing = $this->getQuestionById($id);
        if (!$existing) {
            throw new Exception("Soal kuis dengan ID #{$id} tidak ditemukan.");
        }

        $question = trim($data['question'] ?? $existing['question']);
        $optionA = trim($data['option_a'] ?? $existing['option_a']);
        $optionB = trim($data['option_b'] ?? $existing['option_b']);
        $optionC = trim($data['option_c'] ?? $existing['option_c']);
        $optionD = trim($data['option_d'] ?? $existing['option_d']);
        $correctIndex = isset($data['correct_index']) ? (int)$data['correct_index'] : (int)$existing['correct_index'];
        $timeLimit = isset($data['time_limit']) ? (int)$data['time_limit'] : (int)$existing['time_limit'];
        $points = isset($data['points']) ? (int)$data['points'] : (int)$existing['points'];
        $orderNum = isset($data['order_num']) ? (int)$data['order_num'] : (int)$existing['order_num'];

        if (empty($question) || empty($optionA) || empty($optionB) || empty($optionC) || empty($optionD)) {
            throw new Exception("Teks pertanyaan dan semua pilihan jawaban wajib diisi.");
        }

        $this->db->query(
            "UPDATE quiz_questions 
             SET order_num = :order_num,
                 question = :question,
                 option_a = :option_a,
                 option_b = :option_b,
                 option_c = :option_c,
                 option_d = :option_d,
                 correct_index = :correct_index,
                 time_limit = :time_limit,
                 points = :points
             WHERE id = :id",
            [
                ':id'            => $id,
                ':order_num'     => $orderNum,
                ':question'      => $question,
                ':option_a'      => $optionA,
                ':option_b'      => $optionB,
                ':option_c'      => $optionC,
                ':option_d'      => $optionD,
                ':correct_index' => $correctIndex,
                ':time_limit'    => $timeLimit,
                ':points'        => $points
            ]
        );

        Activity::log("Memperbarui soal kuis nomor #{$orderNum}: " . mb_substr($question, 0, 50));
        return true;
    }

    /**
     * Hapus soal kuis (Guru & Admin)
     */
    public function deleteQuestion(int $id): bool {
        if (!Auth::canManageMateri()) {
            throw new Exception("Hanya Guru atau Administrator yang berhak menghapus soal kuis.");
        }

        $existing = $this->getQuestionById($id);
        if (!$existing) {
            throw new Exception("Soal tidak ditemukan.");
        }

        $this->db->query("DELETE FROM quiz_questions WHERE id = :id", [':id' => $id]);

        // Re-index urutan order_num agar berurutan rapi 1..N
        $all = $this->getQuestions();
        $ord = 1;
        foreach ($all as $q) {
            $this->db->query("UPDATE quiz_questions SET order_num = :ord WHERE id = :id", [
                ':ord' => $ord++,
                ':id'  => $q['id']
            ]);
        }

        Activity::log("Menghapus soal kuis: " . mb_substr($existing['question'], 0, 50));
        return true;
    }
}
