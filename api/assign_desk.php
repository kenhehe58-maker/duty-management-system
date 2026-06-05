<?php
// ============================================================
//  api/assign_desk.php
//  Xử lý lưu vị trí kéo thả / popup sơ đồ lớp vào MySQL
// ============================================================
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/includes/db.php';

// Nhận dữ liệu JSON gửi từ trình duyệt qua lệnh fetch()
$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $sid    = $data['sid'] ?? null;
    $to     = $data['to'];
    $hang   = $data['hang'];
    $vi_tri = $data['vi_tri'];

    // Lấy danh sách học sinh hiện tại từ MySQL
    $students = DB::getStudents();

    // 1. Nếu vị trí mới này đã có học sinh khác ngồi, đẩy bạn đó ra chỗ trống
    foreach ($students as &$s) {
        if ($s['to'] == $to && $s['hang'] == $hang && strtoupper($s['vi_tri']) === strtoupper($vi_tri)) {
            $s['to'] = 0; 
            $s['hang'] = 0; 
            $s['vi_tri'] = '';
        }
    }
    unset($s); // Giải phóng biến tham chiếu

    // 2. Cập nhật vị trí mới cho bạn học sinh được chọn (nếu có chọn)
    if ($sid) {
        foreach ($students as &$s) {
            if ($s['id'] == $sid) {
                $s['to'] = $to;
                $s['hang'] = $hang;
                $s['vi_tri'] = $vi_tri;
                break;
            }
        }
        unset($s);
    }

    // 3. Lưu mảng học sinh đã cập nhật đè lại vào MySQL
    $result = DB::saveStudents($students);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => $result]);
    exit;
}

header('Content-Type: application/json');
echo json_encode(['success' => false]);