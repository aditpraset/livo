-- =====================================================================
-- DATABASE CREATION SCRIPT
-- Nama Sistem: Sistem Informasi Manajemen Bimbel (Admin-Centric)
-- Target RDBMS: MySQL 8.0+ / MariaDB 10.4+
-- =====================================================================

CREATE DATABASE IF NOT EXISTS bimbel_management_db;
USE bimbel_management_db;

-- Menghapus tabel lama jika ada (Urutan drop harus memprioritaskan tabel child terlebih dahulu)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS evaluations;
DROP TABLE IF EXISTS schedules;
DROP TABLE IF EXISTS subjects;
DROP TABLE IF EXISTS packages;
DROP TABLE IF EXISTS tutors;
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- 3. TABEL: tutors
-- =====================================================================
CREATE TABLE tutors (
    id BIGINT UNSIGNED AUTO_INCREMENT,
    phone VARCHAR(20) NOT NULL,
    specialization TEXT NOT NULL, -- Menyimpan array / daftar mapel dalam format JSON string
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 4. TABEL: packages
-- =====================================================================
CREATE TABLE packages (
    id BIGINT UNSIGNED AUTO_INCREMENT,
    package_name VARCHAR(100) NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    total_sessions INT NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 6. TABEL: subjects
-- =====================================================================
CREATE TABLE subjects (
    id BIGINT UNSIGNED AUTO_INCREMENT,
    subject_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 7. TABEL: schedules
-- =====================================================================
CREATE TABLE schedules (
    id BIGINT UNSIGNED AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    tutor_id BIGINT UNSIGNED NOT NULL,
    subject_id BIGINT UNSIGNED NOT NULL,
    class_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status_schedule ENUM('scheduled', 'done', 'canceled') NOT NULL DEFAULT 'scheduled',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    -- Indexing ditambahkan untuk mengoptimalkan kueri pengecekan algoritma anti-bentrok
    KEY idx_schedule_lookup (class_date, status_schedule), 
    CONSTRAINT fk_schedules_student_id FOREIGN KEY (student_id) 
        REFERENCES students (id) ON DELETE CASCADE,
    CONSTRAINT fk_schedules_tutor_id FOREIGN KEY (tutor_id) 
        REFERENCES tutors (id) ON DELETE CASCADE,
    CONSTRAINT fk_schedules_subject_id FOREIGN KEY (subject_id) 
        REFERENCES subjects (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 8. TABEL: evaluations
-- =====================================================================
CREATE TABLE evaluations (
    id BIGINT UNSIGNED AUTO_INCREMENT,
    schedule_id BIGINT UNSIGNED NOT NULL,
    student_attendance ENUM('hadir', 'izin', 'alfa') NOT NULL,
    score INT NULL, -- Nilai kuis/latihan (rentang 0-100)
    tutor_notes TEXT NULL,
    is_published BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY evaluations_schedule_id_unique (schedule_id), -- Memastikan relasi 1:1 ke tabel schedules
    CONSTRAINT fk_evaluations_schedule_id FOREIGN KEY (schedule_id) 
        REFERENCES schedules (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;