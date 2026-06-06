<?php
// ============================================================
//  includes/config.php
// ============================================================

// ── MySQL ────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'quanly_trucnhat');
define('DB_USER', 'root');
define('DB_PASS', ''); // XAMPP mặc định

// ── Lớp học ──────────────────────────────────────────────────
define('CLASS_NAME',          'Lớp 10A1');
define('SCHOOL_YEAR',         '2024–2025');
define('NUM_TO',              4);
define('NUM_ROWS',            6);
define('NUM_SIDE_ROWS',       6);
define('MAX_DUTY_PER_DAY',    10);
define('DEFAULT_DUTY_PER_DAY', 4);
define('DUTY_DAYS_PER_WEEK',  5);
define('DUTY_SESSION',        'Buổi sáng');

// ── Upload ───────────────────────────────────────────────────
define('UPLOAD_DIR',    APP_ROOT . '/uploads/');
define('MAX_UPLOAD_MB', 20);

// ── Màu sắc tổ ───────────────────────────────────────────────
define('TO_COLORS', json_encode([
    1 => ['bg'=>'#EBF5FB','border'=>'#2E86C1','text'=>'#1A5276','label'=>'Tổ 1'],
    2 => ['bg'=>'#E9F7EF','border'=>'#27AE60','text'=>'#1D6A3A','label'=>'Tổ 2'],
    3 => ['bg'=>'#FEF9E7','border'=>'#D4AC0D','text'=>'#7D6608','label'=>'Tổ 3'],
    4 => ['bg'=>'#FDEDEC','border'=>'#E74C3C','text'=>'#922B21','label'=>'Tổ 4'],
]));

// ── Tạo thư mục upload nếu chưa có ───────────────────────────
if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);