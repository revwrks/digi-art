-- =====================================================================
-- Digi-Art: Database Schema & Seed Data
-- Siap Ekspor & Kompatibel Penuh untuk Hosting Gratis (InfinityFree / vPanel)
-- Kompatibel dengan MariaDB 10.x / MySQL 5.7+ / MySQL 8.0+
-- CATATAN: JANGAN tambahkan CREATE DATABASE / USE karena InfinityFree 
-- membuat nama database otomatis (misal: epiz_XXXX_digi_art).
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Hapus tabel lama jika sudah ada (Reverse Dependency Order)
DROP TABLE IF EXISTS studio_drafts;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS quiz_scores;
DROP TABLE IF EXISTS quiz_questions;
DROP TABLE IF EXISTS pameran_karya;
DROP TABLE IF EXISTS materi_sections;
DROP TABLE IF EXISTS referensi_cache;
DROP TABLE IF EXISTS site_settings;
DROP TABLE IF EXISTS users;

-- =====================================================================
-- 1. TABEL USERS (Autentikasi Akun: Admin, Guru, Murid)
-- =====================================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'guru', 'murid') NOT NULL DEFAULT 'murid',
    kelas VARCHAR(20) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 2. TABEL MATERI_SECTIONS (Materi Dinamis & Fleksibel Kurikulum Seni Rupa)
-- =====================================================================
CREATE TABLE materi_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_number INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    subtitle VARCHAR(255) DEFAULT NULL,
    display_mode ENUM('cards', 'list', 'prose', 'steps') NOT NULL DEFAULT 'cards',
    show_cards TINYINT(1) NOT NULL DEFAULT 1,
    image_url VARCHAR(255) DEFAULT NULL,
    content_json LONGTEXT NOT NULL,
    source_note TEXT DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_by INT DEFAULT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 3. TABEL PAMERAN_KARYA (Galeri Hasil Karya & Kurasi Guru/Admin)
