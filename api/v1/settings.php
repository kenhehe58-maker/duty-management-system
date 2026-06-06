<?php
// ============================================================
//  api/v1/settings.php
// ============================================================
define('APP_ROOT', dirname(dirname(__DIR__)));
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$s = [
    'class_name'  => sanitize($body['class_name']  ?? 'Lớp 10A1'),
    'school_year' => sanitize($body['school_year'] ?? '2024-2025'),
    'num_to'      => max(2, min(8,  (int)($body['num_to']   ?? 4))),
    'num_rows'    => max(2, min(12, (int)($body['num_rows'] ?? 6))),
];
DB::saveClassSettings($s);
jsonOut(['ok'=>true]);
