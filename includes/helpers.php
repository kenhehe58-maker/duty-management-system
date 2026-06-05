<?php
// ============================================================
//  includes/helpers.php
//  Module: Utility Functions
//  Phân công: Dev A
// ============================================================

function getInitials(string $name): string {
    $parts = array_filter(explode(' ', trim($name)));
    if (count($parts) === 0) return '?';
    $last = array_pop($parts);
    $second = count($parts) ? array_pop($parts) : '';
    return mb_strtoupper(mb_substr($last, 0, 1) . mb_substr($second, 0, 1));
}

function weekLabel(int $week): string {
    $start = new DateTime('2025-01-06');
    $start->modify('+' . (($week - 1) * 7) . ' days');
    $end = clone $start;
    $end->modify('+4 days');
    return 'Tuần ' . $week . ' (' . $start->format('d/m') . '–' . $end->format('d/m') . ')';
}

function dayNames(): array {
    return ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6'];
}

function sanitize(string $str): string {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}