-- =====================================================================
CREATE TABLE pameran_karya (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    title VARCHAR(150) NOT NULL,
    creator_name VARCHAR(100) NOT NULL,
    kelas VARCHAR(30) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    image_path VARCHAR(255) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    likes_count INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME DEFAULT NULL,
    approved_by INT DEFAULT NULL,
    CONSTRAINT fk_pameran_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 4. TABEL QUIZ_QUESTIONS (Bank Soal Interaktif Format Kahoot)
-- =====================================================================
CREATE TABLE quiz_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_num INT NOT NULL DEFAULT 1,
    question TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    correct_index TINYINT NOT NULL COMMENT '0=A, 1=B, 2=C, 3=D',
    time_limit INT NOT NULL DEFAULT 20,
    points INT NOT NULL DEFAULT 1000,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 5. TABEL QUIZ_SCORES (Leaderboard & Riwayat Skor Kuis Siswa)
-- =====================================================================
CREATE TABLE quiz_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    student_name VARCHAR(100) NOT NULL,
    kelas VARCHAR(30) DEFAULT NULL,
    total_score INT NOT NULL DEFAULT 0,
    correct_count INT NOT NULL DEFAULT 0,
    total_questions INT NOT NULL DEFAULT 10,
    highest_streak INT NOT NULL DEFAULT 0,
    completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_scores_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 6. TABEL ACTIVITY_LOGS (Audit Trail Aktivitas Sistem)
-- =====================================================================
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    user_name VARCHAR(100) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'tamu',
    action VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 7. TABEL REFERENSI_CACHE (Cache Hasil Pencarian Referensi Gambar Edukasi)
-- =====================================================================
CREATE TABLE referensi_cache (
    query_hash VARCHAR(64) PRIMARY KEY,
    query_text VARCHAR(150) NOT NULL,
    results_json LONGTEXT NOT NULL,
    total_items INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 8. TABEL SITE_SETTINGS (Dekorasi & Pengaturan Fleksibel Beranda)
-- =====================================================================
CREATE TABLE site_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value LONGTEXT NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 9. TABEL STUDIO_DRAFTS (Penyimpanan Draf Kanvas Studio Digital per Akun)
-- =====================================================================
CREATE TABLE studio_drafts (
    user_id INT PRIMARY KEY,
    draft_data LONGTEXT NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_studio_drafts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SEED DATA AWAL (Akun Bawaan, Materi Kurikulum, Soal, Pameran & Setting)
-- =====================================================================

-- 1. Akun Pengguna Bawaan:
-- Admin: admin / admin123
-- Guru : guru  / guru123
-- Murid: murid / murid123
--        budi  / murid123
--        ratna / murid123
INSERT INTO users (username, password, full_name, role, kelas) VALUES
('admin', '$2y$10$ToV8osbXhfJ5KdXWPuNrj.M.exZE3oyuJxV5jj.xfEEQOqwd5UKIq', 'Administrator Digi-Art', 'admin', NULL),
('guru', '$2y$10$LheEojRmUUFsjHo.Y7K/jOju7aQ0Uf787SWqSSsWpmnaOxRuCHjjG', 'Ibu Nurul Hidayah, S.Pd', 'guru', NULL),
('murid', '$2y$10$FDpK3hgUUIb41wSBqqNU/uMEOWcddOgaBZhcA4M6Y0AebsGeyEMh.', 'Siti Aisyah', 'murid', 'VIII A'),
('budi', '$2y$10$FDpK3hgUUIb41wSBqqNU/uMEOWcddOgaBZhcA4M6Y0AebsGeyEMh.', 'Budi Pratama', 'murid', 'VIII A'),
('ratna', '$2y$10$FDpK3hgUUIb41wSBqqNU/uMEOWcddOgaBZhcA4M6Y0AebsGeyEMh.', 'Ratna Dewi', 'murid', 'VIII B');

-- 2. Data Materi Pembelajaran Seni Rupa (Menggambar Ilustrasi untuk Mapel Lain)
INSERT INTO materi_sections (section_number, title, subtitle, display_mode, show_cards, content_json, source_note, is_active) VALUES
(1, 'Pengertian Ilustrasi', 'Memahami dasar bahasa visual dalam pembelajaran', 'prose', 0, 
'{"paragraphs":["Ilustrasi adalah gambar atau bentuk visual yang digunakan untuk memperjelas, menerangkan, atau menyampaikan pesan sehingga informasi lebih mudah dipahami. Dalam konteks pembelajaran, ilustrasi dapat membantu mengubah informasi yang abstrak atau verbal menjadi gambaran yang lebih konkret.","Menggambar ilustrasi merupakan kegiatan membuat karya visual dua dimensi untuk menyampaikan gagasan, cerita, suasana, informasi, atau penjelasan tertentu. Karena itu, ilustrasi tidak hanya mengejar keindahan, tetapi juga memperhatikan keterbacaan pesan."]}',
'Panduan FLS3N SMP/MTs/Sederajat 2025 menjelaskan ilustrasi sebagai media yang membuat sesuatu lebih mudah dipahami melalui gambaran konkret dan menekankan fungsi komunikasi, pendidikan, informasi, serta ekspresi.', 1),

(2, 'Fungsi Ilustrasi', 'Peran penting gambar dalam memperkuat pemahaman', 'cards', 1,
'{"items":[{"tag":"01","title":"Menjelaskan","desc":"Memperjelas konsep, proses, objek, atau peristiwa yang sulit dipahami hanya melalui teks."},{"tag":"02","title":"Menginformasikan","desc":"Menyampaikan informasi secara visual agar pembaca dapat menangkap pesan penting dengan cepat."},{"tag":"03","title":"Mendidik","desc":"Membantu proses belajar melalui contoh visual, diagram, gambar cerita, atau representasi suatu konsep."},{"tag":"04","title":"Menghibur & Menarik","desc":"Visual yang menarik dapat meningkatkan perhatian dan membuat materi lebih menyenangkan."},{"tag":"05","title":"Menyampaikan Cerita","desc":"Ilustrasi dapat menggambarkan tokoh, latar, kejadian, dan suasana sehingga sebuah cerita lebih hidup."},{"tag":"06","title":"Ekspresi & Kreativitas","desc":"Ilustrator dapat menyampaikan gagasan dan sudut pandang melalui pilihan bentuk, warna, gaya, dan komposisi."}]}',
'Visual ilustrasi mempercepat pemahaman materi sains, sejarah, dan sastra hingga 60.000 kali lebih cepat dari teks.', 1),

(3, 'Jenis-Jenis Ilustrasi', 'Ragam bentuk dan gaya ilustrasi untuk berbagai kebutuhan', 'list', 0,
'{"items":[{"title":"Ilustrasi ilmiah / edukatif","desc":"Menjelaskan objek atau konsep, misalnya struktur gunung berapi, organ tubuh, peta sederhana, atau proses siklus air."},{"title":"Gambar cerita / naratif","desc":"Menggambarkan rangkaian kejadian atau suasana dalam cerita atau buku bacaan."},{"title":"Komik","desc":"Cerita visual yang memadukan gambar, panel urut, tokoh karakter, dan balon dialog."},{"title":"Kartun","desc":"Gambar dengan bentuk yang disederhanakan atau dilebihkan untuk menyampaikan pesan secara ringan dan humoris."},{"title":"Karikatur","desc":"Penggambaran tokoh atau objek dengan ciri tertentu yang sengaja ditonjolkan atau didistorsi."},{"title":"Ilustrasi dekoratif","desc":"Mengutamakan pengolahan bentuk dan ornamen untuk memperindah tampilan permukaan benda."}]}',
'e-Modul Seni Budaya Kemdikbud & Riset Jurnal Seni Rupa GARUDA.', 1),

(4, 'Objek dan Unsur Visual', 'Elemen-elemen dasar penyusun karya ilustrasi', 'prose', 0,
'{"paragraphs":["Objek ilustrasi dapat berupa manusia, hewan, tumbuhan, benda, bangunan, lingkungan, maupun simbol grafis.","Saat berkarya, perhatikan unsur-unsur dasar: titik, garis, bidang, bentuk, warna, tekstur, gelap-terang, komposisi, proporsi, dan perspektif. Pemilihan unsur harus membantu pesan utama terlihat jelas dan memikat pembaca."]}',
'Keseimbangan dan fokus pesan menjadi kunci utama keberhasilan ilustrasi edukatif.', 1),

(5, 'Menghubungkan Ilustrasi dengan Mapel Lain', 'Aplikasi lintas mata pelajaran', 'cards', 1,
'{"items":[{"tag":"IPS","title":"Ilmu Pengetahuan Sosial","desc":"Ilustrasikan gunung berapi, peta tematik, bentang alam geografi, aktivitas pasar ekonomi, atau peristiwa sejarah kemerdekaan."},{"tag":"IPA","title":"Ilmu Pengetahuan Alam","desc":"Ilustrasikan sistem organ manusia, rantai makanan ekosistem, siklus air presipitasi, tata surya, atau struktur sel biologi."},{"tag":"B. INDO","title":"Bahasa Indonesia","desc":"Ilustrasikan adegan penting dari cerita fabel, cerpen, perwatakan tokoh, latar suasana, atau urutan alur peristiwa."}]}',
'Integrasi STEAM (Science, Technology, Engineering, Arts, Math) mendorong pemikiran holistik siswa.', 1),

(6, 'Langkah-Langkah Berkarya', 'Proses terstruktur dari ide hingga karya akhir', 'steps', 1,
'{"items":[{"step":1,"title":"Tentukan Topik & Pesan","desc":"Identifikasi materi pelajaran yang ingin dijelaskan serta audiens target."},{"step":2,"title":"Kumpulkan Referensi","desc":"Cari inspirasi visual, foto nyata, dan skema di modul atau search engine gambar."},{"step":3,"title":"Thumbnail Sketch","desc":"Buat beberapa sketsa kecil berukuran kartu untuk mencari komposisi terbaik."},{"step":4,"title":"Pilih Sudut Pandang","desc":"Tentukan perspektif, fokus objek (center of interest), dan skala proporsi."},{"step":5,"title":"Perjelas Garis & Bentuk","desc":"Tebalkan garis luar (outline/line-art) dan rapikan detail bentuk objek."},{"step":6,"title":"Pewarnaan & Gelap Terang","desc":"Pilih palet warna yang serasi serta tambahkan arsiran/bayangan untuk dimensi."},{"step":7,"title":"Keterangan & Label","desc":"Sertakan judul, nama bagian, atau keterangan jika ilustrasi bersifat edukatif."},{"step":8,"title":"Evaluasi & Refleksi","desc":"Periksa apakah gambar sudah komunikatif, mudah dipahami, dan estetik."}]}',
'Siswa diharapkan mampu membuat karya ilustrasi yang komunikatif, kreatif, dan berdaya guna.', 1);

-- 3. Pertanyaan Kuis Format Kahoot
INSERT INTO quiz_questions (order_num, question, option_a, option_b, option_c, option_d, correct_index, time_limit, points) VALUES
(1, 'Ilustrasi digunakan terutama untuk…', 'Memperjelas atau menyampaikan pesan visual', 'Membuat karya selalu abstrak dan samar', 'Menghapus kebutuhan akan teks seluruhnya', 'Membuat gambar hiasan tanpa makna', 0, 20, 1000),
(2, 'Salah satu fungsi ilustrasi dalam pembelajaran adalah…', 'Menyulitkan pemahaman konsep materi', 'Membantu menjelaskan dan memperjelas info', 'Menghilangkan kreativitas imajinasi siswa', 'Membatasi sumber daya media belajar', 1, 20, 1000),
(3, 'Contoh ilustrasi edukatif yang tepat untuk mata pelajaran IPA adalah…', 'Diagram struktur lapisan gunung berapi', 'Logo komunitas motor', 'Foto selfie saat liburan', 'Pola dekoratif abstrak tanpa pesan', 0, 20, 1000),
(4, 'Komik termasuk jenis karya ilustrasi yang…', 'Menggunakan rangkaian panel visual berurutan', 'Hanya boleh memakai satu warna monokrom', 'Tidak pernah memiliki tokoh ataupun alur', 'Selalu berupa grafik persentase', 0, 20, 1000),
(5, 'Karikatur pada umumnya memiliki ciri khas berupa…', 'Ciri tertentu objek sengaja ditonjolkan/dilebihkan', 'Ukuran kanvas harus selalu persegi panjang', 'Wajib menggunakan cat minyak di kanvas linen', 'Garis dibuat tidak boleh melengkung', 0, 20, 1000),
(6, 'Saat membuat ilustrasi untuk mata pelajaran IPS, hal awal yang ditentukan adalah…', 'Topik materi dan pesan yang ingin disampaikan', 'Membeli kuas cat termahal di toko seni', 'Ukuran tanda tangan pembuat karya', 'Menentukan harga jual jika dipajang', 0, 20, 1000),
(7, 'Unsur visual seni rupa yang paling dominan membangun mood/suasana adalah…', 'Warna dan pencahayaan (gelap-terang)', 'Nomor absen siswa di pojok kanan', 'Daftar nama anggota kelompok', 'Ketebalan bingkai pigura kayu', 0, 20, 1000),
(8, 'Langkah setelah menentukan topik ilustrasi adalah…', 'Mengumpulkan referensi visual dan membuat sketsa ide', 'Langsung menghapus semua gagasan yang muncul', 'Mempublikasikan karya tanpa memeriksa pesan', 'Mengganti mata pelajaran yang dipilih', 0, 20, 1000),
(9, 'Ilustrasi yang berhasil untuk materi pelajaran memiliki ciri…', 'Komunikatif, mudah dipahami, dan sesuai konteks', 'Sangat rumit sehingga sulit dicerna siswa', 'Sama sekali tidak berkaitan dengan topik', 'Hanya menjiplak persis tanpa pemahaman', 0, 20, 1000),
(10, 'Sikap apresiasi yang positif terhadap karya seni rupa teman adalah…', 'Mengamati, memberi tanggapan santun dan konstruktif', 'Meniru tanpa izin dan mengakuinya sendiri', 'Menyembunyikan karya agar tidak dilihat orang lain', 'Mencoret-coret keterangan karya teman', 0, 20, 1000);

-- 4. Sampel Awal Pameran Karya Siswa (Approved)
INSERT INTO pameran_karya (user_id, title, creator_name, kelas, description, image_path, status, likes_count, approved_at) VALUES
(3, 'Gunung Merapi Meletus (Materi IPS)', 'Siti Aisyah', 'VIII A', 'Ilustrasi mitigasi bencana erupsi gunung berapi untuk mata pelajaran IPS Geografi. Dibuat dengan teknik digital brush.', 'assets/img/sample-volcano-art.png', 'approved', 14, NOW()),
(4, 'Siklus Hidrologi & Air Bersih (IPA)', 'Budi Pratama', 'VIII A', 'Diagram siklus air presipitasi, evaporasi, dan kondensasi dengan skema panah jelas.', 'assets/img/sample-watercycle-art.png', 'approved', 21, NOW()),
(5, 'Fabel Kancil & Buaya (B. Indonesia)', 'Ratna Dewi', 'VIII B', 'Ilustrasi adegan cerita fabel nusantara dengan ekspresi tokoh ceria dan lingkungan rawa.', 'assets/img/sample-story.svg', 'approved', 18, NOW());

-- 5. Pengaturan Tampilan & Dekorasi Beranda
INSERT INTO site_settings (setting_key, setting_value) VALUES
('home_kicker', 'PEMBELAJARAN SENI RUPA • KELAS VIII'),
('home_title', 'Belajar Seni Visual di'),
('home_highlight', 'Digi-Art'),
('home_description', 'Media interaktif untuk menguasai Menggambar Ilustrasi untuk Mata Pelajaran Lain. Jelajahi materi adaptif, temukan referensi gambar nyata, berkarya di studio kanvas digital, dan uji pemahaman lewat kuis interaktif.'),
('home_badge', 'Kurikulum Seni Rupa SMP • Kelas VIII'),
('home_theme', 'blue'),
('home_art_mode', 'svg_default'),
('home_custom_art', ''),
('demo_mode', '1'),
('materi_bab', 'BAB 1 • SENI RUPA KELAS VIII'),
('materi_title', 'Menggambar Ilustrasi untuk Mata Pelajaran Lain'),
('materi_description', 'Pelajari konsep, fungsi, jenis, objek visual, hingga langkah praktis berkarya ilustrasi lintas mata pelajaran.')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- 6. Log Aktivitas Inisialisasi
INSERT INTO activity_logs (user_name, role, action) VALUES
('System', 'admin', 'Inisialisasi sistem database Digi-Art berhasil.');
