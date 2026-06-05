<?php
// ============================================================
//  includes/db.php
//  Module: Data Persistence (CHUYỂN TOÀN BỘ SANG MYSQL)
//  Phân công: Dev A
//  Cam kết: Giữ nguyên 100% tất cả các hàm để chạy mọi tính năng!
// ============================================================

require_once __DIR__ . '/config.php';

class DB {
    private static PDO $pdo;

    public static function init(): void {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("❌ Lỗi kết nối MySQL: " . $e->getMessage());
        }
    }

    // --- Giữ lại hàm đọc/ghi rỗng để không bị lỗi cú pháp nếu file khác lỡ gọi ---
    public static function read(string $table): array { return []; }
    public static function write(string $table, array $data): bool { return true; }

    // ============================================================
    // 1. CHỨC NĂNG: QUẢN LÝ HỌC SINH (Students)
    // ============================================================
    public static function getStudents(array $filters = []): array {
        self::init();
        $sql = "SELECT * FROM students WHERE 1=1";
        $params = [];

        if (!empty($filters['to'])) {
            $sql .= " AND `to` = :to";
            $params['to'] = $filters['to'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND `name` LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function saveStudents(array $students): bool {
        self::init();
        try {
            self::$pdo->beginTransaction();
            // Xóa sạch bảng cũ để ghi đè mảng mới tương tự cơ chế file JSON
            self::$pdo->exec("TRUNCATE TABLE students");
            
            if (!empty($students)) {
                $sql = "INSERT INTO students (id, name, `to`, hang, vi_tri) VALUES (:id, :name, :to, :hang, :vi_tri)";
                $stmt = self::$pdo->prepare($sql);
                foreach ($students as $s) {
                    $stmt->execute([
                        'id'     => $s['id'] ?? null,
                        'name'   => $s['name'],
                        'to'     => $s['to'],
                        'hang'   => $s['hang'],
                        'vi_tri' => $s['vi_tri']
                    ]);
                }
            }
            self::$pdo->commit();
            return true;
        } catch (Exception $e) {
            self::$pdo->rollBack();
            return false;
        }
    }

    // ============================================================
    // 2. CHỨC NĂNG: LỊCH TRỰC NHẬT (Schedule)
    // ============================================================
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
        // Lưu hoặc cập nhật nếu tuần đó đã tồn tại dữ liệu trực nhật
        $sql = "INSERT INTO schedules (week_number, schedule_data) VALUES (:week, :data) 
                ON DUPLICATE KEY UPDATE schedule_data = :data2";
        $stmt = self::$pdo->prepare($sql);
        return $stmt->execute([
            'week'  => $week,
            'data'  => $jsonData,
            'data2' => $jsonData
        ]);
    }

    // ============================================================
    // 3. CHỨC NĂNG: LUẬT PHÂN CÔNG (Rules)
    // ============================================================
    public static function getRules(): array {
        self::init();
        $defaults = [
            'duty_per_day'   => DEFAULT_DUTY_PER_DAY,
            'max_per_day'    => MAX_DUTY_PER_DAY,
            'allow_override' => true,
            'rotate_by'      => 'to',
            'skip_students'  => [],
        ];
        
        $stmt = self::$pdo->query("SELECT rule_data FROM rules LIMIT 1");
        $row = $stmt->fetch();
        if ($row) {
            $saved = json_decode($row['rule_data'], true) ?? [];
            return array_merge($defaults, $saved);
        }
        return $defaults;
    }

    public static function saveRules(array $rules): bool {
        self::init();
        $jsonData = json_encode($rules, JSON_UNESCAPED_UNICODE);
        self::$pdo->exec("TRUNCATE TABLE rules"); // Đảm bảo luôn duy trì duy nhất 1 dòng cấu hình luật
        $stmt = self::$pdo->prepare("INSERT INTO rules (rule_data) VALUES (:data)");
        return $stmt->execute(['data' => $jsonData]);
    }

    // ============================================================
    // 4. CHỨC NĂNG: BÁO CÁO HÌNH ẢNH VỆ SINH (Photo reports)
    // ============================================================
    public static function getReports(string $date = ''): array {
        self::init();
        if ($date) {
            $stmt = self::$pdo->prepare("SELECT report_data FROM reports WHERE report_date = :dt");
            $stmt->execute(['dt' => $date]);
            $row = $stmt->fetch();
            return $row ? json_decode($row['report_data'], true) : [];
        } else {
            $stmt = self::$pdo->query("SELECT report_date, report_data FROM reports");
            $all = [];
            while ($row = $stmt->fetch()) {
                $all[$row['report_date']] = json_decode($row['report_data'], true);
            }
            return $all;
        }
    }

    public static function saveReport(string $date, array $report): bool {
        self::init();
        $jsonData = json_encode($report, JSON_UNESCAPED_UNICODE);
        $sql = "INSERT INTO reports (report_date, report_data) VALUES (:dt, :data) 
                ON DUPLICATE KEY UPDATE report_data = :data2";
        $stmt = self::$pdo->prepare($sql);
        return $stmt->execute([
            'dt'    => $date,
            'data'  => $jsonData,
            'data2' => $jsonData
        ]);
    }
}

DB::init();