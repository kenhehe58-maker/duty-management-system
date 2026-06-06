<?php
// ============================================================
//  api/v1/students.php
// ============================================================
define('APP_ROOT', dirname(dirname(__DIR__)));
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/db.php';

header('Content-Type: application/json; charset=utf-8');

// GET — danh sách học sinh
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(DB::getStudents(), JSON_UNESCAPED_UNICODE);
    exit;
}

// POST — các action
$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? '';

switch ($action) {

    case 'upsert': {
        $s = $body['student'] ?? [];
        if (empty($s['name'])) { jsonOut(['ok'=>false,'error'=>'Tên không được trống'], 422); }
        $s['name']   = sanitize($s['name']);
        $s['to']     = max(1, min(8, (int)($s['to'] ?? 1)));
        $s['hang']   = isset($s['hang'])   && $s['hang']   ? (int)$s['hang']   : null;
        $s['vi_tri'] = isset($s['vi_tri']) && $s['vi_tri'] ? strtoupper(substr($s['vi_tri'],0,1)) : null;
        $s['chuc_vu']= isset($s['chuc_vu']) ? sanitize($s['chuc_vu']) : null;
        $newId = DB::upsertStudent($s);
        jsonOut(['ok'=>true, 'id'=>$newId]);
    }

    case 'delete': {
        $id = (int)($body['id'] ?? 0);
        if (!$id) jsonOut(['ok'=>false,'error'=>'ID không hợp lệ'], 422);
        DB::deleteStudent($id);
        jsonOut(['ok'=>true]);
    }

    case 'assign': {
        $sid    = (int)($body['sid']    ?? 0);
        $to     = (int)($body['to']     ?? 0);
        $hang   = (int)($body['hang']   ?? 0);
        $vi_tri = strtoupper(substr($body['vi_tri'] ?? 'T', 0, 1));
        if (!$sid || !$to || !$hang) jsonOut(['ok'=>false,'error'=>'Thiếu thông tin'], 422);
        DB::assignDesk($sid, $to, $hang, $vi_tri);
        jsonOut(['ok'=>true]);
    }

    case 'auto_assign': {
        $to       = (int)($body['to'] ?? 1);
        $students = DB::getStudents(['to'=>$to]);
        $i = 0;
        foreach ($students as $s) {
            $hang   = (int)floor($i / 2) + 1;
            $vi_tri = $i % 2 === 0 ? 'T' : 'P';
            DB::assignDesk((int)$s['id'], $to, $hang, $vi_tri);
            $i++;
        }
        jsonOut(['ok'=>true, 'count'=>count($students)]);
    }

    case 'import': {
        $rows = $body['students'] ?? [];
        $mode = in_array($body['mode']??'', ['merge','replace']) ? $body['mode'] : 'merge';
        $count = DB::bulkImport(array_map(function($r) {
            return [
                'id'      => !empty($r['id']) ? (int)$r['id'] : null,
                'name'    => sanitize($r['name']   ?? ''),
                'to'      => max(1, min(8, (int)($r['to'] ?? 1))),
                'hang'    => !empty($r['hang'])   ? (int)$r['hang']   : null,
                'vi_tri'  => !empty($r['vi_tri'])  ? strtoupper(substr($r['vi_tri'],0,1)) : null,
                'ban'     => !empty($r['ban'])     ? (int)$r['ban']   : null,
                'chuc_vu' => !empty($r['chuc_vu']) ? sanitize($r['chuc_vu']) : null,
            ];
        }, $rows), $mode);
        jsonOut(['ok'=>true, 'count'=>$count]);
    }

    default:
        jsonOut(['ok'=>false,'error'=>'Unknown action: '.$action], 400);
}
