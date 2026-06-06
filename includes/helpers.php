<?php
// ============================================================
//  includes/helpers.php
// ============================================================
function getInitials(string $name): string {
    $p = array_values(array_filter(explode(' ', trim($name))));
    if (!$p) return '?';
    return mb_strtoupper(mb_substr(end($p), 0, 1) . (count($p)>1 ? mb_substr($p[count($p)-2],0,1) : ''));
}
function weekLabel(int $w): string {
    $d = new DateTime('2025-01-06');
    $d->modify('+' . (($w-1)*7) . ' days');
    $e = clone $d; $e->modify('+4 days');
    return 'Tuần '.$w.' ('.$d->format('d/m').'–'.$e->format('d/m').')';
}
function dayNames(): array { return ['Thứ 2','Thứ 3','Thứ 4','Thứ 5','Thứ 6']; }
function sanitize(string $s): string { return htmlspecialchars(strip_tags(trim($s)), ENT_QUOTES, 'UTF-8'); }
function jsonOut(array $d, int $c = 200): void {
    http_response_code($c);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($d, JSON_UNESCAPED_UNICODE); exit;
}