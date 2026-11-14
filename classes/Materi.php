<?php
/**
 * Materi Management (OOP Deep Module)
 * Mengelola materi dengan sistem template fleksibel (cards, list, prose, steps, on/off toggles),
 * penambahan/pengurangan nomor materi secara dinamis, pengubahan isi, dan dukungan gambar opsional.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Activity.php';

class Materi {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Ambil informasi header materi (Bab, Judul Utama, dan Deskripsi)
     */
    public function getHeader(): array {
        $default = [
            'bab'         => 'BAB 1 • SENI RUPA KELAS VIII',
            'title'       => 'Menggambar Ilustrasi untuk Mata Pelajaran Lain',
            'description' => 'Pelajari konsep, fungsi, jenis, objek visual, hingga langkah praktis berkarya ilustrasi lintas mata pelajaran.'
        ];

        try {
            $rows = $this->db->fetchAll(
                "SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('materi_bab', 'materi_title', 'materi_description')"
            );
            $map = [];
            foreach ($rows as $r) {
                $map[$r['setting_key']] = $r['setting_value'];
            }
            return [
                'bab'         => !empty($map['materi_bab']) ? $map['materi_bab'] : $default['bab'],
                'title'       => !empty($map['materi_title']) ? $map['materi_title'] : $default['title'],
                'description' => !empty($map['materi_description']) ? $map['materi_description'] : $default['description']
            ];
        } catch (Exception $e) {
            return $default;
        }
    }

    /**
     * Update informasi header materi (Bab, Judul Utama, dan Deskripsi)
     * Hanya Guru dan Administrator
     */
    public function updateHeader(array $data): array {
        if (!Auth::canManageMateri()) {
            return ['success' => false, 'message' => 'Hanya Guru dan Admin yang dapat mengubah informasi materi.'];
        }

        $bab   = trim($data['bab'] ?? '');
        $title = trim($data['title'] ?? '');
        $desc  = trim($data['description'] ?? '');

        if (empty($title)) {
            return ['success' => false, 'message' => 'Judul materi wajib diisi.'];
        }
        if (empty($bab)) {
            $bab = 'BAB 1 • SENI RUPA KELAS VIII';
        }

        $settings = [
            'materi_bab'         => $bab,
            'materi_title'       => $title,
            'materi_description' => $desc
        ];

        foreach ($settings as $k => $v) {
            $this->db->query(
                "INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) 
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()",
                [$k, $v]
            );
        }

        $user = Auth::user();
        $updater = $user['full_name'] ?? 'Guru';
        Activity::log("Guru/Admin ({$updater}) memperbarui info materi: {$bab} - {$title}");

        return [
            'success' => true,
            'message' => 'Judul, bab, dan deskripsi materi berhasil diperbarui!',
            'header'  => $this->getHeader()
        ];
    }

    /**
     * Ambil semua materi aktif untuk siswa/guru
     */
    public function getAllActive(): array {
        $sections = $this->db->fetchAll(
            "SELECT * FROM materi_sections WHERE is_active = 1 ORDER BY section_number ASC"
        );

        foreach ($sections as &$sec) {
            $sec['content'] = json_decode($sec['content_json'], true) ?: [];
        }
        return $sections;
    }

    /**
     * Ambil semua materi (untuk dashboard guru, termasuk draft)
     */
    public function getAllForTeacher(): array {
        $sections = $this->db->fetchAll(
            "SELECT * FROM materi_sections ORDER BY section_number ASC"
        );
        foreach ($sections as &$sec) {
            $sec['content'] = json_decode($sec['content_json'], true) ?: [];
        }
        return $sections;
    }

    /**
     * Ambil 1 section berdasarkan ID
     */
    public function getById(int $id): ?array {
        $sec = $this->db->fetch("SELECT * FROM materi_sections WHERE id = :id", [':id' => $id]);
        if ($sec) {
            $sec['content'] = json_decode($sec['content_json'], true) ?: [];
        }
        return $sec;
    }

    /**
     * Tambah Nomor Materi Baru (Guru & Admin)
     */
    public function addSection(array $data): array {
        if (!Auth::canManageMateri()) {
            return ['success' => false, 'message' => 'Hanya Guru dan Admin yang dapat menambah materi.'];
        }

        $title = trim($data['title'] ?? '');
        if (empty($title)) {
            return ['success' => false, 'message' => 'Judul materi wajib diisi.'];
        }

        $subtitle     = trim($data['subtitle'] ?? '');
        $displayMode  = in_array($data['display_mode'] ?? '', ['cards', 'list', 'prose', 'steps']) 
                        ? $data['display_mode'] 
                        : 'cards';
        $showCards    = isset($data['show_cards']) ? (int)(bool)$data['show_cards'] : 1;
        $imageUrl     = trim($data['image_url'] ?? '');
        $sourceNote   = trim($data['source_note'] ?? '');
        $isActive     = isset($data['is_active']) ? (int)(bool)$data['is_active'] : 1;

        // Tentukan nomor materi otomatis (berikutnya) jika tidak ditentukan
        $sectionNumber = isset($data['section_number']) && (int)$data['section_number'] > 0 
                         ? (int)$data['section_number'] 
                         : 0;
        if ($sectionNumber === 0) {
            $maxSec = $this->db->fetch("SELECT MAX(section_number) as max_num FROM materi_sections");
            $sectionNumber = ((int)($maxSec['max_num'] ?? 0)) + 1;
        }

        // Olah konten
        $contentJson = '[]';
        if (isset($data['content_json']) && !empty($data['content_json'])) {
            $contentJson = is_string($data['content_json']) ? $data['content_json'] : json_encode($data['content_json']);
        } elseif (isset($data['content']) && is_array($data['content'])) {
            $contentJson = json_encode($data['content']);
        } elseif (!empty($data['prose_text'])) {
            // Konversi dari textarea teks biasa jika user memilih mode prosa
            $contentJson = json_encode([['title' => $title, 'desc' => trim($data['prose_text'])]]);
        }

        $user = Auth::user();
        $newId = $this->db->insert('materi_sections', [
            'section_number' => $sectionNumber,
            'title'          => $title,
            'subtitle'       => $subtitle,
            'display_mode'   => $displayMode,
            'show_cards'     => $showCards,
            'image_url'      => !empty($imageUrl) ? $imageUrl : null,
            'content_json'   => $contentJson,
            'source_note'    => !empty($sourceNote) ? $sourceNote : null,
            'is_active'      => $isActive,
            'updated_by'     => $user['id'] ?? null
        ]);

        Activity::log("Menambahkan materi baru no. {$sectionNumber}: {$title}");

        return [
            'success' => true,
            'message' => "Materi baru '{$title}' berhasil ditambahkan ke urutan no. {$sectionNumber}!",
            'id'      => $newId
        ];
    }

    /**
     * Hapus / Kurangi Nomor Materi (Guru & Admin)
     */
    public function deleteSection(int $id): array {
        if (!Auth::canManageMateri()) {
            return ['success' => false, 'message' => 'Hanya Guru dan Admin yang dapat menghapus materi.'];
        }

        $existing = $this->getById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Materi tidak ditemukan.'];
        }

        $this->db->delete('materi_sections', 'id = :id', [':id' => $id]);
        Activity::log("Menghapus materi no. {$existing['section_number']}: {$existing['title']}");

        return [
            'success' => true,
            'message' => "Materi no. {$existing['section_number']} ('{$existing['title']}') berhasil dihapus."
        ];
    }

    /**
     * Update konfigurasi tampilan dan isi materi (Hanya Guru & Admin)
     */
    public function updateSection(int $id, array $data): array {
        if (!Auth::canManageMateri()) {
            return ['success' => false, 'message' => 'Hanya Guru dan Admin yang dapat mengubah materi.'];
        }

        $existing = $this->getById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Bagian materi tidak ditemukan.'];
        }

        $title          = trim($data['title'] ?? $existing['title']);
        $subtitle       = trim($data['subtitle'] ?? $existing['subtitle']);
        $sectionNumber  = isset($data['section_number']) && (int)$data['section_number'] > 0 
                          ? (int)$data['section_number'] 
                          : $existing['section_number'];
        $displayMode    = in_array($data['display_mode'] ?? '', ['cards', 'list', 'prose', 'steps']) 
                          ? $data['display_mode'] 
                          : $existing['display_mode'];
        $showCards      = isset($data['show_cards']) ? (int)(bool)$data['show_cards'] : $existing['show_cards'];
        $sourceNote     = isset($data['source_note']) ? trim($data['source_note']) : $existing['source_note'];
        $isActive       = isset($data['is_active']) ? (int)(bool)$data['is_active'] : $existing['is_active'];
        $imageUrl       = isset($data['image_url']) ? trim($data['image_url']) : ($existing['image_url'] ?? null);

        // Konten JSON (bisa berupa string JSON atau array PHP)
        $contentJson = $existing['content_json'];
        if (isset($data['content_json'])) {
            $contentJson = is_string($data['content_json']) ? $data['content_json'] : json_encode($data['content_json']);
        } elseif (isset($data['content'])) {
            $contentJson = json_encode($data['content']);
        } elseif (isset($data['prose_text'])) {
            $contentJson = json_encode([['title' => $title, 'desc' => trim($data['prose_text'])]]);
        }

        $user = Auth::user();
        $this->db->update('materi_sections', [
            'section_number' => $sectionNumber,
            'title'          => $title,
            'subtitle'       => $subtitle,
            'display_mode'   => $displayMode,
            'show_cards'     => $showCards,
            'image_url'      => !empty($imageUrl) ? $imageUrl : null,
            'content_json'   => $contentJson,
            'source_note'    => $sourceNote,
            'is_active'      => $isActive,
            'updated_by'     => $user['id'] ?? null
        ], 'id = :id', [':id' => $id]);

        Activity::log("Mengubah materi no. {$sectionNumber}: {$title}");

        return [
            'success' => true,
            'message' => "Materi '{$title}' berhasil diperbarui!",
            'section' => $this->getById($id)
        ];
    }

    /**
     * Cepat toggle tampilan card on/off pada materi nomor tertentu
     */
    public function toggleCards(int $id, bool $showCards): array {
        if (!Auth::canManageMateri()) {
            return ['success' => false, 'message' => 'Akses ditolak.'];
        }

        $sec = $this->getById($id);
        if (!$sec) {
            return ['success' => false, 'message' => 'Materi tidak ditemukan.'];
        }

        $val = $showCards ? 1 : 0;
        $this->db->update('materi_sections', ['show_cards' => $val], 'id = :id', [':id' => $id]);
        
        $statusText = $showCards ? 'diaktifkan' : 'dinonaktifkan';
        Activity::log("Mengubah mode kartu materi {$sec['section_number']}: {$statusText}");

        return [
            'success'    => true,
            'message'    => "Tampilan kartu untuk '{$sec['title']}' {$statusText}!",
            'show_cards' => $val
        ];
    }
}
