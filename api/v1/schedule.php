<?php
// ============================================================
//  api/v1/schedule.php
// ============================================================
define('APP_ROOT', dirname(dirname(__DIR__)));
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? '';
$week   = max(1, (int)($body['week'] ?? date('W')));

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
        // Kiểm tra trùng
        $ids = array_column($schedule[$day]['students'] ?? [], 'id');
        if (in_array($sid, $ids)) jsonOut(['ok'=>true, 'msg'=>'Đã có trong danh sách']);

        // Kiểm tra giới hạn nếu không cho override
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
        $toOrder  = [1,2,3,4,1];
        $n        = $rules['duty_per_day'];
        $schedule = [];
        for ($d=0; $d<5; $d++) {
            $to   = $toOrder[$d];
            $toSt = array_values(array_filter($students, fn($s)=>$s['to']==$to));
            $schedule[$d] = ['to'=>$to, 'students'=>array_slice($toSt,0,min($n,count($toSt)))];
        }
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

    default:
        jsonOut(['ok'=>false,'error'=>'Unknown action: '.$action], 400);
}
