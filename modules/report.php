<?php
// ============================================================
//  modules/report.php
//  Module 4: Báo cáo ảnh trực nhật
//  Phân công: Dev D
//  Chức năng:
//    - Checklist 6 loại ảnh CẦN chụp (có hướng dẫn chi tiết)
//    - Upload ảnh trực tiếp (hoặc chụp từ camera trên mobile)
//    - Lưu theo ngày
//    - Xem lại các ngày trước
//    - Đánh dấu hoàn thành từng mục
// ============================================================

$today = date('Y-m-d');
$reportDate = $_GET['rdate'] ?? $today;
$report = DB::getReport($reportDate);

// Danh mục ảnh cần chụp (có hướng dẫn góc chụp chi tiết)
$photoSpecs = [
  [
    'key'      => 'podium',
    'label'    => 'Bục giảng & Bảng đen',
    'icon'     => 'ti-chalkboard',
    'required' => true,
    'guide'    => 'Đứng ngoài hành lang, nhìn qua cửa/cửa sổ vào lớp. Lấy hết bục giảng + bảng đen + tường bên trái trong một khung hình. Đảm bảo thấy bàn giáo viên.',
    'angle'    => 'Hành lang → nhìn vào',
    'example'  => 'Bảng sạch, bục giảng gọn, ghế GV vào vị trí',
  ],
  [
    'key'      => 'teacher_desk',
    'label'    => 'Bàn giáo viên',
    'icon'     => 'ti-armchair',
    'required' => true,
    'guide'    => 'Chụp thẳng góc từ trước bàn GV. Lấy hết mặt bàn, ghế GV, và khu vực xung quanh (cùng mặt tường với bảng, góc trái lớp nhìn từ bảng xuống).',
    'angle'    => 'Phía trước bàn GV',
    'example'  => 'Bàn gọn gàng, không có đồ vật lộn xộn',
  ],
  [
    'key'      => 'aisle_1',
    'label'    => 'Đường đi 1 (giữa Tổ 1–2)',
    'icon'     => 'ti-road',
    'required' => true,
    'guide'    => 'Đứng từ đầu đường đi (phía bảng), chụp dọc xuống. Lấy hết sàn đường đi từ đầu đến cuối. Kiểm tra không có rác, cặp sách tràn ra.',
    'angle'    => 'Từ bảng nhìn xuống cuối lớp',
    'example'  => 'Đường đi thông thoáng, sàn sạch',
  ],
  [
    'key'      => 'aisle_2',
    'label'    => 'Đường đi 2 (giữa Tổ 2–3)',
    'icon'     => 'ti-road',
    'required' => true,
    'guide'    => 'Tương tự đường đi 1. Đứng đầu lối, chụp dọc toàn bộ đường đi, kiểm tra hai bên bàn không có cặp tràn ra lối đi.',
    'angle'    => 'Từ bảng nhìn xuống cuối lớp',
    'example'  => 'Lối đi rộng, không vướng cặp/dép',
  ],
  [
    'key'      => 'aisle_3',
    'label'    => 'Đường đi 3 (giữa Tổ 3–4)',
    'icon'     => 'ti-road',
    'required' => true,
    'guide'    => 'Tương tự đường đi 1 & 2.',
    'angle'    => 'Từ bảng nhìn xuống cuối lớp',
    'example'  => 'Lối đi rộng, sàn sạch',
  ],
  [
    'key'      => 'desk_boxes',
    'label'    => 'Hộp bàn học sinh (6 dãy)',
    'icon'     => 'ti-box',
    'required' => true,
    'guide'    => 'Đứng từ đầu dãy (phía hành lang/cửa phụ), chụp dọc theo dãy từ đầu đến cuối, góc thấp để nhìn XUYệN QUA hộp bàn. Cần thấy bên trong hộp từng bàn. Chụp đủ 6 dãy, mỗi dãy 1 ảnh riêng.',
    'angle'    => 'Đầu dãy → dọc xuống cuối, góc thấp xuyên hộp',
    'example'  => 'Hộp bàn gọn, không có rác/thức ăn bên trong',
    'multi'    => true,
    'count'    => 6,
  ],
  [
    'key'      => 'board_written',
    'label'    => 'Bảng ghi (sau buổi học)',
    'icon'     => 'ti-writing',
    'required' => false,
    'guide'    => 'Chụp thẳng bảng đen sau tiết học. Ghi lại nội dung bảng (nếu GV yêu cầu lưu) hoặc xác nhận bảng đã được xoá sạch trước khi ra về.',
    'angle'    => 'Thẳng góc với bảng, từ giữa lớp',
    'example'  => 'Bảng xoá sạch hoặc lưu bài ghi',
  ],
];
?>

