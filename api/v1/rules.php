<?php
// ============================================================
//  api/v1/rules.php
// ============================================================
define('APP_ROOT', dirname(dirname(__DIR__)));
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$r    = $body['rules'] ?? $body ?? [];

$clean = [
    'duty_per_day'   => max(1, min(50, (int)($r['duty_per_day']   ?? 4))),
    'max_per_day'    => max(1, min(100,(int)($r['max_per_day']    ?? 10))),
    'allow_override' => (bool)($r['allow_override'] ?? true),
    'rotate_by'      => in_array($r['rotate_by']??'',['to','manual']) ? $r['rotate_by'] : 'to',
];

DB::saveRules($clean);
jsonOut(['ok'=>true]);
