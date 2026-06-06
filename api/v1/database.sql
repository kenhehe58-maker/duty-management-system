-- ============================================================
--  database.sql  —  Chạy 1 lần trong phpMyAdmin
-- ============================================================
CREATE DATABASE IF NOT EXISTS quanly_trucnhat
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quanly_trucnhat;

CREATE TABLE IF NOT EXISTS students (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(120) NOT NULL,
  `to`       TINYINT UNSIGNED NOT NULL DEFAULT 1,
  hang       TINYINT UNSIGNED NULL,
  vi_tri     CHAR(1) NULL,
  ban        TINYINT UNSIGNED NULL,
  chuc_vu    VARCHAR(50) NULL DEFAULT NULL COMMENT 'Lớp trưởng, Tổ trưởng...',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS schedules (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  week_number   SMALLINT UNSIGNED NOT NULL UNIQUE,
  schedule_data LONGTEXT NOT NULL,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rules (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rule_data LONGTEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reports (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  report_date  DATE NOT NULL UNIQUE,
  report_data  LONGTEXT NOT NULL,
  updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS class_settings (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  class_name VARCHAR(100) NOT NULL DEFAULT 'Lớp 10A1',
  school_year VARCHAR(20) NOT NULL DEFAULT '2024-2025',
  num_to     TINYINT UNSIGNED NOT NULL DEFAULT 4,
  num_rows   TINYINT UNSIGNED NOT NULL DEFAULT 6
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO rules (id, rule_data) VALUES
  (1,'{"duty_per_day":4,"max_per_day":10,"allow_override":true,"rotate_by":"to"}');
INSERT IGNORE INTO class_settings (id, class_name, school_year, num_to, num_rows) VALUES
  (1,'Lớp 10A1','2024-2025',4,6);
