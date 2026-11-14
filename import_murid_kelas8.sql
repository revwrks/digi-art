-- =====================================================================
-- Digi-Art: Batch Import Akun Murid Mock / Demo Kelas VIII
-- Password default semua murid demo: 1234
-- Hash: $2y$10$DA6hTbt6QfzOjzfDzwTe9.zcRjT2V1NTYPIaZG9N.42.4KNFs6DDi
-- =====================================================================

INSERT INTO users (username, password, full_name, role, kelas) VALUES
('aditya', '$2y$10$DA6hTbt6QfzOjzfDzwTe9.zcRjT2V1NTYPIaZG9N.42.4KNFs6DDi', 'Aditya Pratama', 'murid', 'VIII A'),
('aisyah', '$2y$10$DA6hTbt6QfzOjzfDzwTe9.zcRjT2V1NTYPIaZG9N.42.4KNFs6DDi', 'Aisyah Maharani', 'murid', 'VIII A'),
('bintang', '$2y$10$DA6hTbt6QfzOjzfDzwTe9.zcRjT2V1NTYPIaZG9N.42.4KNFs6DDi', 'Bintang Ramadhan', 'murid', 'VIII A'),
('citra', '$2y$10$DA6hTbt6QfzOjzfDzwTe9.zcRjT2V1NTYPIaZG9N.42.4KNFs6DDi', 'Citra Lestari', 'murid', 'VIII A'),
('dimas', '$2y$10$DA6hTbt6QfzOjzfDzwTe9.zcRjT2V1NTYPIaZG9N.42.4KNFs6DDi', 'Dimas Anggara', 'murid', 'VIII A'),
('ekaputri', '$2y$10$DA6hTbt6QfzOjzfDzwTe9.zcRjT2V1NTYPIaZG9N.42.4KNFs6DDi', 'Eka Putri Nurhaliza', 'murid', 'VIII B'),
('farhan', '$2y$10$DA6hTbt6QfzOjzfDzwTe9.zcRjT2V1NTYPIaZG9N.42.4KNFs6DDi', 'Farhan Maulana', 'murid', 'VIII B'),
('gita', '$2y$10$DA6hTbt6QfzOjzfDzwTe9.zcRjT2V1NTYPIaZG9N.42.4KNFs6DDi', 'Gita Permata', 'murid', 'VIII B'),
('hafiz', '$2y$10$DA6hTbt6QfzOjzfDzwTe9.zcRjT2V1NTYPIaZG9N.42.4KNFs6DDi', 'Hafiz Syahputra', 'murid', 'VIII B'),
('indah', '$2y$10$DA6hTbt6QfzOjzfDzwTe9.zcRjT2V1NTYPIaZG9N.42.4KNFs6DDi', 'Indah Cahyani', 'murid', 'VIII B')
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), kelas = VALUES(kelas);
