    <?php
    // ============================================================
    //  includes/helpers.php
    // ============================================================

    function getInitials(string $name): string {
        $parts = array_values(array_filter(explode(' ', trim($name))));
        if (!$parts) return '?';
        $last   = mb_substr(end($parts), 0, 1);
        $second = count($parts) > 1 ? mb_substr($parts[count($parts)-2], 0, 1) : '';
        return mb_strtoupper($last . $second);
    }

    function weekLabel(int $week): string {
        $start = new DateTime('2025-01-06');
        $start->modify('+' . (($week-1)*7) . ' days');
        $end = clone $start; $end->modify('+4 days');
        return 'Tuần '.$week.' ('.$start->format('d/m').'–'.$end->format('d/m').')';
    }

    function dayNames(): array {
        return ['Thứ 2','Thứ 3','Thứ 4','Thứ 5','Thứ 6'];
    }

    function sanitize(string $s): string {
        return htmlspecialchars(strip_tags(trim($s)), ENT_QUOTES, 'UTF-8');
    }

    function jsonOut(array $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }