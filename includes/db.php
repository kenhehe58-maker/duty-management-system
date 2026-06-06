<?php
// ============================================================
//  includes/db.php — MySQL PDO layer
// ============================================================
require_once __DIR__ . '/config.php';

class DB {
    private static ?PDO $pdo = null;

    public static function init(): void {
        if (self::$pdo !== null) return;
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'DB: ' . $e->getMessage()]));
        }
    }

    // ── Students ─────────────────────────────────────────────
    public static function getStudents(array $f = []): array {
        self::init();
        $sql = "SELECT * FROM students WHERE 1=1";
        $p   = [];
        if (!empty($f['to']))     { $sql .= " AND `to`=:to";          $p['to']     = $f['to']; }
        if (!empty($f['search'])) { $sql .= " AND name LIKE :search"; $p['search'] = '%'.$f['search'].'%'; }
        $sql .= " ORDER BY `to`,hang,vi_tri,id";
        $st  = self::$pdo->prepare($sql);
        $st->execute($p);
        return $st->fetchAll();
    }

    public static function saveStudents(array $students): bool {
        self::init();
        try {
            self::$pdo->beginTransaction();
            self::$pdo->exec("TRUNCATE TABLE students");
            if ($students) {
                $st = self::$pdo->prepare(
                    "INSERT INTO students (id,name,`to`,hang,vi_tri,ban) VALUES (:id,:name,:to,:hang,:vi_tri,:ban)"
                );
                foreach ($students as $s) {
                    $st->execute([
                        'id'     => $s['id']     ?? null,
                        'name'   => $s['name'],
                        'to'     => $s['to'],
                        'hang'   => $s['hang']   ?? null,
                        'vi_tri' => $s['vi_tri'] ?? null,
                        'ban'    => $s['ban']    ?? null,
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

    public static function upsertStudent(array $s): bool {
        self::init();
        $st = self::$pdo->prepare(
            "INSERT INTO students (id,name,`to`,hang,vi_tri,ban)
             VALUES (:id,:name,:to,:hang,:vi_tri,:ban)
             ON DUPLICATE KEY UPDATE name=VALUES(name),`to`=VALUES(`to`),
             hang=VALUES(hang),vi_tri=VALUES(vi_tri),ban=VALUES(ban)"
        );
        return $st->execute([
            'id'     => $s['id']     ?? null,
            'name'   => $s['name'],
            'to'     => $s['to'],
            'hang'   => $s['hang']   ?? null,
            'vi_tri' => $s['vi_tri'] ?? null,
            'ban'    => $s['ban']    ?? null,
        ]);
    }

    public static function deleteStudent(int $id): bool {
        self::init();
        return self::$pdo->prepare("DELETE FROM students WHERE id=:id")->execute(['id'=>$id]);
    }

    // ── Schedule ─────────────────────────────────────────────
    public static function getSchedule(int $week): array {
        self::init();
        $st = self::$pdo->prepare("SELECT schedule_data FROM schedules WHERE week_number=:w");
        $st->execute(['w'=>$week]);
        $row = $st->fetch();
        return $row ? (json_decode($row['schedule_data'], true) ?? []) : [];
    }

    public static function saveSchedule(int $week, array $schedule): bool {
        self::init();
        $json = json_encode($schedule, JSON_UNESCAPED_UNICODE);
        $st = self::$pdo->prepare(
            "INSERT INTO schedules (week_number,schedule_data) VALUES (:w,:d)
             ON DUPLICATE KEY UPDATE schedule_data=VALUES(schedule_data)"
        );
        return $st->execute(['w'=>$week,'d'=>$json]);
    }

    // ── Rules ─────────────────────────────────────────────────
    public static function getRules(): array {
        self::init();
        $defaults = [
            'duty_per_day'   => DEFAULT_DUTY_PER_DAY,
            'max_per_day'    => MAX_DUTY_PER_DAY,
            'allow_override' => true,
            'rotate_by'      => 'to',
        ];
        $st = self::$pdo->query("SELECT rule_data FROM rules LIMIT 1");
        $row = $st->fetch();
        return $row ? array_merge($defaults, json_decode($row['rule_data'], true) ?? []) : $defaults;
    }

    public static function saveRules(array $rules): bool {
        self::init();
        self::$pdo->exec("DELETE FROM rules");
        $st = self::$pdo->prepare("INSERT INTO rules (rule_data) VALUES (:d)");
        return $st->execute(['d' => json_encode($rules, JSON_UNESCAPED_UNICODE)]);
    }

    // ── Reports ───────────────────────────────────────────────
    public static function getReport(string $date): array {
        self::init();
        $st = self::$pdo->prepare("SELECT report_data FROM reports WHERE report_date=:d");
        $st->execute(['d'=>$date]);
        $row = $st->fetch();
        return $row ? (json_decode($row['report_data'], true) ?? []) : [];
    }

    public static function saveReport(string $date, array $report): bool {
        self::init();
        $json = json_encode($report, JSON_UNESCAPED_UNICODE);
        $st = self::$pdo->prepare(
            "INSERT INTO reports (report_date,report_data) VALUES (:d,:j)
             ON DUPLICATE KEY UPDATE report_data=VALUES(report_data)"
        );
        return $st->execute(['d'=>$date,'j'=>$json]);
    }
}

DB::init();