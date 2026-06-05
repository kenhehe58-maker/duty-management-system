<?php
// ============================================================
//  modules/assign.php
//  Module 3: Phân công chỗ ngồi học sinh
//  Phân công: Dev C
//  Chức năng:
//    - Hiển thị dạng bảng rõ ràng (Tổ → Bàn → Học sinh)
//    - Lọc theo tổ, tìm theo tên
//    - Kéo-thả học sinh vào chỗ ngồi
//    - Thêm / sửa / xoá học sinh
//    - Học sinh chưa có chỗ ngồi gộp vào "Chưa phân công"
// ============================================================
$students = DB::getStudents();
$toColors = json_decode(TO_COLORS, true);

// Nhóm theo tổ
$byTo = [];
for ($t = 1; $t <= NUM_TO; $t++) $byTo[$t] = [];
foreach ($students as $s) {
    $t = (int)($s['to'] ?? 0);
    if ($t >= 1 && $t <= NUM_TO) $byTo[$t][] = $s;
}

$unassigned = array_values(array_filter($students, fn($s) => empty($s['hang']) || empty($s['vi_tri'])));
?>

<div class="module-assign">

  <div class="module-bar">
    <h2 class="module-title"><i class="ti ti-user-check"></i> Phân công chỗ ngồi</h2>
    <div class="bar-actions">
      <select id="filterTo" class="field-select-sm" onchange="filterByTo(this.value)">
        <option value="">Tất cả tổ</option>
        <?php for ($t = 1; $t <= NUM_TO; $t++): ?>
          <option value="<?= $t ?>">Tổ <?= $t ?></option>
        <?php endfor; ?>
      </select>
      <input type="text" id="searchName" class="field-input-sm" placeholder="Tìm tên..."
             oninput="searchStudents(this.value)" />
      <button class="btn-primary" onclick="openAddStudentModal()">
        <i class="ti ti-user-plus"></i> Thêm học sinh
      </button>
    </div>
  </div>

  <!-- Tổng quan -->
  <div class="stats-row">
    <div class="stat-card">
      <span class="stat-val"><?= count($students) ?></span>
      <span class="stat-label">Tổng học sinh</span>
    </div>
    <?php foreach ($byTo as $t => $arr): $c = $toColors[$t]; ?>
      <div class="stat-card" style="border-top:3px solid <?= $c['border'] ?>">
        <span class="stat-val" style="color:<?= $c['text'] ?>"><?= count($arr) ?></span>
        <span class="stat-label"><?= $c['label'] ?></span>
      </div>
    <?php endforeach; ?>
    <div class="stat-card stat-warn">
      <span class="stat-val"><?= count($unassigned) ?></span>
      <span class="stat-label">Chưa có chỗ ngồi</span>
    </div>
  </div>

  <!-- Lưới học sinh theo tổ -->
  <div id="assignGrid">
    <?php foreach ($byTo as $t => $arr):
      $c = $toColors[$t];
      if (empty($arr)) continue;
    ?>
      <div class="to-section" data-to="<?= $t ?>">
        <div class="to-section-header" style="background:<?= $c['bg'] ?>;border-left:3px solid <?= $c['border'] ?>">
          <span style="color:<?= $c['text'] ?>;font-weight:500"><?= $c['label'] ?></span>
          <span class="muted"><?= count($arr) ?> học sinh</span>
          <button class="btn-icon-xs" onclick="autoAssignTo(<?= $t ?>)" title="Tự động xếp chỗ Tổ <?= $t ?>">
            <i class="ti ti-wand"></i> Tự động xếp
          </button>
        </div>

        <!-- Chia thành 2 cột: T (trái) và P (phải) cho mỗi bàn/hàng -->
        <div class="seat-grid">
          <div class="seat-grid-header">
            <span>Hàng</span>
            <span>Chỗ Trái (T)</span>
            <span>Chỗ Phải (P)</span>
          </div>
          <?php for ($row = 1; $row <= NUM_ROWS; $row++):
            $stT = findStudentA($arr, $row, 'T');
            $stP = findStudentA($arr, $row, 'P');
          ?>
            <div class="seat-row" data-to="<?= $t ?>" data-row="<?= $row ?>">
              <div class="seat-row-label">H<?= $row ?></div>

              <!-- Chỗ T -->
              <div class="seat-cell dropzone"
                   data-to="<?= $t ?>" data-row="<?= $row ?>" data-pos="T"
                   ondragover="event.preventDefault()"
                   ondrop="dropToSeat(event, <?= $t ?>, <?= $row ?>, 'T')">
                <?php if ($stT): ?>
                  <?= renderStudentChip($stT, $c) ?>
                <?php else: ?>
                  <div class="seat-empty">Kéo vào đây</div>
                <?php endif; ?>
              </div>

              <!-- Chỗ P -->
              <div class="seat-cell dropzone"
                   data-to="<?= $t ?>" data-row="<?= $row ?>" data-pos="P"
                   ondragover="event.preventDefault()"
                   ondrop="dropToSeat(event, <?= $t ?>, <?= $row ?>, 'P')">
                <?php if ($stP): ?>
                  <?= renderStudentChip($stP, $c) ?>
                <?php else: ?>
                  <div class="seat-empty">Kéo vào đây</div>
                <?php endif; ?>
              </div>
            </div>
          <?php endfor; ?>
        </div>
      </div>
    <?php endforeach; ?>

    <!-- Chưa phân công -->
    <?php if (!empty($unassigned)): ?>
      <div class="to-section">
        <div class="to-section-header" style="background:#FEF3C7;border-left:3px solid #D97706">
          <span style="color:#92400E;font-weight:500">Chưa có chỗ ngồi</span>
          <span class="muted"><?= count($unassigned) ?> học sinh</span>
        </div>
        <div class="unassigned-list">
          <?php foreach ($unassigned as $s):
            $c2 = $toColors[$s['to'] ?? 1] ?? $toColors[1];
          ?>
            <?= renderStudentChip($s, $c2) ?>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

