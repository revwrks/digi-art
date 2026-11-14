-- =====================================================================
-- Digi-Art: Script Update Terakhir (Idempoten & Aman Dijalankan)
-- Kompatibel dengan MariaDB / MySQL di phpMyAdmin InfinityFree
-- =====================================================================

-- 1. Pengaturan Judul, Bab, Deskripsi Materi, dan Status Mode Demo
INSERT INTO site_settings (setting_key, setting_value) VALUES 
('materi_bab', 'BAB 1 • SENI RUPA KELAS VIII'),
('materi_title', 'Menggambar Ilustrasi untuk Mata Pelajaran Lain'),
('materi_description', 'Pelajari konsep, fungsi, jenis, objek visual, hingga langkah praktis berkarya ilustrasi lintas mata pelajaran.'),
('demo_mode', '1')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- 2. Pastikan tabel studio_drafts tersedia (jika belum dibuat di update sebelumnya)
CREATE TABLE IF NOT EXISTS studio_drafts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    draft_data LONGTEXT NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_draft (user_id),
    CONSTRAINT fk_draft_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
