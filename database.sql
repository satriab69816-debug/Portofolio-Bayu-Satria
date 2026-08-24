CREATE DATABASE IF NOT EXISTS bayu_portfolio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bayu_portfolio;

CREATE TABLE IF NOT EXISTS projects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(80) NOT NULL,
  category VARCHAR(80) NOT NULL,
  description VARCHAR(500) NOT NULL,
  link VARCHAR(500) NOT NULL DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id INT UNSIGNED NOT NULL,
  filename VARCHAR(255) NOT NULL,
  url VARCHAR(500) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_project_images_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  INDEX idx_project_images_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Opsional: data awal. Foto dapat ditambahkan lewat Admin.
INSERT INTO projects (title, category, description, link) VALUES
('Portfolio UI/UX','UI/UX Design','Perancangan antarmuka portfolio dengan fokus pada hierarchy visual, typography, warna, dan pengalaman pengguna yang sederhana serta modern.',''),
('School Website','Website','Website informasi sekolah dengan struktur halaman responsif, navigasi yang jelas, dan tampilan yang mudah digunakan di desktop maupun mobile.',''),
('Event Poster','Graphic Design','Desain poster kegiatan dengan komposisi visual yang kuat, warna kontras, dan informasi utama yang mudah dibaca.',''),
('Social Media Design','Graphic Design','Konten visual media sosial untuk kebutuhan publikasi acara, branding, dan dokumentasi dengan gaya visual yang konsisten.','');
