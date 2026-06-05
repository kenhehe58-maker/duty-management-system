<?php
// ============================================================
//  api/upload_photo.php  — Upload ảnh báo cáo
//  Phân công: Dev D
// ============================================================
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'POST only'], 405);
}

$key  = sanitize($_POST['key']  ?? '');
$date = sanitize($_POST['date'] ?? date('Y-m-d'));

if (!$key) jsonResponse(['ok' => false, 'error' => 'Missing key'], 422);

$allowedKeys = ['podium','teacher_desk','aisle_1','aisle_2','aisle_3','desk_boxes','board_written'];
if (!in_array($key, $allowedKeys)) jsonResponse(['ok' => false, 'error' => 'Invalid key'], 422);

$files = $_FILES['photos'] ?? [];
if (empty($files['name'][0])) jsonResponse(['ok' => false, 'error' => 'No files'], 422);

$uploadDir = UPLOAD_DIR . $date . '/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$report = DB::getReport($date);
if (!isset($report[$key])) $report[$key] = [];

$allowedMime = ['image/jpeg','image/png','image/webp','image/gif'];
$saved = [];

$count = count($files['name']);
for ($i = 0; $i < $count; $i++) {
    $err  = $files['error'][$i];
    $size = $files['size'][$i];
    $tmp  = $files['tmp_name'][$i];
    $mime = mime_content_type($tmp);

    if ($err !== UPLOAD_ERR_OK)            continue;
    if ($size > MAX_UPLOAD_MB * 1024*1024) continue;
    if (!in_array($mime, $allowedMime))    continue;

    $ext  = pathinfo($files['name'][$i], PATHINFO_EXTENSION) ?: 'jpg';
    $fname = $key . '_' . uniqid() . '.' . strtolower($ext);
    $dest  = $uploadDir . $fname;

    if (move_uploaded_file($tmp, $dest)) {
        $webPath = 'uploads/' . $date . '/' . $fname;
        $report[$key][] = $webPath;
        $saved[] = $webPath;
    }
}

DB::saveReport($date, $report);
jsonResponse(['ok' => true, 'saved' => $saved, 'count' => count($saved)]);