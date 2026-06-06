<?php
// modules/schedule.php  v2.1
$rules    = DB::getRules();
$students = DB::getStudents();
$week     = max(1,(int)($_GET['week']??date('W')));
$schedule = DB::getSchedule($week);
$toColors = json_decode(TO_COLORS,true);
$days     = dayNames();

if(empty($schedule)) {
    $toOrder=[1,2,3,4,1];
    $n=$rules['duty_per_day'];
    for($d=0;$d<DUTY_DAYS_PER_WEEK;$d++){
        $to=$toOrder[$d];
        $ts=array_values(array_filter($students,fn($s)=>$s['to']==$to));
        $schedule[$d]=['to'=>$to,'students'=>array_slice($ts,0,min($n,count($ts)))];
    }
    DB::saveSchedule($week,$schedule);
}
?>
<div class="module-schedule">

<div class="module-bar">
  <h2 class="module-title">📅 Lịch trực nhật</h2>
  <div class="week-nav">
    <a href="?tab=schedule&week=<?=$week-1?>" class="btn-icon <?=$week<=1?'disabled':''?>"
       aria-label="Tuần trước" title="Tuần trước">‹</a>
    <span class="week-label"><?=weekLabel($week)?></span>
    <a href="?tab=schedule&week=<?=$week+1?>" class="btn-icon"
       aria-label="Tuần sau" title="Tuần sau">›</a>
  </div>
  <div class="bar-actions">
    <button class="btn-secondary" onclick="openModal('rulesModal')" aria-label="Cài đặt luật trực">
      ⚙️ Luật trực
    </button>
    <button class="btn-secondary" onclick="doRegen(<?=$week?>)" aria-label="Tạo lại lịch">
      🔄 Tạo lại
    </button>
    <button class="btn-primary" onclick="doAddExtra(<?=$week?>)" aria-label="Thêm ngày trực">
      ➕ Thêm ngày
    </button>
  </div>
</div>

<div class="info-bar">
  <span>⏰ Buổi: <strong><?=DUTY_SESSION?></strong></span>
  <span>👥 Mặc định: <strong><?=$rules['duty_per_day']?> người/ngày</strong></span>
  <span>🔄 Xoay: <strong><?=$rules['rotate_by']==='to'?'Theo tổ':'Thủ công'?></strong></span>
  <?php if($rules['allow_override']): ?><span class="tag tag-green">✓ Cho phép thêm tự do</span><?php endif; ?>
</div>

<div class="schedule-grid" id="scheduleGrid">
  <?php foreach($schedule as $di=>$day):
    if(!isset($days[$di])) continue;
    $c=$toColors[$day['to']]??$toColors[1];
    $ppl=$day['students']??[];
  ?>
  <div class="day-card" data-week="<?=$week?>" data-day="<?=$di?>">
    <div class="day-header" style="border-bottom:2px solid <?=$c['border']?>">
      <span class="day-name"><?=$days[$di]?></span>
      <span class="to-badge" style="background:<?=$c['bg']?>;color:<?=$c['text']?>"><?=$c['label']?></span>
    </div>
    <ul class="duty-list sortable-list" id="dutyList-<?=$di?>" data-week="<?=$week?>" data-day="<?=$di?>">
      <?php foreach($ppl as $st): ?>
        <li class="duty-person" data-sid="<?=$st['id']?>">
          <div class="avatar-sm" style="background:<?=$c['bg']?>;color:<?=$c['text']?>"><?=getInitials($st['name'])?></div>
          <span title="<?=htmlspecialchars($st['name'])?>"><?=htmlspecialchars($st['name'])?></span>
          <?php if(!empty($st['chuc_vu'])): ?><span class="tag tag-wood" style="font-size:9px"><?=htmlspecialchars($st['chuc_vu'])?></span><?php endif; ?>
          <button class="btn-icon-xs" onclick="doRemove(<?=$week?>,<?=$di?>,<?=$st['id']?>)"
                  aria-label="Xoá <?=htmlspecialchars($st['name'])?> khỏi ca trực" title="Xoá">✕</button>
        </li>
      <?php endforeach; ?>
      <?php if(empty($ppl)): ?><li class="duty-empty">Chưa phân công</li><?php endif; ?>
    </ul>
    <div class="day-footer">
      <span class="count-label">
        <?=count($ppl)?> người
        <?php if($rules['allow_override']&&count($ppl)>$rules['duty_per_day']): ?>
          <span class="tag tag-amber">+<?=count($ppl)-$rules['duty_per_day']?></span>
        <?php endif; ?>
      </span>
      <button class="btn-add-person" onclick="doAddPerson(<?=$week?>,<?=$di?>)"
              aria-label="Thêm người trực ngày này" title="Thêm người trực">➕</button>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Ngày trực bổ sung -->
