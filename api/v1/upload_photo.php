<?php
// ============================================================
//  api/v1/upload_photo.php  — Upload & lưu ảnh báo cáo
// ============================================================
define('APP_ROOT', dirname(dirname(__DIR__)));
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(['ok'=>false,'error'=>'POST only'], 405);
}

$allowedKeys = ['aisle_1','aisle_2','aisle_3','board','teacher_desk','podium','hallway','desk_boxes',
                'podium','board_written']; // backwards compat
$key  = $_POST['key']  ?? '';
$date = $_POST['date'] ?? date('Y-m-d');

// Validate
if (!$key || !in_array($key, $allowedKeys)) jsonOut(['ok'=>false,'error'=>'Key không hợp lệ: '.$key], 422);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = date('Y-m-d');

// Tạo thư mục upload
$uploadDir = APP_ROOT . '/uploads/' . $date . '/';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
    jsonOut(['ok'=>false,'error'=>'Không tạo được thư mục upload'], 500);
}

$files  = $_FILES['photos'] ?? [];
$report = DB::getReport($date);
if (!isset($report[$key])) $report[$key] = [];

$allowed_mime = ['image/jpeg','image/jpg','image/png','image/webp','image/gif','image/heic'];
$saved  = [];
$errors = [];
$count  = is_array($files['name']) ? count($files['name']) : 0;

for ($i = 0; $i < $count; $i++) {
    $err  = is_array($files['error']) ? $files['error'][$i] : $files['error'];
    $size = is_array($files['size'])  ? $files['size'][$i]  : $files['size'];
    $tmp  = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
    $orig = is_array($files['name'])  ? $files['name'][$i]  : $files['name'];

    if ($err !== UPLOAD_ERR_OK) { $errors[] = "File $i: upload error $err"; continue; }
    if ($size > MAX_UPLOAD_MB * 1024 * 1024) { $errors[] = "File $i quá lớn"; continue; }

    $mime = mime_content_type($tmp);
    if (!in_array(strtolower($mime), $allowed_mime)) { $errors[] = "File $i: định dạng không hỗ trợ ($mime)"; continue; }

    $ext   = strtolower(pathinfo($orig, PATHINFO_EXTENSION)) ?: 'jpg';
    if ($ext === 'heic') $ext = 'jpg';
    $fname = $key . '_' . date('His') . '_' . uniqid() . '.' . $ext;
    $dest  = $uploadDir . $fname;

    if (move_uploaded_file($tmp, $dest)) {
        $webPath = 'uploads/' . $date . '/' . $fname;
        $report[$key][] = $webPath;
        $saved[] = $webPath;
    } else {
        $errors[] = "Không thể lưu file $fname";
    }
}

DB::saveReport($date, $report);

jsonOut([
    'ok'     => true,
    'saved'  => $saved,
    'count'  => count($saved),
    'errors' => $errors,
]);
