<?php
// ============================================================
//  modules/schedule.php
//  Module 2: Lịch trực nhật
//  Phân công: Dev C
//  Chức năng:
//    - Hiển thị tuần theo 5 ngày (T2–T6)
//    - Mỗi ngày: 1 tổ, 2 bàn, N người (mặc định 4, có thể tuỳ chỉnh)
//    - Cho phép kéo-thả thay đổi thứ tự / thêm người ngoài 4
//    - Tự động xoay vòng tổ trực
//    - Cho phép thêm ngày trực thủ công
//    - Lưu vào DB
// ============================================================
$rules   = DB::getRules();
$students = DB::getStudents();

$week = max(1, (int)($_GET['week'] ?? date('W')));
$schedule = DB::getSchedule($week);

// Auto-generate nếu tuần chưa có lịch
if (empty($schedule)) {
    $schedule = autoGenerateWeek($week, $students, $rules);
    DB::saveSchedule($week, $schedule);
}

$days     = dayNames();
$toColors = json_decode(TO_COLORS, true);
?>

<div class="module-schedule">

  <!-- Bar: điều hướng tuần + cài đặt -->
  <div class="module-bar">
    <h2 class="module-title"><i class="ti ti-calendar"></i> Lịch trực nhật</h2>
    <div class="week-nav">
      <a href="?tab=schedule&week=<?= $week - 1 ?>" class="btn-icon <?= $week <= 1 ? 'disabled' : '' ?>">
        <i class="ti ti-chevron-left"></i>
      </a>
      <span class="week-label"><?= weekLabel($week) ?></span>
      <a href="?tab=schedule&week=<?= $week + 1 ?>" class="btn-icon">
        <i class="ti ti-chevron-right"></i>
      </a>
    </div>
    <div class="bar-actions">
      <button class="btn-secondary" onclick="openRulesModal()">
        <i class="ti ti-settings"></i> Luật trực
      </button>
      <button class="btn-secondary" onclick="regenerateWeek(<?= $week ?>)">
        <i class="ti ti-refresh"></i> Tạo lại
      </button>
      <button class="btn-primary" onclick="addExtraDay(<?= $week ?>)">
        <i class="ti ti-plus"></i> Thêm ngày trực
      </button>
    </div>
  </div>

  <!-- Info bar -->
  <div class="info-bar">
    <span><i class="ti ti-clock"></i> Buổi: <strong><?= DUTY_SESSION ?></strong></span>
    <span><i class="ti ti-users"></i> Mặc định: <strong><?= $rules['duty_per_day'] ?> người/ngày</strong></span>
    <span><i class="ti ti-rotate-clockwise"></i> Xoay vòng theo: <strong><?= $rules['rotate_by'] === 'to' ? 'Tổ' : 'Thủ công' ?></strong></span>
    <?php if ($rules['allow_override']): ?>
      <span class="tag-green"><i class="ti ti-check"></i> Cho phép vượt quá mặc định</span>
    <?php endif; ?>
  </div>

  <!-- Lịch 5 ngày -->
  <div class="schedule-grid" id="scheduleGrid">
    <?php foreach ($schedule as $dayIdx => $day):
      if (!isset($days[$dayIdx])) continue;
      $c = $toColors[$day['to']] ?? $toColors[1];
      $dutyStudents = $day['students'] ?? [];
    ?>
      <div class="day-card" data-week="<?= $week ?>" data-day="<?= $dayIdx ?>">

        <div class="day-header" style="border-bottom:2px solid <?= $c['border'] ?>">
          <span class="day-name"><?= $days[$dayIdx] ?></span>
          <span class="to-badge" style="background:<?= $c['bg'] ?>;color:<?= $c['text'] ?>">
            <?= $c['label'] ?>
          </span>
        </div>

        <!-- Danh sách người trực (kéo được) -->
        <ul class="duty-list sortable-list" id="dutyList-<?= $dayIdx ?>"
            data-week="<?= $week ?>" data-day="<?= $dayIdx ?>">
          <?php foreach ($dutyStudents as $st): ?>
            <li class="duty-person" data-sid="<?= $st['id'] ?>">
              <div class="avatar-sm" style="background:<?= $c['bg'] ?>;color:<?= $c['text'] ?>">
                <?= getInitials($st['name']) ?>
              </div>
              <span><?= htmlspecialchars($st['name']) ?></span>
              <button class="btn-icon-xs remove-duty"
                      onclick="removeDutyPerson(<?= $week ?>, <?= $dayIdx ?>, <?= $st['id'] ?>)"
                      title="Xoá khỏi ca trực">
                <i class="ti ti-x"></i>
              </button>
            </li>
          <?php endforeach; ?>
          <?php if (empty($dutyStudents)): ?>
            <li class="duty-empty">Chưa phân công</li>
          <?php endif; ?>
        </ul>

        <div class="day-footer">
          <span class="count-label">
            <?= count($dutyStudents) ?> người
            <?php if ($rules['allow_override'] && count($dutyStudents) > $rules['duty_per_day']): ?>
              <span class="tag-amber">+<?= count($dutyStudents) - $rules['duty_per_day'] ?></span>
            <?php endif; ?>
          </span>
          <button class="btn-add-person"
                  onclick="openAddPersonModal(<?= $week ?>, <?= $dayIdx ?>)"
                  title="Thêm người trực vào ngày này">
            <i class="ti ti-user-plus"></i>
          </button>
        </div>

      </div>
    <?php endforeach; ?>
  </div><!-- /.schedule-grid -->

  <!-- Nút thêm ngày trực bổ sung (ngoài T2–T6) -->
  <div class="extra-days-section">
    <h3 class="section-sub">Ngày trực bổ sung</h3>
    <?php
    $extraDays = array_filter($schedule, fn($d, $k) => $k >= 5, ARRAY_FILTER_USE_BOTH);
    if (empty($extraDays)): ?>
      <p class="muted">Chưa có ngày trực bổ sung trong tuần này.</p>
    <?php else:
      foreach ($extraDays as $idx => $day):
        $c = $toColors[$day['to']] ?? $toColors[1];
    ?>
        <div class="extra-day-chip" style="border-color:<?= $c['border'] ?>">
          <span><?= htmlspecialchars($day['date'] ?? "Ngày bổ sung " . ($idx - 4)) ?></span>
          <span class="to-badge" style="background:<?= $c['bg'] ?>;color:<?= $c['text'] ?>">
            <?= $c['label'] ?>
          </span>
          <span><?= count($day['students']) ?> người</span>
          <button onclick="removeExtraDay(<?= $week ?>, <?= $idx ?>)" class="btn-icon-xs">
            <i class="ti ti-trash"></i>
          </button>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div><!-- /.module-schedule -->

