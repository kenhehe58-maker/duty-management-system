<?php
// modules/report.php — Báo cáo ảnh (v2.1 fixed)
// FIX: DB::getReport() thay vì DB::getReports()
$today      = date('Y-m-d');
$reportDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['rdate']??'') ? $_GET['rdate'] : $today;
$report     = DB::getReport($reportDate);    // ← đã fix
$dates      = DB::getReportDates();

// Danh mục ảnh (đúng yêu cầu: 3 đường đi + bảng + bàn GV + bục giảng + hành lang)
$photoSpecs = [
  [
    'key'=>'aisle_1','label'=>'Đường đi 1 (Tổ 1–2)','icon'=>'🛤️','required'=>true,
    'guide'=>'Đứng đầu đường đi (phía bảng), chụp dọc xuống. Lấy hết sàn từ đầu đến cuối lớp.',
    'angle'=>'Từ bảng nhìn xuống cuối lớp','example'=>'Sàn sạch, không có rác/cặp tràn ra',
  ],
  [
    'key'=>'aisle_2','label'=>'Đường đi 2 (Tổ 2–3)','icon'=>'🛤️','required'=>true,
    'guide'=>'Tương tự đường đi 1. Chụp dọc toàn bộ lối đi giữa Tổ 2 và Tổ 3.',
    'angle'=>'Từ bảng nhìn xuống cuối lớp','example'=>'Lối đi rộng, không vướng cặp/dép',
  ],
  [
    'key'=>'aisle_3','label'=>'Đường đi 3 (Tổ 3–4)','icon'=>'🛤️','required'=>true,
    'guide'=>'Tương tự đường đi 1 & 2.',
    'angle'=>'Từ bảng nhìn xuống cuối lớp','example'=>'Lối đi rộng, sàn sạch',
  ],
  [
    'key'=>'board','label'=>'Bảng đen','icon'=>'📋','required'=>true,
    'guide'=>'Chụp thẳng góc với bảng từ giữa lớp. Lấy hết bảng đen trong khung hình.',
    'angle'=>'Giữa lớp, thẳng góc với bảng','example'=>'Bảng sạch hoặc ghi chú bài học',
  ],
  [
    'key'=>'teacher_desk','label'=>'Bàn giáo viên','icon'=>'🪑','required'=>true,
    'guide'=>'Chụp thẳng từ phía trước bàn GV. Lấy hết mặt bàn, ghế và khu vực xung quanh.',
    'angle'=>'Phía trước bàn GV','example'=>'Bàn gọn gàng, không có đồ vật lộn xộn',
  ],
  [
    'key'=>'podium','label'=>'Bục giảng','icon'=>'🎤','required'=>true,
    'guide'=>'Đứng ngoài hành lang, nhìn qua cửa/cửa sổ vào lớp. Lấy hết bục giảng + tường bảng + bàn GV trong một khung hình.',
    'angle'=>'Hành lang → nhìn vào lớp','example'=>'Bục giảng gọn, ghế GV vào vị trí',
  ],
  [
    'key'=>'hallway','label'=>'Hành lang','icon'=>'🚪','required'=>true,
    'guide'=>'Chụp dọc hành lang từ đầu đến cuối. Ghi lại tình trạng hành lang trước cửa lớp.',
    'angle'=>'Đứng đầu hành lang nhìn dọc','example'=>'Hành lang sạch, không có rác',
  ],
  [
    'key'=>'desk_boxes','label'=>'Hộp bàn (6 dãy)','icon'=>'📦','required'=>true,'multi'=>true,'count'=>6,
    'guide'=>'Đứng đầu dãy (phía hành lang), chụp dọc theo dãy, góc thấp để nhìn XUYÊN QUA hộp bàn. Chụp đủ 6 dãy, mỗi dãy 1 ảnh.',
    'angle'=>'Đầu dãy → cuối dãy, góc thấp xuyên hộp','example'=>'Hộp bàn gọn, không có rác/thức ăn',
  ],
];
$done  = 0;
$total = count($photoSpecs);
foreach ($photoSpecs as $sp) { if(!empty($report[$sp['key']])) $done++; }
$pct = $total>0 ? round($done/$total*100) : 0;
?>
<div class="module-report">

