
CREATE DATABASE IF NOT EXISTS siakad_mini;
USE siakad_mini;

CREATE TABLE users (
 id INT AUTO_INCREMENT PRIMARY KEY,
 username VARCHAR(50) UNIQUE NOT NULL,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('admin','operator') NOT NULL DEFAULT 'operator',
 deleted_at TIMESTAMP NULL DEFAULT NULL
);

CREATE TABLE dosen (
 id INT AUTO_INCREMENT PRIMARY KEY,
 nidn VARCHAR(20) UNIQUE NOT NULL,
 nama VARCHAR(100) NOT NULL,
 email VARCHAR(100) UNIQUE NOT NULL,
 foto VARCHAR(255),
 deleted_at TIMESTAMP NULL DEFAULT NULL
);

CREATE TABLE mata_kuliah (
 id INT AUTO_INCREMENT PRIMARY KEY,
 kode_mk VARCHAR(20) UNIQUE NOT NULL,
 nama_mk VARCHAR(100) NOT NULL,
 deleted_at TIMESTAMP NULL DEFAULT NULL
);

CREATE TABLE dosen_mata_kuliah (
 id INT AUTO_INCREMENT PRIMARY KEY,
 dosen_id INT NOT NULL,
 mata_kuliah_id INT NOT NULL,
 FOREIGN KEY (dosen_id) REFERENCES dosen(id),
 FOREIGN KEY (mata_kuliah_id) REFERENCES mata_kuliah(id)
);

INSERT INTO users(username,password_hash,role)
VALUES(
'admin',
'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
'admin'
);
