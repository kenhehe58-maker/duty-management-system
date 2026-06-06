<?php
// modules/import.php  v2.1
$students = DB::getStudents();
?>
<div class="module-import">

<div class="module-bar">
  <h2 class="module-title">📊 Nhập danh sách học sinh</h2>
  <div class="bar-actions">
    <button class="btn-secondary" onclick="downloadTemplate()" aria-label="Tải file mẫu Excel">
      📥 Tải file mẫu
    </button>
  </div>
</div>

<!-- Hướng dẫn cột -->
<div class="card import-guide">
  <p class="card-title">ℹ️ Định dạng Excel được hỗ trợ</p>
  <p class="muted" style="margin-bottom:10px">
    Tự nhận diện cột qua <strong>tiêu đề hàng đầu</strong>. Thứ tự cột tùy ý. Chấp nhận tiếng Việt có/không dấu.
  </p>
  <div class="col-grid">
    <?php
    $cols=[
      ['d'=>'STT',         'a'=>'stt, #',                   'r'=>false],
      ['d'=>'Họ và tên',   'a'=>'ho ten, hoten, name, ten', 'r'=>true],
      ['d'=>'Tổ (1–4)',    'a'=>'to, tổ, nhom, group',      'r'=>true],
      ['d'=>'Số bàn',      'a'=>'ban, bàn, desk',           'r'=>false],
      ['d'=>'Vị trí (T/P)','a'=>'vi tri, pos, seat',        'r'=>false],
      ['d'=>'Hàng (1–6)',  'a'=>'hang, hàng, row',          'r'=>false],
      ['d'=>'Chức vụ',     'a'=>'chuc vu, role, chuc_vu',   'r'=>false],
    ];
    foreach($cols as $col): ?>
      <div class="col-chip <?=$col['r']?'col-req':'col-opt'?>">
        <span class="col-name"><?=$col['d']?></span>
        <span class="col-aliases"><?=$col['a']?></span>
        <span class="col-badge"><?=$col['r']?'Bắt buộc':'Tuỳ chọn'?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Upload -->
<div class="upload-area" id="uploadArea"
     ondragover="event.preventDefault();this.classList.add('drag-over')"
     ondragleave="this.classList.remove('drag-over')"
     ondrop="handleDrop(event)"
     onclick="document.getElementById('excelInput').click()"
     role="button" tabindex="0" aria-label="Tải lên file Excel"
     onkeydown="if(event.key==='Enter')document.getElementById('excelInput').click()">
  <span class="upload-icon" aria-hidden="true">📤</span>
  <p class="upload-text">Kéo thả hoặc nhấn để chọn file</p>
  <p class="upload-hint">.xlsx · .xls · .csv — tối đa <?=MAX_UPLOAD_MB?>MB</p>
</div>
<input type="file" id="excelInput" accept=".xlsx,.xls,.csv"
       aria-label="Chọn file Excel" onchange="parseFile(this.files[0])" style="display:none"/>

<!-- Mode nhập -->
<div class="import-options card" id="importOptions" style="display:none">
  <label class="toggle-label">
    <input type="radio" name="importMode" value="merge" checked/> Cập nhật & thêm mới (giữ dữ liệu cũ)
  </label>
  <label class="toggle-label" style="margin-top:6px">
    <input type="radio" name="importMode" value="replace"/> Thay thế toàn bộ (xoá hết rồi nhập lại)
  </label>
</div>

<!-- Preview -->
<div id="previewWrap" style="display:none">
  <div class="preview-header">
    <span id="previewCount" class="preview-count"></span>
    <div style="display:flex;gap:8px">
      <button class="btn-secondary" onclick="clearPreview()">✕ Huỷ</button>
      <button class="btn-primary" onclick="confirmImport()">✓ Xác nhận nhập</button>
    </div>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>#</th><th>Họ tên</th><th>Tổ</th><th>Hàng</th><th>Vị trí</th><th>Chức vụ</th><th>Trạng thái</th></tr></thead>
      <tbody id="previewBody"></tbody>
    </table>
  </div>
</div>

<!-- Danh sách hiện tại -->
<div class="card" style="margin-top:14px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
    <p class="card-title">👥 Danh sách hiện tại <span class="tag tag-gray"><?=count($students)?> học sinh</span></p>
    <button class="btn-danger-sm" onclick="clearAll()" <?=empty($students)?'disabled':''?> aria-label="Xoá tất cả học sinh">🗑 Xoá tất cả</button>
  </div>
  <?php if(empty($students)): ?>
    <p class="muted">Chưa có dữ liệu. Hãy nhập từ Excel hoặc thêm thủ công trong tab Phân công.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>#</th><th>Họ tên</th><th>Tổ</th><th>Chức vụ</th><th>Hàng</th><th>Vị trí</th></tr></thead>
        <tbody>
          <?php foreach($students as $i=>$s): ?>
            <tr>
              <td><?=$i+1?></td>
              <td><?=htmlspecialchars($s['name'])?></td>
              <td>Tổ <?=$s['to']?></td>
              <td><?=htmlspecialchars($s['chuc_vu']??'')?:'—'?></td>
              <td><?=$s['hang']?'H'.$s['hang']:'—'?></td>
              <td><?=$s['vi_tri']?:'—'?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