<div class="extra-days-section">
  <h3 class="section-sub">Ngày trực bổ sung</h3>
  <?php
  $extras=array_filter($schedule,fn($d,$k)=>$k>=5,ARRAY_FILTER_USE_BOTH);
  if(empty($extras)): ?><p class="muted">Chưa có ngày trực bổ sung trong tuần này.</p>
  <?php else: foreach($extras as $idx=>$day):
    $c=$toColors[$day['to']]??$toColors[1]; ?>
    <div class="extra-day-chip" style="border-color:<?=$c['border']?>">
      <span><?=htmlspecialchars($day['date']??'Ngày bổ sung '.($idx-4))?></span>
      <span class="to-badge" style="background:<?=$c['bg']?>;color:<?=$c['text']?>"><?=$c['label']?></span>
      <span><?=count($day['students'])?> người</span>
      <button class="btn-icon-xs" onclick="doRemoveExtra(<?=$week?>,<?=$idx?>)"
              aria-label="Xoá ngày trực bổ sung" title="Xoá">🗑</button>
    </div>
  <?php endforeach; endif; ?>
</div>
</div>

<!-- Modal thêm người -->
<div id="addPersonModal" class="popup hidden" role="dialog" aria-modal="true" aria-labelledby="addPersonTitle">
  <div class="popup-box">
    <div class="popup-header">
      <span id="addPersonTitle">Thêm người trực</span>
      <button onclick="closeModal('addPersonModal')" aria-label="Đóng">✕</button>
    </div>
    <div class="popup-body">
      <label class="field-label" for="addPersonSearch">Tìm học sinh</label>
      <input id="addPersonSearch" class="field-input" placeholder="Nhập tên..."
             aria-label="Tìm học sinh" oninput="filterAddList()"/>
      <ul id="addPersonList" class="picker-list" role="listbox" aria-label="Danh sách học sinh">
        <?php foreach($students as $s):
          $c=$toColors[$s['to']]??$toColors[1]; ?>
          <li data-sid="<?=$s['id']?>" data-name="<?=htmlspecialchars($s['name'])?>"
              onclick="doSelectPerson(<?=$s['id']?>)"
              role="option" tabindex="0"
              onkeydown="if(event.key==='Enter')doSelectPerson(<?=$s['id']?>)">
            <div class="avatar-sm" style="background:<?=$c['bg']?>;color:<?=$c['text']?>"><?=getInitials($s['name'])?></div>
            <?=htmlspecialchars($s['name'])?>
            <?php if(!empty($s['chuc_vu'])): ?><span class="chip-role"><?=htmlspecialchars($s['chuc_vu'])?></span><?php endif; ?>
            <span class="muted"><?=$c['label']?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="popup-footer">
      <input type="hidden" id="apWeek"/><input type="hidden" id="apDay"/>
      <button class="btn-secondary" onclick="closeModal('addPersonModal')">Hủy</button>
    </div>
  </div>
</div>