<div class="module-bar">
  <h2 class="module-title">📸 Báo cáo ảnh trực nhật</h2>
  <div class="date-nav">
    <input type="date" class="field-input-sm" value="<?=$reportDate?>"
           title="Chọn ngày báo cáo" aria-label="Chọn ngày báo cáo"
           onchange="location.href='?tab=report&rdate='+this.value"/>
    <span class="tag <?=$reportDate===$today?'tag-green':'tag-gray'?>">
      <?=$reportDate===$today?'Hôm nay':$reportDate?>
    </span>
  </div>
  <?php if(count($dates)>1): ?>
    <select class="field-select-sm" title="Xem ngày khác" aria-label="Xem ngày khác"
            onchange="location.href='?tab=report&rdate='+this.value">
      <?php foreach($dates as $dt): ?>
        <option value="<?=$dt?>" <?=$dt===$reportDate?'selected':''?>><?=$dt?></option>
      <?php endforeach; ?>
    </select>
  <?php endif; ?>
</div>

<!-- Progress -->
<div class="progress-bar-wrap">
  <div class="progress-bar-track"><div class="progress-bar-fill" style="width:<?=$pct?>%"></div></div>
  <span class="progress-label"><?=$done?>/<?=$total?> mục · <?=$pct?>%</span>
</div>

<!-- Checklist -->
<div class="photo-checklist">
  <?php foreach($photoSpecs as $spec):
    $uploaded = $report[$spec['key']] ?? [];
    $isDone   = !empty($uploaded);
    $isMulti  = $spec['multi'] ?? false;
    $cnt      = $spec['count'] ?? 1;
  ?>
  <div class="photo-item <?=$isDone?'done':''?>" id="photo-<?=$spec['key']?>">
    <div class="photo-item-header">
      <div class="photo-item-icon <?=$isDone?'icon-done':'icon-pending'?>">
        <?=$spec['icon']?>
      </div>
      <div class="photo-item-info">
        <div class="photo-item-title">
          <?=$spec['label']?>
          <span class="tag <?=$spec['required']?'tag-red':'tag-gray'?>">
            <?=$spec['required']?'Bắt buộc':'Tuỳ chọn'?>
          </span>
          <?php if($isMulti): ?>
            <span class="tag tag-blue"><?=count($uploaded)?>/<?=$cnt?> ảnh</span>
          <?php endif; ?>
        </div>
        <div class="photo-guide">
          <div class="guide-angle">📷 Góc chụp: <?=$spec['angle']?></div>
          <div class="guide-text"><?=$spec['guide']?></div>
          <div class="guide-example">✅ <?=$spec['example']?></div>
        </div>
      </div>
      <div class="photo-item-actions">
        <?php if($isDone): ?><span class="done-badge">✓ Đã chụp</span><?php endif; ?>
        <!-- Nút chụp camera (mobile) -->
        <button class="btn-camera-big" onclick="openCamera('<?=$spec['key']?>','<?=$reportDate?>')"
                style="padding:8px 12px;font-size:12px;width:auto"
                aria-label="Mở camera chụp ảnh <?=$spec['label']?>">
          <span class="cam-icon" aria-hidden="true">📷</span> Chụp
        </button>
        <!-- Nút chọn file -->
        <label class="btn-upload" for="upload-<?=$spec['key']?>"
               aria-label="Chọn ảnh từ thư viện cho <?=$spec['label']?>">
          📁 <?=$isMulti?'Chọn ảnh':'Từ thư viện'?>
        </label>
        <input type="file" id="upload-<?=$spec['key']?>"
               accept="image/*" <?=$isMulti?'multiple':''?>
               style="display:none"
               aria-label="Chọn file ảnh"
               onchange="uploadPhoto('<?=$spec['key']?>','<?=$reportDate?>',this)"/>
      </div>
    </div>

    <?php if(!empty($uploaded)): ?>
      <div class="photo-preview-row">
        <?php foreach($uploaded as $img): ?>
          <div class="photo-thumb">
            <img src="<?=htmlspecialchars($img)?>" alt="Ảnh <?=$spec['label']?>" loading="lazy"/>
            <button class="thumb-delete" aria-label="Xoá ảnh"
                    onclick="deletePhoto('<?=$spec['key']?>','<?=$reportDate?>','<?=htmlspecialchars($img)?>')">✕</button>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="photo-placeholder">📷 <span>Chưa có ảnh</span></div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
</div>

