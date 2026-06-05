<?php
// ============================================================
//  includes/config.php
//  Module: Configuration
//  Phân công: Dev A
// ============================================================

// --- Cấu hình lớp học ---
define('CLASS_NAME',    'Lớp 10A1');
define('SCHOOL_YEAR',   '2024–2025');
define('NUM_TO',        4);          // Số tổ
define('NUM_ROWS',      6);          // Số hàng bàn chính (dọc)
define('NUM_SIDE_ROWS', 6);          // Số dãy bàn bên phải
define('DESKS_PER_BAN', 2);          // T + P mỗi bàn
define('MAX_DUTY_PER_DAY', 8);       // Giới hạn mềm: tối đa người trực/ngày (có thể override)
define('DEFAULT_DUTY_PER_DAY', 4);   // Mặc định 4 người/ngày

// --- Cấu hình lịch trực ---
define('DUTY_DAYS_PER_WEEK', 5);     // Thứ 2–6
define('DUTY_SESSION', 'Buổi sáng'); // Chỉ sáng

// --- Cấu hình file / upload ---
define('UPLOAD_DIR',    APP_ROOT . '/uploads/');
define('DATA_DIR',      APP_ROOT . '/data/');
define('MAX_UPLOAD_MB', 10);

// --- Màu sắc tổ ---
define('TO_COLORS', json_encode([
    1 => ['bg' => '#E6F1FB', 'border' => '#378ADD', 'text' => '#0C447C', 'label' => 'Tổ 1'],
    2 => ['bg' => '#EAF3DE', 'border' => '#639922', 'text' => '#27500A', 'label' => 'Tổ 2'],
    3 => ['bg' => '#FAEEDA', 'border' => '#BA7517', 'text' => '#633806', 'label' => 'Tổ 3'],
    4 => ['bg' => '#FAECE7', 'border' => '#D85A30', 'text' => '#712B13', 'label' => 'Tổ 4'],
]));

// --- Tạo thư mục nếu chưa có ---
foreach ([UPLOAD_DIR, DATA_DIR] as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
}