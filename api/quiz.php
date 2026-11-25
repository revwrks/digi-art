<?php
/**
 * API: Quiz Kahoot-Style Endpoints
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Security.php';
require_once __DIR__ . '/../classes/Quiz.php';

Security::sendSecurityHeaders();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$rawBody = file_get_contents('php://input');
if ($rawBody === false) {
    $rawBody = '';
}
$input = json_decode($rawBody, true) ?: $_POST;

$quizModel = new Quiz();

switch ($action) {
    case 'get_questions':
        // Terkunci hanya untuk Guru dan Murid
        if (!Auth::canAccessProtectedFeatures()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'locked'  => true,
                'message' => 'Kuis hanya dapat diakses oleh akun Siswa dan Guru. Silakan login terlebih dahulu.'
            ]);
            exit;
        }
        $questions = $quizModel->getQuestionsForPlay();
        echo json_encode(['success' => true, 'questions' => $questions]);
        break;

    case 'check_answer':
        Security::requireMethod('POST');
        Security::requireCsrf($rawBody);
        if (!Auth::canAccessProtectedFeatures()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
            exit;
        }
        $res = $quizModel->checkAnswer(
            (int)($input['question_id'] ?? 0),
            (int)($input['chosen_index'] ?? -1),
            (int)($input['time_left'] ?? 0)
        );
        echo json_encode($res);
        break;

    case 'submit_score':
        Security::requireMethod('POST');
        Security::requireCsrf($rawBody);
        if (!Auth::canAccessProtectedFeatures()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
            exit;
        }
        $res = $quizModel->submitVerifiedScore();
        echo json_encode($res);
        break;

    case 'get_leaderboard':
        $limit = (int)($_GET['limit'] ?? 20);
        $leaders = $quizModel->getLeaderboard($limit);
        echo json_encode(['success' => true, 'leaderboard' => $leaders]);
        break;

    case 'admin_list':
        if (!Auth::canManageMateri()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Hanya Guru atau Admin yang berhak mengelola kuis.']);
            exit;
        }
        $questions = $quizModel->getQuestions();
        echo json_encode(['success' => true, 'questions' => $questions]);
        break;

    case 'get_question':
        if (!Auth::canManageMateri()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
            exit;
        }
        $id = (int)($_GET['id'] ?? 0);
        $q = $quizModel->getQuestionById($id);
        if (!$q) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Soal tidak ditemukan.']);
            exit;
        }
        echo json_encode(['success' => true, 'question' => $q]);
        break;

    case 'add_question':
        Security::requireMethod('POST');
        Security::requireCsrf($rawBody);
        if (!Auth::canManageMateri()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
            exit;
        }
        try {
            $newId = $quizModel->addQuestion($input);
            echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Soal kuis baru berhasil ditambahkan!']);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update_question':
        Security::requireMethod('POST');
        Security::requireCsrf($rawBody);
        if (!Auth::canManageMateri()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
            exit;
        }
        $id = (int)($input['id'] ?? 0);
        try {
            $quizModel->updateQuestion($id, $input);
            echo json_encode(['success' => true, 'message' => 'Soal dan pilihan jawaban kuis berhasil diperbarui!']);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_question':
        Security::requireMethod('POST');
        Security::requireCsrf($rawBody);
        if (!Auth::canManageMateri()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
            exit;
        }
        $id = (int)($input['id'] ?? 0);
        try {
            $quizModel->deleteQuestion($id);
            echo json_encode(['success' => true, 'message' => 'Soal kuis berhasil dihapus.']);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi kuis tidak valid.']);
        break;
}
