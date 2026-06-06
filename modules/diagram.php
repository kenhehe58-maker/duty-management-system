<?php
// modules/diagram.php — Sơ đồ lớp (v2.1 fixed)
$students  = DB::getStudents();
$toColors  = json_decode(TO_COLORS, true);
$sched     = DB::getSchedule((int)date('W'));
$dow       = max(0, (int)date('N') - 1);
$dutyToday = $sched[$dow] ?? [];

function findStudent(array $a, int $to, int $row, string $pos): ?array {
    foreach ($a as $s) {
        if ($s['to']==$to && $s['hang']==$row && strtoupper($s['vi_tri']===$pos?$s['vi_tri']:'')===$pos) return $s;
    }
    return null;
}
function isDutySlot(array $duty, int $to, int $row): bool {
    if (empty($duty['to'])) return false;
    return $duty['to']==$to && $row<=2;
}
function renderDesk(?array $st, int $to, int $row, string $pos, array $c, bool $duty): string {
    $id   = "desk-t{$to}-r{$row}-{$pos}";
    $init = $st ? getInitials($st['name']) : '+';
    $sid  = $st['id'] ?? 0;
    $tip  = $st ? "{$st['name']} · Tổ {$to} H{$row}{$pos}" : "Tổ {$to} H{$row}{$pos} (trống)";
    $dc   = $duty ? ' desk-duty' : '';
    return "<div id=\"{$id}\" class=\"desk{$dc}\""
      ." style=\"background:{$c['bg']};border-color:{$c['border']};color:{$c['text']}\""
      ." data-to=\"{$to}\" data-row=\"{$row}\" data-pos=\"{$pos}\" data-sid=\"{$sid}\""
      ." draggable=\"true\" onclick=\"openDeskPopup({$to},{$row},'{$pos}')\""
      ." title=\"{$tip}\" aria-label=\"{$tip}\">"
      .htmlspecialchars($init)
      ."<span class=\"desk-tip\">{$tip}</span>"
      ."</div>";
}
?>
<div class="module-diagram">

<div class="module-bar">
  <h2 class="module-title">🗺️ Sơ đồ bố trí lớp học</h2>
  <div class="legend">
    <?php foreach($toColors as $i=>$c): ?>
      <span class="legend-chip" style="background:<?=$c['bg']?>;color:<?=$c['text']?>;border-color:<?=$c['border']?>">
        <?=$c['label']?>
      </span>
    <?php endforeach; ?>
    <!-- FIX: legend đang trực dùng border thay vì fill để không nhầm với Tổ 4 -->
    <span class="legend-chip legend-duty">Đang trực hôm nay</span>
  </div>
</div>

<div class="diagram-wrap">

  <!-- LEFT: bảng + bục giảng + bàn GV + 4 tổ -->
  <div class="diagram-left">
    <div class="blackboard">✏️ BẢNG ĐEN</div>

    <!-- FIX: Bục giảng BÊN TRÁI, Bàn GV BÊN PHẢI (nhìn từ bảng xuống) -->
    <div class="podium-row">
      <div class="podium-box">
        <span class="podium-label">🎤 Bục giảng</span>
        <span class="podium-note">Đứng từ hành lang nhìn vào,<br>lấy hết bục + tường bảng trong 1 khung</span>
      </div>
      <div class="teacher-box">
        <span>🪑 Bàn giáo viên</span>
        <small>Góc trái · cùng tường với bảng</small>
        <small style="color:var(--ocean);font-size:9px">📷 Chụp từ hành lang nhìn vào</small>
      </div>
    </div>

    <!-- Column headers — FIX: Tổ 1 bên trái nhất (phía hành lang) -->
    <div class="desk-header-row">
      <div style="width:22px"></div>
      <?php for($to=1;$to<=NUM_TO;$to++): $c=$toColors[$to]; ?>
        <div class="to-header" style="color:<?=$c['text']?>">
          <span><?=$c['label']?></span>
          <small>T / P</small>
        </div>
        <?php if($to<NUM_TO): ?>
          <div class="path-spacer-header"><?=$to?></div>
        <?php endif; ?>
      <?php endfor; ?>
    </div>

    <div class="desk-rows" id="deskRows">
      <?php for($row=1;$row<=NUM_ROWS;$row++): ?>
        <div class="desk-row" data-row="<?=$row?>">
          <div class="row-num">H<?=$row?></div>
          <?php for($to=1;$to<=NUM_TO;$to++):
            $c=$toColors[$to];
            $stT=findStudent($students,$to,$row,'T');
            $stP=findStudent($students,$to,$row,'P');
            $isDuty=isDutySlot($dutyToday,$to,$row);
          ?>
            <?=renderDesk($stT,$to,$row,'T',$c,$isDuty)?>
            <?=renderDesk($stP,$to,$row,'P',$c,$isDuty)?>
            <?php if($to<NUM_TO): ?>
              <div class="path-line path-<?=$to?>" aria-label="Đường đi <?=$to?>"></div>
            <?php endif; ?>
          <?php endfor; ?>
        </div>
      <?php endfor; ?>
    </div>

    <div class="path-legend">
      <div class="path-legend-item"><span class="path-dot dot-1"></span>Đường đi 1</div>
      <div class="path-legend-item"><span class="path-dot dot-2"></span>Đường đi 2</div>
      <div class="path-legend-item"><span class="path-dot dot-3"></span>Đường đi 3</div>
      <div class="path-legend-item"><span class="duty-box-icon"></span>Đang trực nhật</div>
    </div>
  </div>

  <!-- RIGHT: 6 dãy bàn (từ hành lang nhìn vào) -->
  <div class="diagram-right">
    <div class="side-title">🚪 Bên phải (từ hành lang)</div>
    <p class="side-subtitle">6 dãy · chụp xuyên qua hộp bàn</p>
    <?php for($dy=1;$dy<=NUM_SIDE_ROWS;$dy++): ?>
      <div class="side-row-group">
        <span class="side-row-label">Dãy <?=$dy?></span>
        <div class="side-desk" title="Dãy <?=$dy?> Trái" aria-label="Dãy <?=$dy?> Trái">D<?=$dy?>T</div>
        <div class="side-desk" title="Dãy <?=$dy?> Phải" aria-label="Dãy <?=$dy?> Phải">D<?=$dy?>P</div>
        <div class="shoot-arrow" aria-hidden="true">→</div>
      </div>
    <?php endfor; ?>
    <div class="side-note">
      📷 <span>Chụp từ đầu dãy → cuối dãy, góc thấp xuyên qua hộp bàn để kiểm tra bên trong</span>
    </div>
  </div>