<!-- Modal: Thêm người trực -->
<div id="addPersonModal" class="popup hidden">
  <div class="popup-box">
    <div class="popup-header">
      <span>Thêm người trực</span>
      <button onclick="closeModal('addPersonModal')"><i class="ti ti-x"></i></button>
    </div>
    <div class="popup-body">
      <label class="field-label">Tìm học sinh</label>
      <input type="text" id="addPersonSearch" class="field-input" placeholder="Nhập tên..." oninput="filterAddList()" />
      <ul id="addPersonList" class="picker-list">
        <?php foreach ($students as $s):
          $c = $toColors[$s['to']] ?? $toColors[1];
        ?>
          <li data-sid="<?= $s['id'] ?>" data-name="<?= htmlspecialchars($s['name']) ?>"
              onclick="selectAddPerson(<?= $s['id'] ?>)">
            <div class="avatar-sm" style="background:<?= $c['bg'] ?>;color:<?= $c['text'] ?>">
              <?= getInitials($s['name']) ?>
            </div>
            <?= htmlspecialchars($s['name']) ?>
            <span class="muted"><?= $toColors[$s['to']]['label'] ?? '' ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="popup-footer">
      <input type="hidden" id="addPersonWeek" />
      <input type="hidden" id="addPersonDay" />
      <button class="btn-secondary" onclick="closeModal('addPersonModal')">Hủy</button>
    </div>
  </div>
</div>

<!-- Modal: Cài đặt luật trực -->
<div id="rulesModal" class="popup hidden">
  <div class="popup-box">
    <div class="popup-header">
      <span><i class="ti ti-settings"></i> Luật phân công trực nhật</span>
      <button onclick="closeModal('rulesModal')"><i class="ti ti-x"></i></button>
    </div>
    <div class="popup-body">
      <label class="field-label">Số người trực mặc định / ngày</label>
      <input type="number" id="rulesDutyPerDay" class="field-input" min="1" max="20"
             value="<?= $rules['duty_per_day'] ?>" />

      <label class="field-label" style="margin-top:12px">Số người tối đa / ngày (giới hạn mềm)</label>
      <input type="number" id="rulesMaxPerDay" class="field-input" min="1" max="30"
             value="<?= $rules['max_per_day'] ?>" />

      <label class="field-label toggle-label" style="margin-top:12px">
        <input type="checkbox" id="rulesAllowOverride" <?= $rules['allow_override'] ? 'checked' : '' ?> />
        Cho phép thêm người vượt quá mặc định (huỷ luật tự do)
      </label>

      <label class="field-label" style="margin-top:12px">Kiểu xoay vòng</label>
      <select id="rulesRotateBy" class="field-select">
        <option value="to"     <?= $rules['rotate_by'] === 'to'     ? 'selected' : '' ?>>Theo tổ (tự động)</option>
        <option value="manual" <?= $rules['rotate_by'] === 'manual' ? 'selected' : '' ?>>Thủ công hoàn toàn</option>
      </select>
    </div>
    <div class="popup-footer">
      <button class="btn-secondary" onclick="closeModal('rulesModal')">Hủy</button>
      <button class="btn-primary" onclick="saveRules()">Lưu luật</button>
    </div>
  </div>
