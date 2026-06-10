<?php
// ============================================================
//   includes/config.php — Cấu hình đã cập nhật cho InfinityFree
// ============================================================
define('DB_HOST', 'sql200.infinityfree.com'); // Host lấy từ MySQL Databases của ông
define('DB_NAME', 'if0_42140390_if0_42140390'); // Tên DB đầy đủ trên hosting
define('DB_USER', 'if0_42140390'); // Username hosting cấp
define('DB_PASS', 'Donhatnam2202'); // Mật khẩu vPanel/MySQL của ông

define('DUTY_DAYS_PER_WEEK',  5);
define('DUTY_SESSION',        'Buổi sáng');
define('MAX_DUTY_PER_DAY',    10);
define('DEFAULT_DUTY_PER_DAY', 4);
define('MAX_UPLOAD_MB',        20);
define('UPLOAD_DIR',           APP_ROOT . '/uploads/');

define('TO_COLORS', json_encode([
  1 => ['bg'=>'#E8F4FD','border'=>'#2980B9','text'=>'#1A5276','label'=>'Tổ 1'],
  2 => ['bg'=>'#E9F7EF','border'=>'#27AE60','text'=>'#1D6A3A','label'=>'Tổ 2'],
  3 => ['bg'=>'#FEF9E7','border'=>'#D4AC0D','text'=>'#7D6608','label'=>'Tổ 3'],
  4 => ['bg'=>'#FDEDEC','border'=>'#E74C3C','text'=>'#922B21','label'=>'Tổ 4'],
]));

if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0755, true);