<!-- Modal camera -->
<div id="cameraModal" class="popup hidden" role="dialog" aria-modal="true" aria-label="Camera chụp ảnh">
  <div class="popup-box">
    <div class="popup-header">
      <span id="cameraTitle">📷 Chụp ảnh</span>
      <button onclick="stopCamera()" aria-label="Đóng camera">✕</button>
    </div>
    <div class="popup-body">
      <div class="camera-wrap">
        <video id="camVideo" autoplay playsinline muted aria-label="Xem trước camera"></video>
        <canvas id="camCanvas"></canvas>
      </div>
      <div style="display:flex;gap:8px;margin-top:10px">
        <button class="btn-camera-big" style="flex:1" onclick="snapPhoto()" aria-label="Chụp ảnh">
          <span aria-hidden="true">📸</span> Chụp ảnh
        </button>
        <button class="btn-secondary" onclick="switchCamera()" aria-label="Đổi camera">🔄</button>
      </div>
      <canvas id="previewCanvas" style="width:100%;border-radius:8px;margin-top:8px;display:none"></canvas>
      <div id="camActions" style="display:none;gap:8px;margin-top:8px;display:none">
        <button class="btn-danger" onclick="retakePhoto()" aria-label="Chụp lại">🔄 Chụp lại</button>
        <button class="btn-primary" onclick="confirmPhoto()" style="flex:1" aria-label="Dùng ảnh này">✓ Dùng ảnh này</button>
      </div>
    </div>
  </div>
</div>

<script>
let _camKey='', _camDate='', _camStream=null, _camFacing='environment', _snapBlob=null;

async function openCamera(key, date) {
  _camKey=key; _camDate=date; _snapBlob=null;
  document.getElementById('cameraTitle').textContent='📷 Chụp – '+key.replace(/_/g,' ');
  const preCanvas=document.getElementById('previewCanvas');
  const actions=document.getElementById('camActions');
  preCanvas.style.display='none';
  actions.style.display='none';
  openModal('cameraModal');
  await startStream();
}

async function startStream() {
  if(_camStream) _camStream.getTracks().forEach(t=>t.stop());
  try {
    _camStream = await navigator.mediaDevices.getUserMedia({
      video:{facingMode:_camFacing,width:{ideal:1280},height:{ideal:960}}, audio:false
    });
    const v=document.getElementById('camVideo');
    v.srcObject=_camStream;
    await v.play();
  } catch(e) {
    App.toast('Không thể mở camera: '+e.message,'error');
    closeModal('cameraModal');
  }
}

async function switchCamera() {
  _camFacing = _camFacing==='environment'?'user':'environment';
  await startStream();
}

function snapPhoto() {
  const v=document.getElementById('camVideo');
  const c=document.getElementById('previewCanvas');
  c.width=v.videoWidth; c.height=v.videoHeight;
  c.getContext('2d').drawImage(v,0,0);
  c.style.display='block';
  document.getElementById('camActions').style.display='flex';
  c.toBlob(b=>{ _snapBlob=b; },'image/jpeg',0.92);
}

function retakePhoto() {
  const c=document.getElementById('previewCanvas');
  c.style.display='none';
  document.getElementById('camActions').style.display='none';
  _snapBlob=null;
}

async function confirmPhoto() {
  if(!_snapBlob) { App.toast('Chưa chụp ảnh','info'); return; }
  const fd=new FormData();
  fd.append('key',_camKey);
  fd.append('date',_camDate);
  fd.append('photos[]',_snapBlob,'photo_'+Date.now()+'.jpg');
  stopCamera();
  App.toast('Đang tải lên...','info');
  const r=await fetch('api/v1/upload_photo.php',{method:'POST',body:fd});
  const j=await r.json().catch(()=>({ok:false}));
  if(j.ok) { App.toast('Đã lưu ảnh ✓'); setTimeout(()=>location.reload(),800); }
  else App.toast('Lỗi tải ảnh: '+(j.error||'?'),'error');
}

function stopCamera() {
  if(_camStream) { _camStream.getTracks().forEach(t=>t.stop()); _camStream=null; }
  closeModal('cameraModal');
}

async function uploadPhoto(key, date, input) {
  const files=[...input.files];
  if(!files.length) return;
  const fd=new FormData();
  fd.append('key',key); fd.append('date',date);
  files.forEach(f=>fd.append('photos[]',f));
  App.toast('Đang tải lên...','info');
  const r=await fetch('api/v1/upload_photo.php',{method:'POST',body:fd});
  const j=await r.json().catch(()=>({ok:false}));
  if(j.ok) { App.toast('Đã lưu '+j.count+' ảnh ✓'); setTimeout(()=>location.reload(),800); }
  else App.toast('Lỗi: '+(j.error||'?'),'error');
}

async function deletePhoto(key, date, path) {
  if(!confirm('Xoá ảnh này?')) return;
  const r=await App.post('api/v1/report.php',{action:'delete_photo',key,date,path});
  if(r.ok!==false) location.reload();
  else App.toast('Lỗi xoá ảnh','error');
}
</script>