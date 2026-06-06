<?php
// ============================================================
//  includes/db.php — MySQL PDO layer (hoàn chỉnh)
// ============================================================
require_once __DIR__ . '/config.php';

class DB {
    private static ?PDO $pdo = null;

    public static function init(): void {
        if (self::$pdo) return;
        try {
            self::$pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error'=>'DB: '.$e->getMessage()], JSON_UNESCAPED_UNICODE));
        }
    }

    // ── Class settings ────────────────────────────────────────
    public static function getClassSettings(): array {
        self::init();
        $row = self::$pdo->query("SELECT * FROM class_settings LIMIT 1")->fetch();
        return $row ?: ['class_name'=>'Lớp 10A1','school_year'=>'2024-2025','num_to'=>4,'num_rows'=>6];
    }
    public static function saveClassSettings(array $s): bool {
        self::init();
        $st = self::$pdo->prepare("UPDATE class_settings SET class_name=:cn,school_year=:sy,num_to=:nt,num_rows=:nr WHERE id=1");
        return $st->execute(['cn'=>$s['class_name'],'sy'=>$s['school_year'],'nt'=>(int)$s['num_to'],'nr'=>(int)$s['num_rows']]);
    }

    // ── Students ──────────────────────────────────────────────
    public static function getStudents(array $f=[]): array {
        self::init();
        $sql = "SELECT * FROM students WHERE 1=1";
        $p = [];
        if (!empty($f['to']))     { $sql .= " AND `to`=:to";          $p['to']=$f['to']; }
        if (!empty($f['search'])) { $sql .= " AND name LIKE :s";       $p['s']='%'.$f['search'].'%'; }
        $sql .= " ORDER BY `to`,hang,vi_tri,id";
        $st = self::$pdo->prepare($sql); $st->execute($p);
        return $st->fetchAll();
    }

    public static function upsertStudent(array $s): int {
        self::init();
        if (!empty($s['id'])) {
            $st = self::$pdo->prepare(
                "INSERT INTO students (id,name,`to`,hang,vi_tri,ban,chuc_vu)
                 VALUES (:id,:n,:t,:h,:v,:b,:c)
                 ON DUPLICATE KEY UPDATE name=VALUES(name),`to`=VALUES(`to`),
                 hang=VALUES(hang),vi_tri=VALUES(vi_tri),ban=VALUES(ban),chuc_vu=VALUES(chuc_vu)"
            );
            $st->execute(['id'=>$s['id'],'n'=>$s['name'],'t'=>$s['to'],
                'h'=>$s['hang']??null,'v'=>$s['vi_tri']??null,'b'=>$s['ban']??null,'c'=>$s['chuc_vu']??null]);
            return (int)$s['id'];
        } else {
            $st = self::$pdo->prepare(
                "INSERT INTO students (name,`to`,hang,vi_tri,ban,chuc_vu) VALUES (:n,:t,:h,:v,:b,:c)"
            );
            $st->execute(['n'=>$s['name'],'t'=>$s['to'],
                'h'=>$s['hang']??null,'v'=>$s['vi_tri']??null,'b'=>$s['ban']??null,'c'=>$s['chuc_vu']??null]);
            return (int)self::$pdo->lastInsertId();
        }
    }

    public static function deleteStudent(int $id): bool {
        self::init();
        return self::$pdo->prepare("DELETE FROM students WHERE id=:id")->execute(['id'=>$id]);
    }

    public static function assignDesk(int $sid, int $to, int $hang, string $vi_tri): bool {
        self::init();
        // Clear previous occupant of that seat
        self::$pdo->prepare("UPDATE students SET hang=NULL,vi_tri=NULL WHERE `to`=:t AND hang=:h AND vi_tri=:v AND id!=:sid")
            ->execute(['t'=>$to,'h'=>$hang,'v'=>$vi_tri,'sid'=>$sid]);
        return self::$pdo->prepare("UPDATE students SET `to`=:t,hang=:h,vi_tri=:v WHERE id=:id")
            ->execute(['t'=>$to,'h'=>$hang,'v'=>$vi_tri,'id'=>$sid]);
    }

    public static function bulkImport(array $students, string $mode='merge'): int {
        self::init();
        if ($mode === 'replace') self::$pdo->exec("TRUNCATE TABLE students");
        $count = 0;
        foreach ($students as $s) {
            if (empty($s['name'])) continue;
            if ($mode === 'merge') {
                // match by name
                $st = self::$pdo->prepare("SELECT id FROM students WHERE name=:n LIMIT 1");
                $st->execute(['n'=>$s['name']]);
                $row = $st->fetch();
                if ($row) $s['id'] = $row['id'];
            }
            self::upsertStudent($s);
            $count++;
        }
        return $count;
    }

    // ── Schedule ──────────────────────────────────────────────
    public static function getSchedule(int $week): array {
        self::init();
        $st = self::$pdo->prepare("SELECT schedule_data FROM schedules WHERE week_number=:w");
        $st->execute(['w'=>$week]);
        $row = $st->fetch();
        return $row ? (json_decode($row['schedule_data'],true)??[]) : [];
    }
    public static function saveSchedule(int $week, array $s): bool {
        self::init();
        $j = json_encode($s, JSON_UNESCAPED_UNICODE);
        return self::$pdo->prepare(
            "INSERT INTO schedules (week_number,schedule_data) VALUES (:w,:d)
             ON DUPLICATE KEY UPDATE schedule_data=VALUES(schedule_data)"
        )->execute(['w'=>$week,'d'=>$j]);
    }

    // ── Rules ─────────────────────────────────────────────────
    public static function getRules(): array {
        self::init();
        $def = ['duty_per_day'=>DEFAULT_DUTY_PER_DAY,'max_per_day'=>MAX_DUTY_PER_DAY,'allow_override'=>true,'rotate_by'=>'to'];
        $row = self::$pdo->query("SELECT rule_data FROM rules LIMIT 1")->fetch();
        return $row ? array_merge($def, json_decode($row['rule_data'],true)??[]) : $def;
    }
    public static function saveRules(array $r): bool {
        self::init();
        self::$pdo->exec("DELETE FROM rules");
        return self::$pdo->prepare("INSERT INTO rules (rule_data) VALUES (:d)")
            ->execute(['d'=>json_encode($r,JSON_UNESCAPED_UNICODE)]);
    }

    // ── Reports ───────────────────────────────────────────────
    public static function getReport(string $date): array {
        self::init();
        $st = self::$pdo->prepare("SELECT report_data FROM reports WHERE report_date=:d");
        $st->execute(['d'=>$date]);
        $row = $st->fetch();
        return $row ? (json_decode($row['report_data'],true)??[]) : [];
    }
    public static function saveReport(string $date, array $r): bool {
        self::init();
        $j = json_encode($r, JSON_UNESCAPED_UNICODE);
        return self::$pdo->prepare(
            "INSERT INTO reports (report_date,report_data) VALUES (:d,:j)
             ON DUPLICATE KEY UPDATE report_data=VALUES(report_data)"
        )->execute(['d'=>$date,'j'=>$j]);
    }
    public static function getReportDates(): array {
        self::init();
        return self::$pdo->query("SELECT report_date FROM reports ORDER BY report_date DESC LIMIT 30")->fetchAll(PDO::FETCH_COLUMN);
    }
}
DB::init();