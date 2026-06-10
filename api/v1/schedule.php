<?php
// modules/schedule.php  v2.2 — Smart duty rotation
$rules    = DB::getRules();
$students = DB::getStudents();
$week     = max(1,(int)($_GET['week']??date('W')));
$schedule = DB::getSchedule($week);
$toColors = json_decode(TO_COLORS,true);
$days     = dayNames();

// ── Tính tổ trực tuần này ────────────────────────────────────
$baseWeek = (int)($rules['base_week'] ?? 1);
$baseTo   = (int)($rules['base_to']   ?? 1);
$numTo    = 4;
$offset   = (($week - $baseWeek) % $numTo + $numTo) % $numTo;
$dutyTo   = (($baseTo - 1 + $offset) % $numTo) + 1;

// ── Hàm sinh lịch nội bộ ────────────────────────────────────
function buildScheduleLocal(int $dutyTo, array $students, array $rules): array {
    $toSt = array_values(array_filter($students,
        fn($s) => (int)($s['to']??0) === $dutyTo
    ));
    usort($toSt, function($a,$b) {
        $ha=(int)($a['hang']??0); $hb=(int)($b['hang']??0);
        if($ha===0&&$hb===0) return strcmp($a['name']??'',$b['name']??'');
        if($ha===0) return 1; if($hb===0) return -1;
        return $ha<=>$hb?:strcmp($a['vi_tri']??'',$b['vi_tri']??'');
    });
    $early=[]; $late=[];
    foreach($toSt as $s) {
        $h=(int)($s['hang']??0);
        if($h===0){$early[]=$s;$late[]=$s;}
        elseif($h<=3){$early[]=$s;}
        else{$late[]=$s;}
    }
    $n = max(1,(int)($rules['duty_per_day']??4));
    $sched = [];
    for ($d=0; $d<5; $d++) {
        $pool = $d<=2 ? $early : $late;
        $sched[$d] = ['to'=>$dutyTo,'students'=>array_slice($pool,0,min($n,count($pool)))];
    }
    return $sched;
}

// ── Tự sinh lịch nếu chưa có HOẶC lịch cũ không khớp tổ tuần này ────
// Lấy tổ từ ngày đầu tiên trong lịch hiện có
$savedTo = (int)($schedule[0]['to'] ?? 0);
$needRegen = empty($schedule) || $savedTo !== $dutyTo;

if ($needRegen) {
    $schedule = buildScheduleLocal($dutyTo, $students, $rules);
    DB::saveSchedule($week, $schedule);
}

// ── Lấy IDs học sinh trực hôm nay (để highlight vòng đỏ) ────
$dow = max(0,(int)date('N')-1); // 0=T2 … 4=T6
$dutyTodaySids = array_column($schedule[$dow]['students'] ?? [], 'id');
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
  <span>📋 Tuần này: <strong style="color:<?=$toColors[$dutyTo]['text']?>"><?=$toColors[$dutyTo]['label']?> trực</strong></span>
  <span>👥 Mặc định: <strong><?=$rules['duty_per_day']?> người/ngày</strong></span>
  <?php if($rules['allow_override']): ?><span class="tag tag-green">✓ Cho phép thêm tự do</span><?php endif; ?>
  <button class="btn-secondary" style="padding:3px 10px;font-size:11px" onclick="openModal('baseModal')"
          title="Đặt tổ bắt đầu cho tuần này">🔧 Đặt tổ gốc</button>
</div>

