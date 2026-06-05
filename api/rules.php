<?php
// ============================================================
//  api/rules.php  — Lưu luật phân công
//  Phân công: Dev C
// ============================================================
if (basename(__FILE__) === 'rules.php') {
    require_once dirname(__DIR__) . '/includes/config.php';
    require_once dirname(__DIR__) . '/includes/helpers.php';
    require_once dirname(__DIR__) . '/includes/db.php';
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    if (($body['action'] ?? '') === 'save') {
        $r = $body['rules'] ?? [];
        $clean = [
            'duty_per_day'   => max(1, min(30, (int)($r['duty_per_day'] ?? DEFAULT_DUTY_PER_DAY))),
            'max_per_day'    => max(1, min(50, (int)($r['max_per_day']  ?? MAX_DUTY_PER_DAY))),
            'allow_override' => (bool)($r['allow_override'] ?? true),
            'rotate_by'      => in_array($r['rotate_by'] ?? '', ['to','manual']) ? $r['rotate_by'] : 'to',
        ];
        DB::saveRules($clean);
        jsonResponse(['ok' => true]);
    }
    jsonResponse(['ok' => false, 'error' => 'Bad action'], 400);
}