<?php
// ============================================================
// includes/db.php - TỐI ƯU HÓA (Đã xóa trùng lặp)
// ============================================================
require_once __DIR__ . '/config.php';

class DB {
    private static PDO $pdo;

    public static function init(): void {
        if (isset(self::$pdo)) return;
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("❌ Lỗi kết nối MySQL: " . $e->getMessage());
        }
    }

    // 1. QUẢN LÝ HỌC SINH
    public static function getStudents(array $filters = []): array {
        self::init();
        $sql = "SELECT * FROM students WHERE 1=1";
        $params = [];
        if (!empty($filters['to'])) { $sql .= " AND `to` = :to"; $params['to'] = $filters['to']; }
        if (!empty($filters['search'])) { $sql .= " AND `name` LIKE :search"; $params['search'] = '%' . $filters['search'] . '%'; }
        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function saveStudents(array $students): bool {
        self::init();
        try {
            self::$pdo->beginTransaction();
            self::$pdo->exec("TRUNCATE TABLE students");
            if (!empty($students)) {
                $stmt = self::$pdo->prepare("INSERT INTO students (id, name, `to`, hang, vi_tri) VALUES (:id, :name, :to, :hang, :vi_tri)");
                foreach ($students as $s) {
                    $stmt->execute(['id'=>$s['id']??null, 'name'=>$s['name'], 'to'=>$s['to'], 'hang'=>$s['hang'], 'vi_tri'=>$s['vi_tri']]);
                }
            }
            self::$pdo->commit();
            return true;
        } catch (Exception $e) {
            self::$pdo->rollBack();
            return false;
        }
    }

    // 2. LỊCH TRỰC NHẬT
    public static function getSchedule(int $week): array {
        self::init();
        $stmt = self::$pdo->prepare("SELECT schedule_data FROM schedules WHERE week_number = :week");
        $stmt->execute(['week' => $week]);
        $row = $stmt->fetch();
        return $row ? json_decode($row['schedule_data'], true) : [];
    }

    public static function saveSchedule(int $week, array $schedule): bool {
        self::init();
        $jsonData = json_encode($schedule, JSON_UNESCAPED_UNICODE);
        $stmt = self::$pdo->prepare("INSERT INTO schedules (week_number, schedule_data) VALUES (:week, :data) ON DUPLICATE KEY UPDATE schedule_data = :data2");
        return $stmt->execute(['week' => $week, 'data' => $jsonData, 'data2' => $jsonData]);
    }

    // 3. LUẬT PHÂN CÔNG (Fixed: Đã gộp và xử lý mặc định)
    public static function getRules(): array {
        self::init();
        $defaults = ['duty_per_day' => 1, 'max_per_day' => 5, 'allow_override' => true, 'rotate_by' => 'to'];
        $stmt = self::$pdo->query("SELECT rule_data FROM rules LIMIT 1");
        $row = $stmt->fetch();
        return $row ? array_merge($defaults, json_decode($row['rule_data'], true) ?? []) : $defaults;
    }

    public static function saveRules(array $rules): bool {
        self::init();
        self::$pdo->exec("TRUNCATE TABLE rules");
        $stmt = self::$pdo->prepare("INSERT INTO rules (rule_data) VALUES (:data)");
        return $stmt->execute(['data' => json_encode($rules, JSON_UNESCAPED_UNICODE)]);
    }

    // 4. BÁO CÁO HÌNH ẢNH
    public static function getReports(): array {
        self::init();
        $stmt = self::$pdo->query("SELECT * FROM reports");
        return $stmt->fetchAll();
    }

    public static function saveReport(string $date, array $report): bool {
        self::init();
        $stmt = self::$pdo->prepare("INSERT INTO reports (report_date, report_data) VALUES (:dt, :data) ON DUPLICATE KEY UPDATE report_data = :data2");
        return $stmt->execute(['dt' => $date, 'data' => json_encode($report, JSON_UNESCAPED_UNICODE), 'data2' => json_encode($report, JSON_UNESCAPED_UNICODE)]);
    }
}
DB::init();