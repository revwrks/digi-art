<?php
/**
 * Karya / Pameran Management (OOP Deep Module)
 * Mengelola pengunggahan karya siswa dan sistem persetujuan (approval) oleh guru/admin.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Activity.php';
require_once __DIR__ . '/Security.php';

class Karya {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Ambil karya yang sudah DISETUJUI (Approved) untuk Galeri Pameran Publik beserta kuratornya
     */
    public function getApproved(int $limit = 60): array {
        $works = $this->db->fetchAll(
            "SELECT pk.*, u.full_name AS curator_name, u.role AS curator_role 
             FROM pameran_karya pk 
             LEFT JOIN users u ON pk.approved_by = u.id 
             WHERE pk.status = 'approved' 
             ORDER BY pk.id DESC LIMIT :limit",
            [':limit' => $limit]
        );
        foreach ($works as &$w) {
            if (empty($w['curator_name'])) {
                $w['curator_name'] = 'Ibu Nurul Hidayah, S.Pd';
                $w['curator_role'] = 'guru';
            }
        }
        return $works;
    }

    /**
     * Ambil semua karya untuk manajemen Guru/Admin (Pending, Approved, Rejected)
     */
    public function getAllForTeacher(?string $status = null): array {
        if (!Auth::canManagePameran()) {
            return [];
        }

        $sql = "SELECT pk.*, u.full_name AS curator_name, u.role AS curator_role 
                FROM pameran_karya pk 
                LEFT JOIN users u ON pk.approved_by = u.id ";

        if ($status && in_array($status, ['pending', 'approved', 'rejected'])) {
            $sql .= "WHERE pk.status = :st ORDER BY pk.id DESC";
            return $this->db->fetchAll($sql, [':st' => $status]);
        }

        $sql .= "ORDER BY (pk.status = 'pending') DESC, pk.id DESC";
        return $this->db->fetchAll($sql);
    }

    /**
     * Kirim karya baru (status otomatis 'pending')
     */
    public function submitWork(array $data): array {
        $title       = trim($data['title'] ?? '');
        $creatorName = trim($data['creator_name'] ?? '');
        $kelas       = trim($data['kelas'] ?? '');
        $description = trim($data['description'] ?? '');
        $imagePath   = trim($data['image_path'] ?? '');

        if (empty($title) || empty($creatorName) || empty($imagePath)) {
            return ['success' => false, 'message' => 'Judul karya, nama pembuat, dan gambar karya wajib diisi.'];
        }

        if (!Security::isSafeUploadPath($imagePath)) {
            return ['success' => false, 'message' => 'Path gambar karya tidak valid. Silakan unggah gambar melalui formulir resmi.'];
        }

        $user = Auth::user();
        $userId = $user['id'] ?? null;

        $newId = $this->db->insert('pameran_karya', [
            'user_id'      => $userId,
            'title'        => $title,
            'creator_name' => $creatorName,
            'kelas'        => $kelas ?: 'VIII',
            'description'  => $description,
            'image_path'   => $imagePath,
            'status'       => 'pending',
            'likes_count'  => 0
        ]);

        Activity::log("Mengirim karya baru (menunggu persetujuan): {$title} oleh {$creatorName}", $userId, $creatorName);

        return [
            'success' => true,
            'message' => 'Karya berhasil dikirim! Karya akan tampil di galeri pameran setelah disetujui oleh Guru.',
            'id'      => $newId
        ];
    }

    /**
     * Approve karya siswa (Hanya Guru & Admin)
     */
    public function approve(int $id): array {
        if (!Auth::canManagePameran()) {
            return ['success' => false, 'message' => 'Hanya Guru dan Admin yang dapat menyetujui karya.'];
        }

        $karya = $this->db->fetch("SELECT * FROM pameran_karya WHERE id = :id", [':id' => $id]);
        if (!$karya) {
            return ['success' => false, 'message' => 'Karya tidak ditemukan.'];
        }

        $user = Auth::user();
        $this->db->update('pameran_karya', [
            'status'      => 'approved',
            'approved_at' => date('Y-m-d H:i:s'),
            'approved_by' => $user['id'] ?? null
        ], 'id = :id', [':id' => $id]);

        Activity::log("Menyetujui karya: '{$karya['title']}' karya {$karya['creator_name']}");

        return [
            'success' => true,
            'message' => "Karya '{$karya['title']}' berhasil disetujui dan kini tampil di Pameran!"
        ];
    }

    /**
     * Tolak karya siswa (Hanya Guru & Admin)
     */
    public function reject(int $id): array {
        if (!Auth::canManagePameran()) {
            return ['success' => false, 'message' => 'Akses ditolak.'];
        }

        $karya = $this->db->fetch("SELECT * FROM pameran_karya WHERE id = :id", [':id' => $id]);
        if (!$karya) {
            return ['success' => false, 'message' => 'Karya tidak ditemukan.'];
        }

        $this->db->update('pameran_karya', ['status' => 'rejected'], 'id = :id', [':id' => $id]);
        Activity::log("Menolak karya: '{$karya['title']}'");

        return ['success' => true, 'message' => "Karya '{$karya['title']}' ditolak."];
    }

    /**
     * Hapus karya (Hanya Guru & Admin)
     */
    public function delete(int $id): array {
        if (!Auth::canManagePameran()) {
            return ['success' => false, 'message' => 'Akses ditolak.'];
        }

        $karya = $this->db->fetch("SELECT * FROM pameran_karya WHERE id = :id", [':id' => $id]);
        if ($karya) {
            // Hapus file gambar jika ada di uploads/ (dengan verifikasi realpath ketat)
            if (!empty($karya['image_path']) && !str_starts_with($karya['image_path'], 'assets/') && !str_starts_with($karya['image_path'], 'http')) {
                $realUploadDir = realpath(UPLOAD_DIR);
                $candidatePath = __DIR__ . '/../' . ltrim($karya['image_path'], '/\\');
                $realFile = realpath($candidatePath);
                if (
                    $realUploadDir !== false &&
                    $realFile !== false &&
                    str_starts_with($realFile, $realUploadDir . DIRECTORY_SEPARATOR) &&
                    is_file($realFile)
                ) {
                    @unlink($realFile);
                }
            }
            $this->db->delete('pameran_karya', 'id = :id', [':id' => $id]);
            Activity::log("Menghapus karya: '{$karya['title']}'");
        }

        return ['success' => true, 'message' => 'Karya berhasil dihapus.'];
    }

    /**
     * Beri Apresiasi (Like) pada karya yang sudah disetujui dengan deduplikasi per sesi
     */
    public function like(int $id): array {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'ID karya tidak valid.'];
        }

        $karya = $this->db->fetch(
            "SELECT id, likes_count, status FROM pameran_karya WHERE id = :id",
            [':id' => $id]
        );
        if (!$karya || ($karya['status'] ?? '') !== 'approved') {
            return ['success' => false, 'message' => 'Karya tidak ditemukan atau belum disetujui.'];
        }

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        if (!isset($_SESSION['liked_works']) || !is_array($_SESSION['liked_works'])) {
            $_SESSION['liked_works'] = [];
        }

        if (!empty($_SESSION['liked_works'][$id])) {
            return [
                'success'       => true,
                'likes'         => (int)$karya['likes_count'],
                'already_liked' => true
            ];
        }

        $this->db->query(
            "UPDATE pameran_karya SET likes_count = likes_count + 1 WHERE id = :id AND status = 'approved'",
            [':id' => $id]
        );
        $_SESSION['liked_works'][$id] = true;

        $updated = $this->db->fetch("SELECT likes_count FROM pameran_karya WHERE id = :id", [':id' => $id]);
        return ['success' => true, 'likes' => (int)($updated['likes_count'] ?? $karya['likes_count'])];
    }
}