</div><!-- /.module-assign -->

<!-- Modal thêm học sinh -->
<div id="addStudentModal" class="popup hidden">
  <div class="popup-box">
    <div class="popup-header">
      <span>Thêm / Sửa học sinh</span>
      <button onclick="closeModal('addStudentModal')"><i class="ti ti-x"></i></button>
    </div>
    <div class="popup-body">
      <input type="hidden" id="editStudentId" value="" />
      <label class="field-label">Họ và tên <span class="req">*</span></label>
      <input type="text" id="studentName" class="field-input" placeholder="Nguyễn Văn A" />

      <label class="field-label" style="margin-top:10px">Tổ <span class="req">*</span></label>
      <select id="studentTo" class="field-select">
        <?php for ($t = 1; $t <= NUM_TO; $t++): ?>
          <option value="<?= $t ?>">Tổ <?= $t ?></option>
        <?php endfor; ?>
      </select>

      <label class="field-label" style="margin-top:10px">Hàng (1–<?= NUM_ROWS ?>)</label>
      <input type="number" id="studentRow" class="field-input" min="1" max="<?= NUM_ROWS ?>" placeholder="Để trống = chưa xếp" />

      <label class="field-label" style="margin-top:10px">Vị trí</label>
      <select id="studentPos" class="field-select">
        <option value="">— Chưa xác định —</option>
        <option value="T">Trái (T)</option>
        <option value="P">Phải (P)</option>
      </select>
    </div>
    <div class="popup-footer">
      <button class="btn-secondary" onclick="closeModal('addStudentModal')">Hủy</button>
      <button class="btn-danger" id="deleteStudentBtn" onclick="deleteStudent()" style="display:none">
        <i class="ti ti-trash"></i> Xoá
      </button>
      <button class="btn-primary" onclick="saveStudent()">Lưu</button>
    </div>
  </div>
</div>

<?php
function findStudentA(array $arr, int $row, string $pos): ?array {
    foreach ($arr as $s) {
        if ($s['hang'] == $row && strtoupper($s['vi_tri'] ?? '') === $pos) return $s;
    }
    return null;
}

function renderStudentChip(array $s, array $c): string {
    $init = getInitials($s['name']);
    $name = htmlspecialchars($s['name'], ENT_QUOTES);
    return <<<HTML
    <div class="student-chip" draggable="true"
         data-sid="{$s['id']}"
         ondragstart="event.dataTransfer.setData('sid','{$s['id']}')"
         onclick="openEditStudent({$s['id']})">
      <div class="avatar-sm" style="background:{$c['bg']};color:{$c['text']}">{$init}</div>
      <div class="chip-info">
        <span class="chip-name">{$name}</span>
        <span class="chip-meta">Tổ {$s['to']} · H{$s['hang']}{$s['vi_tri']}</span>
      </div>
      <i class="ti ti-grip-vertical drag-handle"></i>
    </div>
    HTML;
}
?>
<script>
function dropToSeat(e, to, row, pos) {
  e.preventDefault();
  const sid = e.dataTransfer.getData('sid');
  if (!sid) return;
  AppStore.assignDesk(parseInt(sid), to, row, pos).then(() => location.reload());
}

function openAddStudentModal() {
  document.getElementById('editStudentId').value = '';
  document.getElementById('studentName').value   = '';
  document.getElementById('studentTo').value     = '1';
  document.getElementById('studentRow').value    = '';
  document.getElementById('studentPos').value    = '';
  document.getElementById('deleteStudentBtn').style.display = 'none';
  document.getElementById('addStudentModal').classList.remove('hidden');
}

function openEditStudent(sid) {
  const s = AppStore.getStudents().find(x => x.id == sid);
  if (!s) return;
  document.getElementById('editStudentId').value = s.id;
  document.getElementById('studentName').value   = s.name;
  document.getElementById('studentTo').value     = s.to;
  document.getElementById('studentRow').value    = s.hang || '';
  document.getElementById('studentPos').value    = s.vi_tri || '';
  document.getElementById('deleteStudentBtn').style.display = '';
  document.getElementById('addStudentModal').classList.remove('hidden');
}

function saveStudent() {
  const id   = document.getElementById('editStudentId').value;
  const data = {
    id:      id ? parseInt(id) : Date.now(),
    name:    document.getElementById('studentName').value.trim(),
    to:      parseInt(document.getElementById('studentTo').value),
    hang:    parseInt(document.getElementById('studentRow').value) || null,
    vi_tri:  document.getElementById('studentPos').value || null,
  };
  if (!data.name) { alert('Vui lòng nhập họ tên.'); return; }
  AppStore.saveStudent(data).then(() => { closeModal('addStudentModal'); location.reload(); });
}

function deleteStudent() {
  const id = document.getElementById('editStudentId').value;
  if (!id || !confirm('Xoá học sinh này?')) return;
  AppStore.deleteStudent(parseInt(id)).then(() => { closeModal('addStudentModal'); location.reload(); });
}

function autoAssignTo(to) {
  AppStore.autoAssignTo(to).then(() => location.reload());
}

function filterByTo(val) {
  document.querySelectorAll('.to-section[data-to]').forEach(el => {
    el.style.display = (!val || el.dataset.to === val) ? '' : 'none';
  });
}

function searchStudents(q) {
  q = q.toLowerCase();
  document.querySelectorAll('.student-chip').forEach(el => {
    const name = el.querySelector('.chip-name')?.textContent.toLowerCase() || '';
    el.style.display = name.includes(q) ? '' : 'none';
  });
}

function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
</script>