<!-- Modal luật -->
<div id="rulesModal" class="popup hidden" role="dialog" aria-modal="true" aria-labelledby="rulesTitle">
  <div class="popup-box">
    <div class="popup-header">
      <span id="rulesTitle">⚙️ Luật phân công trực nhật</span>
      <button onclick="closeModal('rulesModal')" aria-label="Đóng">✕</button>
    </div>
    <div class="popup-body">
      <label class="field-label" for="rDutyPerDay">Số người trực mặc định / ngày</label>
      <input id="rDutyPerDay" class="field-input" type="number" min="1" max="20" value="<?=$rules['duty_per_day']?>"/>
      <label class="field-label" for="rMaxPerDay" style="margin-top:10px">Tối đa người / ngày</label>
      <input id="rMaxPerDay"   class="field-input" type="number" min="1" max="50" value="<?=$rules['max_per_day']?>"/>
      <label class="toggle-label" style="margin-top:10px">
        <input type="checkbox" id="rAllowOverride" <?=$rules['allow_override']?'checked':''?>/>
        Cho phép thêm người vượt mặc định
      </label>
      <label class="field-label" for="rRotateBy" style="margin-top:10px">Kiểu xoay vòng</label>
      <select id="rRotateBy" class="field-select" title="Kiểu xoay vòng">
        <option value="to" <?=$rules['rotate_by']==='to'?'selected':''?>>Theo tổ (tự động)</option>
        <option value="manual" <?=$rules['rotate_by']==='manual'?'selected':''?>>Thủ công</option>
      </select>
    </div>
    <div class="popup-footer">
      <button class="btn-secondary" onclick="closeModal('rulesModal')">Hủy</button>
      <button class="btn-primary" onclick="doSaveRules()">💾 Lưu luật</button>
    </div>
  </div>
</div>

<script>
// Sortable
document.querySelectorAll('.sortable-list').forEach(list => {
  if(typeof Sortable==='undefined') return;
  Sortable.create(list, { animation:150, handle:'.duty-person',
    onEnd() {
      const sids=[...list.querySelectorAll('.duty-person')].map(li=>+li.dataset.sid);
      App.reorderDuty(+list.dataset.week,+list.dataset.day,sids);
    }
  });
});

function doRemove(w,d,sid) {
  App.removeDutyPerson(w,d,sid).then(r=>{ if(r.ok!==false) location.reload(); else App.toast('Lỗi','error'); });
}

function doAddPerson(w,d) {
  document.getElementById('apWeek').value=w;
  document.getElementById('apDay').value=d;
  document.getElementById('addPersonSearch').value='';
  filterAddList();
  openModal('addPersonModal');
}

function filterAddList() {
  const q=document.getElementById('addPersonSearch').value.toLowerCase();
  document.querySelectorAll('#addPersonList li').forEach(li=>{
    li.style.display=li.dataset.name.toLowerCase().includes(q)?'':'none';
  });
}

function doSelectPerson(sid) {
  const w=+document.getElementById('apWeek').value;
  const d=+document.getElementById('apDay').value;
  App.addDutyPerson(w,d,sid).then(r=>{
    closeModal('addPersonModal');
    if(r.ok!==false) location.reload(); else App.toast(r.error||'Lỗi','error');
  });
}

function doSaveRules() {
  App.saveRules({
    duty_per_day: +document.getElementById('rDutyPerDay').value,
    max_per_day:  +document.getElementById('rMaxPerDay').value,
    allow_override: document.getElementById('rAllowOverride').checked,
    rotate_by: document.getElementById('rRotateBy').value,
  }).then(r=>{ closeModal('rulesModal'); if(r.ok!==false) location.reload(); });
}

function doRegen(w) {
  if(!confirm('Tạo lại lịch tuần này?')) return;
  App.regenerateWeek(w).then(r=>{ if(r.ok!==false) location.reload(); });
}

function doAddExtra(w) {
  const date=prompt('Ngày trực bổ sung (VD: Thứ 7 20/06):','');
  if(!date) return;
  const to=parseInt(prompt('Tổ trực (1–4):','1'));
  if(!to||to<1||to>4){App.toast('Tổ không hợp lệ','error');return;}
  App.addExtraDay(w,date,to).then(r=>{ if(r.ok!==false) location.reload(); });
}

function doRemoveExtra(w,idx) {
  App.removeExtraDay(w,idx).then(r=>{ if(r.ok!==false) location.reload(); });
}
</script>