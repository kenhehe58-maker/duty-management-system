<?php
// ============================================================
//  includes/db.php
//  Module: Data Persistence (JSON files, no SQL needed)
//  Phân công: Dev A
//  Đổi sang MySQL dễ dàng: chỉ sửa file này.
// ============================================================

class DB {
    private static string $dir;

    public static function init(): void {
        self::$dir = DATA_DIR;
    }

    // --- Generic read/write ---
    public static function read(string $table): array {
        $file = self::$dir . $table . '.json';
        if (!file_exists($file)) return [];
        $raw = file_get_contents($file);
        return json_decode($raw, true) ?? [];
    }

    public static function write(string $table, array $data): bool {
        self::init();
        $file = self::$dir . $table . '.json';
        return file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false;
    }

    // --- Students ---
    public static function getStudents(array $filters = []): array {
        $rows = self::read('students');
        if (!empty($filters['to']))     $rows = array_filter($rows, fn($r) => $r['to'] == $filters['to']);
        if (!empty($filters['search'])) $rows = array_filter($rows, fn($r) => mb_stripos($r['name'], $filters['search']) !== false);
        return array_values($rows);
    }

    public static function saveStudents(array $students): bool {
        return self::write('students', $students);
    }

    // --- Schedule ---
    public static function getSchedule(int $week): array {
        $all = self::read('schedules');
        return $all[$week] ?? [];
    }

    public static function saveSchedule(int $week, array $schedule): bool {
        $all = self::read('schedules');
        $all[$week] = $schedule;
        return self::write('schedules', $all);
    }

    // --- Rules (luật phân công) ---
    public static function getRules(): array {
        $defaults = [
            'duty_per_day'   => DEFAULT_DUTY_PER_DAY,
            'max_per_day'    => MAX_DUTY_PER_DAY,
            'allow_override' => true,   // cho phép vượt quá mặc định
            'rotate_by'      => 'to',   // 'to' | 'manual'
            'skip_students'  => [],     // ID học sinh được miễn trực
        ];
        $saved = self::read('rules');
        return array_merge($defaults, $saved);
    }

    public static function saveRules(array $rules): bool {
        return self::write('rules', $rules);
    }

    // --- Photo reports ---
    public static function getReports(string $date = ''): array {
        $all = self::read('reports');
        if ($date) return $all[$date] ?? [];
        return $all;
    }

    public static function saveReport(string $date, array $report): bool {
        $all = self::read('reports');
        $all[$date] = $report;
        return self::write('reports', $all);
    }
}

DB::init();