<div class="schedule-grid" id="scheduleGrid">
  <?php foreach($schedule as $di=>$day):
    if(!isset($days[$di])) continue;
    $c   = $toColors[$day['to']]??$toColors[1];
    $ppl = $day['students']??[];
    // Ngày hôm nay?
    $isToday = ($di === $dow && $week === (int)date('W'));
  ?>
  <div class="day-card <?=$isToday?'day-card-today':''?>" data-week="<?=$week?>" data-day="<?=$di?>">
    <div class="day-header" style="border-bottom:2px solid <?=$c['border']?>">
      <span class="day-name"><?=$days[$di]?><?=$isToday?' <span class="tag tag-green" style="font-size:9px">Hôm nay</span>':''?></span>
      <span class="to-badge" style="background:<?=$c['bg']?>;color:<?=$c['text']?>"><?=$c['label']?></span>
    </div>
    <ul class="duty-list sortable-list" id="dutyList-<?=$di?>" data-week="<?=$week?>" data-day="<?=$di?>">
      <?php foreach($ppl as $st):
        // Vòng đỏ = người này đang trực HÔM NAY (đúng ngày trong tuần hiện tại)
        $isOnDutyNow = $isToday && in_array($st['id'], $dutyTodaySids);
      ?>
        <li class="duty-person <?=$isOnDutyNow?'duty-person-active':''?>" data-sid="<?=$st['id']?>">
          <div class="avatar-sm <?=$isOnDutyNow?'avatar-duty':''?>"
               style="background:<?=$c['bg']?>;color:<?=$c['text']?>"><?=getInitials($st['name'])?></div>
          <span title="<?=htmlspecialchars($st['name'])?>"><?=htmlspecialchars($st['name'])?></span>
          <?php if(!empty($st['chuc_vu'])): ?>
            <span class="tag tag-wood" style="font-size:9px"><?=htmlspecialchars($st['chuc_vu'])?></span>
          <?php endif; ?>
          <?php if(!empty($st['hang'])): ?>
            <span class="tag tag-gray" style="font-size:9px">H<?=$st['hang']?><?=$st['vi_tri']??''?></span>
          <?php endif; ?>
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

<!-- Modal đặt tổ gốc -->
<div id="baseModal" class="popup hidden" role="dialog" aria-modal="true" aria-labelledby="baseTitle">
  <div class="popup-box">
    <div class="popup-header">
      <span id="baseTitle">🔧 Đặt tổ gốc xoay vòng</span>
      <button onclick="closeModal('baseModal')" aria-label="Đóng">✕</button>
    </div>
    <div class="popup-body">
      <p class="muted" style="margin-bottom:10px">
        Hệ thống sẽ tính tổ trực cho mọi tuần dựa trên mốc này.<br>
        <strong>Tuần <?=$week?></strong> đang tính là <strong><?=$toColors[$dutyTo]['label']?></strong> trực.
      </p>
      <label class="field-label" for="baseToSelect">Tuần <?=$week?> (hiện tại) là tổ nào trực?</label>
      <select id="baseToSelect" class="field-select" title="Chọn tổ gốc">
        <?php for($t=1;$t<=4;$t++): ?>
          <option value="<?=$t?>" <?=$dutyTo===$t?'selected':''?>><?=$toColors[$t]['label']?></option>
        <?php endfor; ?>
      </select>
      <p class="muted" style="margin-top:8px;font-size:11px">
        Sau khi lưu, nhấn "Tạo lại" để áp dụng cho tuần này.
      </p>
    </div>
    <div class="popup-footer">
      <button class="btn-secondary" onclick="closeModal('baseModal')">Hủy</button>
      <button class="btn-primary" onclick="doSetBase()">💾 Lưu mốc</button>
    </div>
  </div>
</div>

<script>
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
  if(!confirm('Tạo lại lịch tuần này theo quy tắc tự động?')) return;
  App.regenerateWeek(w).then(r=>{ if(r.ok!==false) location.reload(); });
}

function doSetBase() {
  const baseTo = +document.getElementById('baseToSelect').value;
  App.post('api/v1/schedule.php', { action:'set_base', week:<?=$week?>, base_to:baseTo })
    .then(r=>{
      closeModal('baseModal');
      if(r.ok!==false){ App.toast('Đã lưu mốc ✓'); }
      else App.toast('Lỗi: '+(r.error||'?'),'error');
    });
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

<style>
/* Vòng đỏ học sinh đang trực hôm nay */
.avatar-duty {
  outline: 2.5px solid var(--red);
  outline-offset: 2px;
  box-shadow: 0 0 0 4px rgba(192,57,43,.15);
}
.duty-person-active {
  background: var(--red-bg) !important;
  border-color: var(--red) !important;
}
/* Highlight card ngày hôm nay */
.day-card-today {
  border: 2px solid var(--board) !important;
  box-shadow: 0 0 0 3px rgba(44,122,44,.15), var(--sh-md) !important;
}
</style>