</div>

<script>
// Column aliases
const COL_MAP={
  name:    ['ho ten','hoten','ten','name','fullname','ho va ten','họ tên','họ và tên'],
  to:      ['to','tổ','nhom','nhóm','group','to hoc','tổ học'],
  hang:    ['hang','hàng','row','hang ban'],
  vi_tri:  ['vi tri','vị trí','vitri','pos','position','cho ngoi','seat'],
  ban:     ['ban','bàn','desk','so ban'],
  chuc_vu: ['chuc vu','chức vụ','chucvu','role','chuc_vu','chuc vu hoc sinh'],
};

function norm(h){
  return(h||'').toString().toLowerCase()
    .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
    .replace(/\s+/g,' ').trim();
}
function detectCols(hdrs){
  const n=hdrs.map(norm), map={};
  for(const[f,aliases]of Object.entries(COL_MAP)){
    const idx=n.findIndex(h=>aliases.some(a=>h.includes(norm(a))));
    if(idx>=0)map[f]=idx;
  }
  return map;
}

let parsedRows=[];

function parseFile(file){
  if(!file) return;
  const reader=new FileReader();
  reader.onload=e=>{
    try{
      const wb=XLSX.read(new Uint8Array(e.target.result),{type:'array'});
      const ws=wb.Sheets[wb.SheetNames[0]];
      const rows=XLSX.utils.sheet_to_json(ws,{header:1,defval:''});
      if(rows.length<2){alert('File không có dữ liệu (cần ít nhất 2 hàng: tiêu đề + dữ liệu).');return;}
      const colMap=detectCols(rows[0]);
      if(colMap.name===undefined||colMap.to===undefined){
        alert('Không tìm thấy cột "Họ tên" hoặc "Tổ".\nHãy chắc hàng đầu là tiêu đề cột.');
        return;
      }
      parsedRows=[];
      for(let i=1;i<rows.length;i++){
        const r=rows[i];
        const name=String(r[colMap.name]??'').trim();
        if(!name) continue;
        parsedRows.push({
          name,
          to:Math.min(8,Math.max(1,parseInt(r[colMap.to]??1)||1)),
          hang:parseInt(r[colMap.hang]??'')||null,
          vi_tri:String(r[colMap.vi_tri]??'').toUpperCase().charAt(0)||null,
          ban:parseInt(r[colMap.ban]??'')||null,
          chuc_vu:String(r[colMap.chuc_vu]??'').trim()||null,
        });
      }
      showPreview(parsedRows);
      document.getElementById('importOptions').style.display='';
    }catch(err){alert('Lỗi đọc file: '+err.message);}
  };
  reader.readAsArrayBuffer(file);
}

function handleDrop(e){
  e.preventDefault();
  document.getElementById('uploadArea').classList.remove('drag-over');
  parseFile(e.dataTransfer.files[0]);
}

function showPreview(rows){
  document.getElementById('previewWrap').style.display='';
  document.getElementById('previewCount').textContent=rows.length+' học sinh sẽ được nhập';
  document.getElementById('previewBody').innerHTML=rows.map((r,i)=>`
    <tr><td>${i+1}</td><td>${r.name}</td><td>Tổ ${r.to}</td>
    <td>${r.hang?'H'+r.hang:'—'}</td><td>${r.vi_tri||'—'}</td>
    <td>${r.chuc_vu||'—'}</td><td><span class="tag tag-blue">Mới</span></td></tr>
  `).join('');
}

function clearPreview(){
  parsedRows=[];
  document.getElementById('previewWrap').style.display='none';
  document.getElementById('importOptions').style.display='none';
  document.getElementById('excelInput').value='';
}

async function confirmImport(){
  if(!parsedRows.length) return;
  const mode=document.querySelector('input[name=importMode]:checked')?.value||'merge';
  App.toast('Đang nhập...','info');
  const r=await App.importStudents(parsedRows,mode);
  if(r.ok!==false){App.toast('Đã nhập '+r.count+' học sinh ✓');location.reload();}
  else App.toast('Lỗi nhập: '+(r.error||'?'),'error');
}

async function clearAll(){
  if(!confirm('Xoá TOÀN BỘ danh sách học sinh? Không thể hoàn tác!')) return;
  const r=await App.importStudents([],'replace');
  if(r.ok!==false) location.reload();
}

function downloadTemplate(){
  // Tạo file mẫu ngay trên trình duyệt
  const wb=XLSX.utils.book_new();
  const ws=XLSX.utils.aoa_to_sheet([
    ['STT','Họ và tên','Tổ','Hàng','Vị trí','Số bàn','Chức vụ'],
    [1,'Nguyễn Văn A',1,1,'T',1,'Lớp trưởng'],
    [2,'Trần Thị B',1,1,'P',1,''],
    [3,'Lê Văn C',2,1,'T',1,'Tổ trưởng'],
  ]);
  XLSX.utils.book_append_sheet(wb,ws,'Danh sách');
  XLSX.writeFile(wb,'mau_danh_sach_hoc_sinh.xlsx');
}
</script>
