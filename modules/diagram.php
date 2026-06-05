<?php
// ============================================================
//  modules/diagram.php
//  Module 1: Sơ đồ lớp học
//  Phân công: Dev B
//  Chức năng:
//    - Vẽ 4 tổ × 6 hàng (chính diện từ bảng)
//    - 3 đường đi dọc (giữa các tổ)
//    - 6 dãy bàn bên phải (nhìn từ hành lang)
//    - Khu bục giảng + bàn GV (góc trái)
//    - Hover = tên HS; click = popup chỉnh sửa
//    - Kéo-thả HS sang chỗ khác
// ============================================================
$students   = DB::getStudents();
$toColors   = json_decode(TO_COLORS, true);
$dutyToday  = []; // sẽ được fill từ schedule nếu có
$todaySchedule = DB::getSchedule((int)date('W'));
if (!empty($todaySchedule)) {
    $dow = (int)date('N') - 1; // 0=Mon..4=Fri
    $dutyToday = $todaySchedule[$dow] ?? [];
}
?>

<div class="module-diagram">

  <div class="module-bar">
    <h2 class="module-title"><i class="ti ti-layout-grid"></i> Sơ đồ bố trí lớp học</h2>
    <div class="legend">
      <?php foreach ($toColors as $i => $c): ?>
        <span class="legend-chip" style="background:<?= $c['bg'] ?>;color:<?= $c['text'] ?>;border-color:<?= $c['border'] ?>">
          <?= $c['label'] ?>
        </span>
      <?php endforeach; ?>
      <span class="legend-chip legend-duty">⬛ Đang trực hôm nay</span>
    </div>
  </div>

  <div class="diagram-wrap">

    <div class="diagram-left">

      <div class="blackboard">
        <i class="ti ti-writing"></i> BẢNG ĐEN
      </div>

      <div class="podium-row">
        <div class="podium-box">
          <div class="podium-inner">
            <span class="podium-label">🎤 Bục giảng</span>
            <span class="podium-note">Chụp từ hành lang nhìn vào,<br>lấy hết bục + tường bảng</span>
          </div>
        </div>
        <div class="teacher-box">
          <i class="ti ti-armchair"></i>
          <span>Bàn giáo viên</span>
          <small>Góc trái · cùng tường với bảng</small>
        </div>
      </div>

      <div class="desk-header-row">
        <div class="row-num-spacer"></div>
        <?php for ($to = 1; $to <= NUM_TO; $to++):
          $c = $toColors[$to];
        ?>
          <div class="to-header" style="color:<?= $c['text'] ?>">
            <span><?= $c['label'] ?></span>
            <small>T&nbsp;/&nbsp;P</small>
          </div>
          <?php if ($to < NUM_TO): ?>
            <div class="path-spacer-header">
              <span class="path-number"><?= $to ?></span>
            </div>
          <?php endif; ?>
        <?php endfor; ?>
      </div>

      <div class="desk-rows" id="deskRows">
        <?php for ($row = 1; $row <= NUM_ROWS; $row++): ?>
          <div class="desk-row" data-row="<?= $row ?>">
            <div class="row-num">H<?= $row ?></div>

            <?php for ($to = 1; $to <= NUM_TO; $to++):
              $c = $toColors[$to];
              // Find students T and P
              $stT = findStudent($students, $to, $row, 'T');
              $stP = findStudent($students, $to, $row, 'P');
              $isDuty = isDutySlot($dutyToday, $to, $row);
            ?>

              <?= renderDesk($stT, $to, $row, 'T', $c, $isDuty) ?>
              <?= renderDesk($stP, $to, $row, 'P', $c, $isDuty) ?>

              <?php if ($to < NUM_TO): ?>
                <div class="path-line path-<?= $to ?>" title="Đường đi <?= $to ?>">
                  <span class="path-label"><?= $to ?></span>
                </div>
              <?php endif; ?>

            <?php endfor; ?>
          </div>
        <?php endfor; ?>
      </div>

      <div class="path-legend">
        <div class="path-legend-item"><span class="path-dot dot-1"></span> Đường đi 1</div>
        <div class="path-legend-item"><span class="path-dot dot-2"></span> Đường đi 2</div>
        <div class="path-legend-item"><span class="path-dot dot-3"></span> Đường đi 3</div>
        <div class="path-legend-item"><span class="duty-box-icon"></span> Đang trực nhật hôm nay</div>
      </div>
    </div>

    <div class="diagram-right">
      <div class="side-title">
        <i class="ti ti-door-enter"></i> Bên phải (từ hành lang)
      </div>
      <p class="side-subtitle">6 dãy · chụp xuyên qua hộp bàn</p>

      <?php for ($dy = 1; $dy <= NUM_SIDE_ROWS; $dy++): ?>
        <div class="side-row-group">
          <span class="side-row-label">Dãy <?= $dy ?></span>
          <div class="side-desk side-desk-T" title="Dãy <?= $dy ?> · Trái">D<?= $dy ?>T</div>
          <div class="side-desk side-desk-P" title="Dãy <?= $dy ?> · Phải">D<?= $dy ?>P</div>
          <div class="shoot-arrow" title="Hướng chụp từ đầu dãy → cuối dãy">
            <i class="ti ti-arrow-right"></i>
          </div>
        </div>
      <?php endfor; ?>

      <div class="side-note">
        <i class="ti ti-camera"></i>
        <span>Chụp từ đầu dãy → cuối dãy,<br>xuyên qua hộp bàn để kiểm tra vệ sinh bên trong bàn</span>
      </div>
    </div>

  </div></div><div id="deskPopup" class="popup hidden">
  <div class="popup-box">
    <div class="popup-header">
      <span id="popupTitle">Chỗ ngồi</span>
      <button onclick="closePopup()"><i class="ti ti-x"></i></button>
    </div>
    <div class="popup-body" id="popupBody"></div>
    <div class="popup-footer">
      <button class="btn-secondary" onclick="closePopup()">Đóng</button>
      <button class="btn-primary" onclick="saveDeskAssign()">Lưu</button>
    </div>
  </div>
