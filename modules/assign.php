<?php
// modules/assign.php  v2.1 — Phân công chỗ ngồi
$students = DB::getStudents();
$toColors = json_decode(TO_COLORS, true);

$byTo = [];
for ($t=1;$t<=NUM_TO;$t++) $byTo[$t]=[];
foreach ($students as $s) {
    $t=(int)($s['to']??0);
    if($t>=1&&$t<=NUM_TO) $byTo[$t][]=$s;
}
$unassigned = array_values(array_filter($students,fn($s)=>empty($s['hang'])||empty($s['vi_tri'])));

function findStudentA(array $arr,int $row,string $pos):?array {
    foreach($arr as $s){if($s['hang']==$row&&strtoupper($s['vi_tri']??'')===$pos)return $s;}
    return null;
}
function renderChip(array $s,array $c):string {
    $init=getInitials($s['name']);
    $name=htmlspecialchars($s['name'],ENT_QUOTES);
    $role=!empty($s['chuc_vu'])?"<span class=\"chip-role\">".htmlspecialchars($s['chuc_vu'])."</span>":'';
    return "<div class=\"student-chip\" draggable=\"true\" data-sid=\"{$s['id']}\""
      ." ondragstart=\"event.dataTransfer.setData('sid','{$s['id']}')\" onclick=\"openEditStudent({$s['id']})\">"
      ."<div class=\"avatar-sm\" style=\"background:{$c['bg']};color:{$c['text']}\">{$init}</div>"
      ."<div class=\"chip-info\"><span class=\"chip-name\">{$name}</span><span class=\"chip-meta\">Tổ {$s['to']} · H{$s['hang']}{$s['vi_tri']}</span>{$role}</div>"
      ."<span class=\"drag-handle\" aria-hidden=\"true\">⠿</span>"
      ."</div>";
}
?>
<div class="module-assign">

<div class="module-bar">
  <h2 class="module-title">👤 Phân công chỗ ngồi</h2>
  <div class="bar-actions">
    <select id="filterTo" class="field-select-sm" title="Lọc theo tổ" aria-label="Lọc theo tổ" onchange="filterByTo(this.value)">
      <option value="">Tất cả tổ</option>
      <?php for($t=1;$t<=NUM_TO;$t++): ?><option value="<?=$t?>">Tổ <?=$t?></option><?php endfor; ?>
    </select>
    <input type="text" id="searchName" class="field-input-sm" placeholder="Tìm tên..."
           title="Tìm kiếm học sinh" aria-label="Tìm kiếm học sinh" oninput="searchStudents(this.value)"/>
    <button class="btn-primary" onclick="openAddStudentModal()" aria-label="Thêm học sinh mới">
      👤 Thêm học sinh
    </button>
  </div>
</div>

<!-- Stats -->
<div class="stats-row">
  <div class="stat-card"><span class="stat-val"><?=count($students)?></span><span class="stat-label">Tổng học sinh</span></div>
  <?php foreach($byTo as $t=>$arr): $c=$toColors[$t]; ?>
    <div class="stat-card" style="border-top:3px solid <?=$c['border']?>">
      <span class="stat-val" style="color:<?=$c['text']?>"><?=count($arr)?></span>
      <span class="stat-label"><?=$c['label']?></span>
    </div>
  <?php endforeach; ?>
  <div class="stat-card stat-warn"><span class="stat-val"><?=count($unassigned)?></span><span class="stat-label">Chưa có chỗ</span></div>
</div>

