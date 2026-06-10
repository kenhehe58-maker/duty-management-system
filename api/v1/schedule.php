<?php
// ============================================================
//  api/v1/schedule.php  — v2.2: Smart auto-rotation by week
// ============================================================
define('APP_ROOT', dirname(dirname(__DIR__)));
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? '';
$week   = max(1, (int)($body['week'] ?? date('W')));

/**
 * Sinh lịch tuần theo quy tắc:
 *  - Mỗi tuần 1 tổ trực cả tuần: tổ = ((week - base_week) % 4) + 1
 *    (base_week lấy từ rules['base_week'] và rules['base_to'], mặc định tuần 1 = tổ 1)
 *  - T2,T3,T4 (day 0,1,2): lấy học sinh hàng 1+2+3 của tổ đó
 *  - T5,T6    (day 3,4):   lấy học sinh hàng 4+5+6 của tổ đó
 *  - Mỗi ngày tối đa duty_per_day người, lấy theo thứ tự T rồi P từng hàng
 */
function buildWeekSchedule(int $week, array $students, array $rules): array {
    $baseWeek = (int)($rules['base_week'] ?? 1);
    $baseTo   = (int)($rules['base_to']   ?? 1);
    $numTo    = 4; // cố định 4 tổ

    // Tính tổ trực tuần này (xoay vòng 1→2→3→4→1...)
    $offset = (($week - $baseWeek) % $numTo + $numTo) % $numTo;
    $dutyTo = (($baseTo - 1 + $offset) % $numTo) + 1;

    // Lọc học sinh thuộc tổ này (bao gồm cả chưa có chỗ ngồi)
    $toStudents = array_values(array_filter($students,
        fn($s) => (int)($s['to'] ?? 0) === $dutyTo
    ));
    // Sắp xếp: có hàng trước (theo hàng→vị trí), chưa có hàng xếp sau
    usort($toStudents, function($a, $b) {
        $ha = (int)($a['hang'] ?? 0);
        $hb = (int)($b['hang'] ?? 0);
        if ($ha === 0 && $hb === 0) return strcmp($a['name']??'', $b['name']??'');
        if ($ha === 0) return 1;  // chưa có hàng -> xếp sau
        if ($hb === 0) return -1;
        return $ha <=> $hb ?: strcmp($a['vi_tri']??'', $b['vi_tri']??'');
    });

    // Phân nhóm: hàng 1-3 (sáng T2-T4), hàng 4-6 (T5-T6), chưa có chỗ -> cả 2 nhóm
    $earlyStudents = [];
    $lateStudents  = [];
    foreach ($toStudents as $s) {
        $h = (int)($s['hang'] ?? 0);
        if ($h === 0) {
            // Chưa có chỗ: phân đều vào cả 2 nhóm
            $earlyStudents[] = $s;
            $lateStudents[]  = $s;
        } elseif ($h <= 3) {
            $earlyStudents[] = $s;
        } else {
            $lateStudents[]  = $s;
        }
    }

    $n        = max(1, (int)($rules['duty_per_day'] ?? 4));
    $schedule = [];

    // 5 ngày
    $dayGroups = [
        0 => $earlyStudents, // T2
        1 => $earlyStudents, // T3
        2 => $earlyStudents, // T4
        3 => $lateStudents,  // T5
        4 => $lateStudents,  // T6
    ];
    for ($d = 0; $d < 5; $d++) {
        $pool = $dayGroups[$d];
        $schedule[$d] = [
            'to'       => $dutyTo,
            'students' => array_slice($pool, 0, min($n, count($pool))),
        ];
    }

    return $schedule;
}

switch ($action) {

    case 'add': {
        $day = (int)($body['day'] ?? 0);
        $sid = (int)($body['sid'] ?? 0);
        if (!$sid) jsonOut(['ok'=>false,'error'=>'Thiếu sid'], 422);

        $schedule = DB::getSchedule($week);
        $students = DB::getStudents();
        $st = array_values(array_filter($students, fn($s)=>$s['id']==$sid));
        if (!$st) jsonOut(['ok'=>false,'error'=>'Không tìm thấy học sinh'], 404);

        $rules = DB::getRules();
        $ids = array_column($schedule[$day]['students'] ?? [], 'id');
        if (in_array($sid, $ids)) jsonOut(['ok'=>true, 'msg'=>'Đã có trong danh sách']);

        if (!$rules['allow_override'] && count($ids) >= $rules['max_per_day']) {
            jsonOut(['ok'=>false,'error'=>'Đã đủ số người trực tối đa ('.$rules['max_per_day'].')'], 422);
        }

        $schedule[$day]['students'][] = $st[0];
        DB::saveSchedule($week, $schedule);
        jsonOut(['ok'=>true]);
    }

    case 'remove': {
        $day = (int)($body['day'] ?? 0);
        $sid = (int)($body['sid'] ?? 0);
        $schedule = DB::getSchedule($week);
        $schedule[$day]['students'] = array_values(
            array_filter($schedule[$day]['students'] ?? [], fn($s)=>$s['id']!=$sid)
        );
        DB::saveSchedule($week, $schedule);
        jsonOut(['ok'=>true]);
    }

    case 'reorder': {
        $day  = (int)($body['day'] ?? 0);
        $sids = array_map('intval', $body['sids'] ?? []);
        $schedule = DB::getSchedule($week);
        $indexed  = array_column($schedule[$day]['students'] ?? [], null, 'id');
        $ordered  = [];
        foreach ($sids as $id) { if (isset($indexed[$id])) $ordered[] = $indexed[$id]; }
        $schedule[$day]['students'] = $ordered;
        DB::saveSchedule($week, $schedule);
        jsonOut(['ok'=>true]);
    }

    case 'regen': {
        $students = DB::getStudents();
        $rules    = DB::getRules();
        $schedule = buildWeekSchedule($week, $students, $rules);
        DB::saveSchedule($week, $schedule);
        jsonOut(['ok'=>true]);
    }

    case 'add_extra': {
        $date = sanitize($body['date'] ?? '');
        $to   = max(1, min(8, (int)($body['to'] ?? 1)));
        $schedule = DB::getSchedule($week);
        $students = DB::getStudents();
        $rules    = DB::getRules();
        $toSt     = array_values(array_filter($students, fn($s)=>$s['to']==$to));
        $n        = $rules['duty_per_day'];
        $nextIdx  = max(5, (empty($schedule)?4:max(array_keys($schedule))) + 1);
        $schedule[$nextIdx] = ['to'=>$to,'date'=>$date,'students'=>array_slice($toSt,0,$n)];
        DB::saveSchedule($week, $schedule);
        jsonOut(['ok'=>true]);
    }

    case 'remove_extra': {
        $idx = (int)($body['idx'] ?? -1);
        $schedule = DB::getSchedule($week);
        unset($schedule[$idx]);
        DB::saveSchedule($week, $schedule);
        jsonOut(['ok'=>true]);
    }

    // Lưu tuần gốc & tổ gốc vào rules (dùng khi admin set "tuần này bắt đầu từ tổ X")
    case 'set_base': {
        $rules = DB::getRules();
        $rules['base_week'] = $week;
        $rules['base_to']   = max(1, min(4, (int)($body['base_to'] ?? 1)));
        DB::saveRules($rules);
        jsonOut(['ok'=>true]);
    }

    default:
        jsonOut(['ok'=>false,'error'=>'Unknown action: '.$action], 400);
}