</div>

<?php
// ── Helper functions (only used in this module) ──────────────

function findStudent(array $students, int $to, int $row, string $pos): ?array {
    foreach ($students as $s) {
        if ($s['to'] == $to && $s['hang'] == $row && strtoupper($s['vi_tri']) === $pos) {
            return $s;
        }
    }
    return null;
}

function isDutySlot(array $duty, int $to, int $row): bool {
    if (empty($duty['to'])) return false;
    return $duty['to'] == $to && $row <= 2;
}

function renderDesk(?array $st, int $to, int $row, string $pos, array $c, bool $isDuty): string {
    $id   = "desk-t{$to}-r{$row}-{$pos}";
    $name = $st ? htmlspecialchars($st['name'], ENT_QUOTES) : '';
    $init = $st ? getInitials($st['name']) : '+';
    $sid  = $st['id'] ?? '';
    $duty = $isDuty ? ' desk-duty' : '';
    $tip  = $st ? $st['name'] . " · Tổ {$to} H{$row}{$pos}" : "Tổ {$to} H{$row}{$pos} (trống)";

    return <<<HTML
    <div id="{$id}"
         class="desk{$duty}"
         style="background:{$c['bg']};border-color:{$c['border']};color:{$c['text']}"
         data-to="{$to}" data-row="{$row}" data-pos="{$pos}" data-sid="{$sid}"
         draggable="true"
         onclick="openDeskPopup({$to},{$row},'{$pos}')"
         title="{$tip}">
      {$init}
      <span class="desk-tip">{$tip}</span>
    </div>
    HTML;
}
?>
<script>
// Biến chứa mảng học sinh PHP đổ sang để xử lý JS đồng bộ với MySQL
const globalStudentsArray = <?= json_encode($students, JSON_UNESCAPED_UNICODE) ?>;

// Drag-and-drop desk reassignment
document.querySelectorAll('.desk').forEach(el => {
  el.addEventListener('dragstart', e => {
    e.dataTransfer.setData('sid', el.dataset.sid);
    e.dataTransfer.setData('from', el.id);
  });
  el.addEventListener('dragover', e => e.preventDefault());
  el.addEventListener('drop', e => {
    e.preventDefault();
    const sid = e.dataTransfer.getData('sid');
    const fromId = e.dataTransfer.getData('from');
    if (!sid || fromId === el.id) return;
    const to  = el.dataset.to;
    const row = el.dataset.row;
    const pos = el.dataset.pos;
    
    // Gọi API xử lý cập nhật MySQL
    fetch('api/assign_desk.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ sid: parseInt(sid), to: parseInt(to), hang: parseInt(row), vi_tri: pos })
    }).then(() => location.reload());
  });
});

function openDeskPopup(to, row, pos) {
  const desk = document.getElementById(`desk-t${to}-r${row}-${pos}`);
  const sid  = desk?.dataset.sid;
  document.getElementById('popupTitle').textContent = `Tổ ${to} · Hàng ${row} · ${pos === 'T' ? 'Trái' : 'Phải'}`;
  
  const body = document.getElementById('popupBody');
  body.innerHTML = `
    <label class="field-label">Học sinh tại vị trí này</label>
    <select id="popupSelect" class="field-select">
      <option value="">— Trống —</option>
      ${globalStudentsArray.map(s => `<option value="${s.id}" ${s.id == sid ? 'selected' : ''}>${s.name} (Tổ ${s.to})</option>`).join('')}
    </select>
    <input type="hidden" id="popupTo" value="${to}" />
    <input type="hidden" id="popupRow" value="${row}" />
    <input type="hidden" id="popupPos" value="${pos}" />
  `;
  document.getElementById('deskPopup').classList.remove('hidden');
}

function closePopup() {
  document.getElementById('deskPopup').classList.add('hidden');
}

function saveDeskAssign() {
  const sid = document.getElementById('popupSelect').value;
  const to  = document.getElementById('popupTo').value;
  const row = document.getElementById('popupRow').value;
  const pos = document.getElementById('popupPos').value;
  
  fetch('api/assign_desk.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ sid: sid ? parseInt(sid) : null, to: parseInt(to), hang: parseInt(row), vi_tri: pos })
  }).then(() => { closePopup(); location.reload(); });
}
</script>