<!-- Lưới theo tổ -->
<div id="assignGrid">
  <?php foreach($byTo as $t=>$arr):
    $c=$toColors[$t];
    if(empty($arr)) continue;
  ?>
  <div class="to-section" data-to="<?=$t?>">
    <div class="to-section-header" style="background:<?=$c['bg']?>;border-left:4px solid <?=$c['border']?>">
      <span style="color:<?=$c['text']?>;font-weight:700"><?=$c['label']?></span>
      <span class="muted"><?=count($arr)?> học sinh</span>
      <button class="btn-secondary" style="padding:4px 10px;font-size:11px" onclick="autoAssignTo(<?=$t?>)"
              aria-label="Tự động xếp chỗ Tổ <?=$t?>">⚡ Tự động xếp</button>
    </div>
    <div class="seat-grid">
      <div class="seat-grid-header"><span>Hàng</span><span>Chỗ Trái (T)</span><span>Chỗ Phải (P)</span></div>
      <?php for($row=1;$row<=NUM_ROWS;$row++):
        $stT=findStudentA($arr,$row,'T');
        $stP=findStudentA($arr,$row,'P');
      ?>
      <div class="seat-row" data-to="<?=$t?>" data-row="<?=$row?>">
        <div class="seat-row-label">H<?=$row?></div>
        <div class="seat-cell" ondragover="event.preventDefault();this.classList.add('drag-over')"
             ondragleave="this.classList.remove('drag-over')"
             ondrop="dropToSeat(event,<?=$t?>,<?=$row?>,'T')">
          <?=$stT?renderChip($stT,$c):'<div class="seat-empty">Kéo vào đây</div>'?>
        </div>
        <div class="seat-cell" ondragover="event.preventDefault();this.classList.add('drag-over')"
             ondragleave="this.classList.remove('drag-over')"
             ondrop="dropToSeat(event,<?=$t?>,<?=$row?>,'P')">
          <?=$stP?renderChip($stP,$c):'<div class="seat-empty">Kéo vào đây</div>'?>
        </div>
      </div>
      <?php endfor; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- Chưa có chỗ -->
  <?php if(!empty($unassigned)): ?>
  <div class="to-section">
    <div class="to-section-header" style="background:#FEF3C7;border-left:4px solid #D97706">
      <span style="color:#92400E;font-weight:700">⚠️ Chưa có chỗ ngồi</span>
      <span class="muted"><?=count($unassigned)?> học sinh</span>
    </div>
    <div class="unassigned-list">
      <?php foreach($unassigned as $s):
        $c2=$toColors[$s['to']??1]??$toColors[1];
        echo renderChip($s,$c2);
      endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
</div>

<!-- Modal thêm/sửa học sinh -->
<div id="addStudentModal" class="popup hidden" role="dialog" aria-modal="true" aria-labelledby="addStudentTitle">
  <div class="popup-box">
    <div class="popup-header">
      <span id="addStudentTitle">Thêm / Sửa học sinh</span>
      <button onclick="closeModal('addStudentModal')" aria-label="Đóng">✕</button>
    </div>
    <div class="popup-body">
      <input type="hidden" id="editStudentId"/>
      <label class="field-label" for="studentName">Họ và tên <span class="req">*</span></label>
      <input id="studentName" class="field-input" placeholder="Nguyễn Văn A" aria-required="true"/>

      <label class="field-label" for="studentTo" style="margin-top:10px">Tổ <span class="req">*</span></label>
      <select id="studentTo" class="field-select" title="Chọn tổ" aria-label="Chọn tổ">
        <?php for($t=1;$t<=NUM_TO;$t++): ?><option value="<?=$t?>">Tổ <?=$t?></option><?php endfor; ?>
      </select>

      <label class="field-label" for="studentChucVu" style="margin-top:10px">Chức vụ</label>
      <select id="studentChucVu" class="field-select" title="Chọn chức vụ" aria-label="Chọn chức vụ">
        <option value="">— Học sinh —</option>
        <option value="Lớp trưởng">Lớp trưởng</option>
        <option value="Lớp phó">Lớp phó</option>
        <option value="Tổ trưởng">Tổ trưởng</option>
        <option value="Tổ phó">Tổ phó</option>
        <option value="Bí thư">Bí thư</option>
      </select>

      <label class="field-label" for="studentRow" style="margin-top:10px">Hàng (1–<?=NUM_ROWS?>)</label>
      <input id="studentRow" class="field-input" type="number" min="1" max="<?=NUM_ROWS?>" placeholder="Để trống = chưa xếp"/>

      <label class="field-label" for="studentPos" style="margin-top:10px">Vị trí</label>
      <select id="studentPos" class="field-select" title="Chọn vị trí" aria-label="Chọn vị trí">
        <option value="">— Chưa xác định —</option>
        <option value="T">Trái (T)</option>
        <option value="P">Phải (P)</option>
      </select>
    </div>
    <div class="popup-footer">
      <button class="btn-secondary" onclick="closeModal('addStudentModal')">Hủy</button>
      <button class="btn-danger" id="deleteStudentBtn" onclick="doDeleteStudent()" style="display:none" aria-label="Xoá học sinh">🗑 Xoá</button>
      <button class="btn-primary" onclick="doSaveStudent()" aria-label="Lưu thông tin học sinh">💾 Lưu</button>
    </div>
  </div>