<div class="module-report">

  <div class="module-bar">
    <h2 class="module-title"><i class="ti ti-camera"></i> Báo cáo ảnh trực nhật</h2>
    <div class="date-nav">
      <input type="date" id="reportDatePicker" class="field-input-sm"
             value="<?= $reportDate ?>"
             onchange="window.location='?tab=report&rdate='+this.value" />
      <span class="tag-<?= $reportDate === $today ? 'green' : 'gray' ?>">
        <?= $reportDate === $today ? 'Hôm nay' : $reportDate ?>
      </span>
    </div>
    <div class="bar-actions">
      <button class="btn-secondary" onclick="exportReport('<?= $reportDate ?>')">
        <i class="ti ti-download"></i> Xuất báo cáo
      </button>
    </div>
  </div>

  <!-- Tóm tắt tiến độ -->
  <?php
  $done    = 0;
  $total   = count($photoSpecs);
  foreach ($photoSpecs as $spec) {
      if (!empty($report[$spec['key']])) $done++;
  }
  $pct = $total > 0 ? round($done / $total * 100) : 0;
  ?>
  <div class="progress-bar-wrap">
    <div class="progress-bar-track">
      <div class="progress-bar-fill" style="width:<?= $pct ?>%"></div>
    </div>
    <span class="progress-label"><?= $done ?>/<?= $total ?> mục · <?= $pct ?>% hoàn thành</span>
  </div>

  <!-- Danh sách mục cần chụp -->
  <div class="photo-checklist">
    <?php foreach ($photoSpecs as $spec):
      $uploaded = $report[$spec['key']] ?? [];
      $isDone   = !empty($uploaded);
      $isMulti  = $spec['multi'] ?? false;
      $count    = $spec['count'] ?? 1;
    ?>
      <div class="photo-item <?= $isDone ? 'done' : '' ?>" id="photo-<?= $spec['key'] ?>">

        <div class="photo-item-header">
          <div class="photo-item-icon <?= $isDone ? 'icon-done' : 'icon-pending' ?>">
            <i class="ti <?= $spec['icon'] ?>"></i>
          </div>
          <div class="photo-item-info">
            <div class="photo-item-title">
              <?= $spec['label'] ?>
              <?php if ($spec['required']): ?>
                <span class="tag-red">Bắt buộc</span>
              <?php else: ?>
                <span class="tag-gray">Tuỳ chọn</span>
              <?php endif; ?>
              <?php if ($isMulti): ?>
                <span class="tag-blue"><?= count($uploaded) ?>/<?= $count ?> ảnh</span>
              <?php endif; ?>
            </div>

            <!-- Hướng dẫn chụp -->
            <div class="photo-guide">
              <div class="guide-angle">
                <i class="ti ti-camera-rotate"></i>
                <strong>Góc chụp:</strong> <?= $spec['angle'] ?>
              </div>
              <div class="guide-text"><?= $spec['guide'] ?></div>
              <div class="guide-example">
                <i class="ti ti-check"></i> <?= $spec['example'] ?>
              </div>
            </div>
          </div>

          <div class="photo-item-actions">
            <?php if ($isDone): ?>
              <span class="done-badge"><i class="ti ti-check"></i> Đã chụp</span>
            <?php endif; ?>
            <label class="btn-upload" for="upload-<?= $spec['key'] ?>">
              <i class="ti ti-upload"></i>
              <?= $isMulti ? 'Thêm ảnh' : 'Chọn ảnh' ?>
            </label>
            <input type="file" id="upload-<?= $spec['key'] ?>"
                   accept="image/*" capture="environment"
                   <?= $isMulti ? 'multiple' : '' ?>
                   onchange="uploadPhoto('<?= $spec['key'] ?>', '<?= $reportDate ?>', this)"
                   style="display:none" />
          </div>
        </div>

        <!-- Preview ảnh đã upload -->
        <?php if (!empty($uploaded)): ?>
          <div class="photo-preview-row" id="preview-<?= $spec['key'] ?>">
            <?php foreach ($uploaded as $imgPath): ?>
              <div class="photo-thumb">
                <img src="<?= htmlspecialchars($imgPath) ?>" alt="<?= $spec['label'] ?>" />
                <button class="thumb-delete"
                        onclick="deletePhoto('<?= $spec['key'] ?>', '<?= $reportDate ?>', '<?= $imgPath ?>')">
                  <i class="ti ti-x"></i>
                </button>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="photo-placeholder" id="preview-<?= $spec['key'] ?>">
            <i class="ti ti-photo"></i>
            <span>Chưa có ảnh</span>
          </div>
        <?php endif; ?>

      </div>
    <?php endforeach; ?>
  </div>

</div><!-- /.module-report -->

<script>
async function uploadPhoto(key, date, input) {
  const files = [...input.files];
  if (!files.length) return;
  const formData = new FormData();
  formData.append('key', key);
  formData.append('date', date);
  files.forEach(f => formData.append('photos[]', f));
  const res = await fetch('api/upload_photo.php', { method: 'POST', body: formData });
  const json = await res.json();
  if (json.ok) location.reload();
  else alert('Lỗi upload: ' + (json.error || 'Không xác định'));
}

async function deletePhoto(key, date, path) {
  if (!confirm('Xoá ảnh này?')) return;
  const res = await AppStore.deletePhoto(key, date, path);
  if (res.ok) location.reload();
}

async function exportReport(date) {
  window.open('api/export_report.php?date=' + date, '_blank');
}
</script>