</div>

<?php
// ── Auto-generate week schedule ──────────────────────────────
function autoGenerateWeek(int $week, array $students, array $rules): array {
    $schedule = [];
    $toOrder  = [1, 2, 3, 4, 1]; // T2–T6 → Tổ 1,2,3,4,1
    $n        = $rules['duty_per_day'];

    for ($d = 0; $d < DUTY_DAYS_PER_WEEK; $d++) {
        $to        = $toOrder[$d];
        $toStudents = array_values(array_filter($students, fn($s) => $s['to'] == $to));
        // chọn N người đầu tiên (hoặc tất cả nếu ít hơn N)
        $chosen    = array_slice($toStudents, 0, min($n, count($toStudents)));
        $schedule[$d] = ['to' => $to, 'students' => $chosen];
    }
    return $schedule;
}
?>

<script>
// Kéo-thả reorder trong ngày
document.querySelectorAll('.sortable-list').forEach(list => {
  Sortable.create(list, {
    animation: 150,
    onEnd(evt) {
      const week = list.dataset.week;
      const day  = list.dataset.day;
      const order = [...list.querySelectorAll('.duty-person')].map(li => li.dataset.sid);
      AppStore.reorderDuty(parseInt(week), parseInt(day), order).then(() => {});
    }
  });
});

function removeDutyPerson(week, day, sid) {
  AppStore.removeDutyPerson(week, day, sid).then(() => location.reload());
}

function openAddPersonModal(week, day) {
  document.getElementById('addPersonWeek').value = week;
  document.getElementById('addPersonDay').value  = day;
  document.getElementById('addPersonSearch').value = '';
  filterAddList();
  document.getElementById('addPersonModal').classList.remove('hidden');
}

function filterAddList() {
  const q = document.getElementById('addPersonSearch').value.toLowerCase();
  document.querySelectorAll('#addPersonList li').forEach(li => {
    li.style.display = li.dataset.name.toLowerCase().includes(q) ? '' : 'none';
  });
}

function selectAddPerson(sid) {
  const week = document.getElementById('addPersonWeek').value;
  const day  = document.getElementById('addPersonDay').value;
  AppStore.addDutyPerson(parseInt(week), parseInt(day), parseInt(sid))
    .then(() => { closeModal('addPersonModal'); location.reload(); });
}

function openRulesModal() {
  document.getElementById('rulesModal').classList.remove('hidden');
}

function saveRules() {
  const rules = {
    duty_per_day:   parseInt(document.getElementById('rulesDutyPerDay').value),
    max_per_day:    parseInt(document.getElementById('rulesMaxPerDay').value),
    allow_override: document.getElementById('rulesAllowOverride').checked,
    rotate_by:      document.getElementById('rulesRotateBy').value,
  };
  AppStore.saveRules(rules).then(() => { closeModal('rulesModal'); location.reload(); });
}

function regenerateWeek(week) {
  if (!confirm('Tạo lại lịch tuần này? Dữ liệu tuần này sẽ bị ghi đè.')) return;
  AppStore.regenerateWeek(week).then(() => location.reload());
}

function addExtraDay(week) {
  const date = prompt('Nhập ngày trực bổ sung (ví dụ: Thứ 7 20/06):', '');
  if (!date) return;
  const to = parseInt(prompt('Tổ trực (1–4):', '1'));
  if (isNaN(to) || to < 1 || to > 4) { alert('Tổ không hợp lệ.'); return; }
  AppStore.addExtraDay(week, date, to).then(() => location.reload());
}

function removeExtraDay(week, idx) {
  AppStore.removeExtraDay(week, idx).then(() => location.reload());
}

function closeModal(id) {
  document.getElementById(id).classList.add('hidden');
}
</script>