</div>

<script>
function dropToSeat(e,to,row,pos){
  e.preventDefault();
  e.currentTarget.classList.remove('drag-over');
  const sid=e.dataTransfer.getData('sid');
  if(!sid) return;
  App.assignDesk(+sid,to,row,pos).then(r=>{
    if(r.ok!==false) location.reload();
    else App.toast('Lỗi gán chỗ: '+(r.error||'?'),'error');
  });
}

function openAddStudentModal(){
  document.getElementById('editStudentId').value='';
  document.getElementById('studentName').value='';
  document.getElementById('studentTo').value='1';
  document.getElementById('studentChucVu').value='';
  document.getElementById('studentRow').value='';
  document.getElementById('studentPos').value='';
  document.getElementById('deleteStudentBtn').style.display='none';
  openModal('addStudentModal');
}

// openEditStudent defined in app.js — uses App.getStudents()

function doSaveStudent(){
  const id=document.getElementById('editStudentId').value;
  const data={
    id:id?+id:null,
    name:document.getElementById('studentName').value.trim(),
    to:+document.getElementById('studentTo').value,
    hang:+document.getElementById('studentRow').value||null,
    vi_tri:document.getElementById('studentPos').value||null,
    chuc_vu:document.getElementById('studentChucVu').value||null,
  };
  if(!data.name){App.toast('Vui lòng nhập họ tên','error');return;}
  App.saveStudent(data).then(r=>{
    closeModal('addStudentModal');
    if(r.ok!==false){App.toast('Đã lưu học sinh ✓');location.reload();}
    else App.toast('Lỗi lưu: '+(r.error||'?'),'error');
  });
}

function doDeleteStudent(){
  const id=+document.getElementById('editStudentId').value;
  if(!id||!confirm('Xoá học sinh này?')) return;
  App.deleteStudent(id).then(r=>{
    closeModal('addStudentModal');
    if(r.ok!==false) location.reload();
    else App.toast('Lỗi xoá','error');
  });
}

function autoAssignTo(to){
  if(!confirm('Tự động xếp chỗ ngồi Tổ '+to+'?\nDữ liệu chỗ ngồi cũ của tổ này sẽ bị thay thế.')) return;
  App.autoAssignTo(to).then(r=>{
    if(r.ok!==false){App.toast('Đã xếp chỗ '+r.count+' học sinh ✓');location.reload();}
    else App.toast('Lỗi: '+(r.error||'?'),'error');
  });
}

function filterByTo(val){
  document.querySelectorAll('.to-section[data-to]').forEach(el=>{
    el.style.display=(!val||el.dataset.to===val)?'':'none';
  });
}

function searchStudents(q){
  q=q.toLowerCase();
  document.querySelectorAll('.student-chip').forEach(el=>{
    const n=el.querySelector('.chip-name')?.textContent.toLowerCase()||'';
    el.style.display=n.includes(q)?'':'none';
  });
}
</script>
