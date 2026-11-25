<?php
/**
 * StudioDraft Model
 * Mengelola penyimpanan draf kanvas Studio Digital per akun pengguna (Cloud Database Sync).
 */

require_once __DIR__ . '/Database.php';

class StudioDraft {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureTableExists();
    }

    /**
     * Pastikan tabel studio_drafts telah dibuat secara otomatis
     */
    private function ensureTableExists(): void {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS studio_drafts (
                user_id INT PRIMARY KEY,
                draft_data LONGTEXT NOT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_studio_drafts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            $this->db->exec($sql);
        } catch (PDOException $e) {
            error_log('[Digi-Art] StudioDraft::ensureTableExists failed: ' . $e->getMessage());
        }
    }

    /**
     * Simpan atau perbarui draf kanvas untuk akun tertentu
     */
    public function saveDraft(int $userId, string $draftData): array {
        if ($userId <= 0) {
            return ['success' => false, 'message' => 'User ID tidak valid.'];
        }
        if (empty($draftData)) {
            return ['success' => false, 'message' => 'Data draf tidak boleh kosong.'];
        }
        if (strlen($draftData) > 4 * 1024 * 1024) {
            return ['success' => false, 'message' => 'Ukuran draf melebihi batas maksimal 4MB.'];
        }

        json_decode($draftData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'message' => 'Format data draf tidak valid (harus JSON).'];
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO studio_drafts (user_id, draft_data, updated_at) 
                VALUES (:uid, :data, NOW()) 
                ON DUPLICATE KEY UPDATE draft_data = :data2, updated_at = NOW()
            ");
            $stmt->execute([
                ':uid'   => $userId,
                ':data'  => $draftData,
                ':data2' => $draftData
            ]);

            return [
                'success'    => true,
                'message'    => 'Draf berhasil disimpan di database akun.',
                'updated_at' => date('Y-m-d H:i:s')
            ];
        } catch (PDOException $e) {
            error_log('[Digi-Art] StudioDraft::saveDraft failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menyimpan draf ke database. Silakan coba lagi.'];
        }
    }

    /**
     * Ambil draf kanvas akun pengguna
     */
    public function getDraft(int $userId): ?array {
        if ($userId <= 0) {
            return null;
        }

        try {
            $stmt = $this->db->prepare("SELECT user_id, draft_data, updated_at FROM studio_drafts WHERE user_id = :uid LIMIT 1");
            $stmt->execute([':uid' => $userId]);
            $res = $stmt->fetch();
            return $res ?: null;
        } catch (PDOException $e) {
            error_log('[Digi-Art] StudioDraft::getDraft failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Hapus draf akun pengguna (misal saat klik Lembar Baru)
     */
    public function clearDraft(int $userId): bool {
        if ($userId <= 0) {
            return false;
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM studio_drafts WHERE user_id = :uid");
            return $stmt->execute([':uid' => $userId]);
        } catch (PDOException $e) {
            error_log('[Digi-Art] StudioDraft::clearDraft failed: ' . $e->getMessage());
            return false;
        }
    }
}
