<?php
/**
 * Activity Logger Class
 */

require_once __DIR__ . '/Database.php';

class Activity {
    public static function log(string $action, ?int $userId = null, ?string $userName = null, ?string $role = null): void {
        try {
            $db = Database::getInstance();
            if ($userId === null && isset($_SESSION['user'])) {
                $userId = $_SESSION['user']['id'];
                $userName = $_SESSION['user']['full_name'];
                $role = $_SESSION['user']['role'];
            }
            $db->insert('activity_logs', [
                'user_id'   => $userId,
                'user_name' => $userName ?: 'Pengunjung',
                'role'      => $role ?: 'tamu',
                'action'    => $action
            ]);

            // Batasi mentok 250 logs: hapus log lama jika melebihi kuota 250
            self::pruneLogs(250);
        } catch (Exception $e) {
            // Log silent fail agar tidak memutus flow utama
        }
    }

    /**
     * Hapus log aktivitas yang lebih lama dari 250 data terbaru
     */
    public static function pruneLogs(int $maxLogs = 250): void {
        try {
            $db = Database::getInstance();
            $countRes = $db->fetch("SELECT COUNT(*) AS total FROM activity_logs");
            $total = (int)($countRes['total'] ?? 0);
            if ($total > $maxLogs) {
                $db->query(
                    "DELETE FROM activity_logs 
                     WHERE id NOT IN (
                         SELECT id FROM (
                             SELECT id FROM activity_logs ORDER BY id DESC LIMIT {$maxLogs}
                         ) as keep_ids
                     )"
                );
            }
        } catch (Exception $e) {
            // Ignore error
        }
    }

    /**
     * Ambil data log dengan pagination (maksimal 250 log terbaru)
     */
    public static function getPaginated(int $page = 1, int $perPage = 15): array {
        self::pruneLogs(250);
        $db = Database::getInstance();
        $countRes = $db->fetch("SELECT COUNT(*) AS total FROM activity_logs");
        $total = min(250, (int)($countRes['total'] ?? 0));
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $logs = $db->fetchAll(
            "SELECT * FROM activity_logs ORDER BY id DESC LIMIT :limit OFFSET :offset",
            [':limit' => $perPage, ':offset' => $offset]
        );

        return [
            'logs'         => $logs,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'total_pages'  => $totalPages
        ];
    }

    public static function getRecent(int $limit = 50): array {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT * FROM activity_logs ORDER BY id DESC LIMIT :limit",
            [':limit' => min(250, $limit)]
        );
    }

    public static function getStats(): array {
        $db = Database::getInstance();
        $visitors = $db->fetch("SELECT COUNT(*) AS total FROM activity_logs");
        $works = $db->fetch("SELECT COUNT(*) AS total FROM pameran_karya WHERE status = 'approved'");
        $pendingWorks = $db->fetch("SELECT COUNT(*) AS total FROM pameran_karya WHERE status = 'pending'");
        $quizzes = $db->fetch("SELECT COUNT(*) AS total FROM quiz_scores");
        $students = $db->fetch("SELECT COUNT(*) AS total FROM users WHERE role = 'murid'");
        $teachers = $db->fetch("SELECT COUNT(*) AS total FROM users WHERE role IN ('guru', 'admin')");

        return [
            'visitors'     => (int)($visitors['total'] ?? 0),
            'works'        => (int)($works['total'] ?? 0),
            'pending_works'=> (int)($pendingWorks['total'] ?? 0),
            'quizzes'      => (int)($quizzes['total'] ?? 0),
            'students'     => (int)($students['total'] ?? 0),
            'teachers'     => (int)($teachers['total'] ?? 0)
        ];
    }
}
