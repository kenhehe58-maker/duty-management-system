<?php
// ============================================================
//  api/v1/report.php
// ============================================================
define('APP_ROOT', dirname(dirname(__DIR__)));
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? '';

switch ($action) {
    case 'delete_photo': {
        $key  = $body['key']  ?? '';
        $date = $body['date'] ?? '';
        $path = $body['path'] ?? '';

        if (!$key || !$date || !$path) jsonOut(['ok'=>false,'error'=>'Thiếu thông tin'], 422);

        // Xoá file vật lý
        $abs = APP_ROOT . '/' . ltrim($path, '/');
        if (file_exists($abs)) @unlink($abs);

        // Xoá khỏi DB
        $report = DB::getReport($date);
        if (isset($report[$key])) {
            $report[$key] = array_values(array_filter($report[$key], fn($p)=>$p!==$path));
        }
        DB::saveReport($date, $report);
        jsonOut(['ok'=>true]);
    }
    default:
        jsonOut(['ok'=>false,'error'=>'Unknown action'], 400);
}