</div>
</div>

<!-- Popup gán chỗ ngồi -->
<div id="deskPopup" class="popup hidden" role="dialog" aria-modal="true" aria-labelledby="popupTitle">
  <div class="popup-box">
    <div class="popup-header">
      <span id="popupTitle">Chỗ ngồi</span>
      <button onclick="closeModal('deskPopup')" aria-label="Đóng">✕</button>
    </div>
    <div class="popup-body" id="popupBody"></div>
    <div class="popup-footer">
      <button class="btn-secondary" onclick="closeModal('deskPopup')">Đóng</button>
      <button class="btn-primary" onclick="saveDeskAssign()">💾 Lưu</button>
    </div>
  </div>
</div>

<script>
const _allStudents = <?=json_encode($students, JSON_UNESCAPED_UNICODE)?>;

// Drag & drop
document.querySelectorAll('.desk').forEach(el => {
  el.addEventListener('dragstart', e => {
    e.dataTransfer.setData('sid', el.dataset.sid);
    e.dataTransfer.setData('from', el.id);
    el.style.opacity = '.5';
  });
  el.addEventListener('dragend',  e => { el.style.opacity = ''; });
  el.addEventListener('dragover', e => { e.preventDefault(); el.classList.add('drag-over'); });
  el.addEventListener('dragleave',e => { el.classList.remove('drag-over'); });
  el.addEventListener('drop', e => {
    e.preventDefault();
    el.classList.remove('drag-over');
    const sid = e.dataTransfer.getData('sid');
    if (!sid || e.dataTransfer.getData('from') === el.id) return;
    App.assignDesk(+sid, +el.dataset.to, +el.dataset.row, el.dataset.pos)
       .then(r => { if(r.ok!==false) location.reload(); else App.toast('Lỗi gán chỗ','error'); });
  });
});

function openDeskPopup(to, row, pos) {
  const desk = document.getElementById(`desk-t${to}-r${row}-${pos}`);
  const sid  = desk?.dataset.sid || '';
  document.getElementById('popupTitle').textContent = `Tổ ${to} · Hàng ${row} · ${pos==='T'?'Trái':'Phải'}`;
  document.getElementById('popupBody').innerHTML = `
    <label class="field-label" for="popupSelect">Học sinh tại vị trí này</label>
    <select id="popupSelect" class="field-select" title="Chọn học sinh">
      <option value="">— Trống —</option>
      ${_allStudents.map(s=>`<option value="${s.id}" ${s.id==sid?'selected':''}>${s.name} (Tổ ${s.to})</option>`).join('')}
    </select>
    <input type="hidden" id="pTo" value="${to}"/>
    <input type="hidden" id="pRow" value="${row}"/>
    <input type="hidden" id="pPos" value="${pos}"/>
  `;
  openModal('deskPopup');
}

function saveDeskAssign() {
  const sid = document.getElementById('popupSelect').value;
  const to  = +document.getElementById('pTo').value;
  const row = +document.getElementById('pRow').value;
  const pos = document.getElementById('pPos').value;
  if (!sid) { App.toast('Vui lòng chọn học sinh','info'); return; }
  App.assignDesk(+sid, to, row, pos).then(r => {
    closeModal('deskPopup');
    if(r.ok!==false) { App.toast('Đã lưu chỗ ngồi'); location.reload(); }
    else App.toast('Lỗi lưu','error');
  });
}
</script>
