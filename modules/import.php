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
    Tự nhận diện hàng tiêu đề (bỏ qua các hàng trên như tên trường, tên lớp...). Chấp nhận cột họ tên gộp <strong>hoặc</strong> tách thành 2 cột họ đệm + tên.
  </p>
  <div class="col-grid">
    <?php
    $cols=[
      ['d'=>'Họ và tên','a'=>'ho ten, hoten, name, ten','r'=>true],
      ['d'=>'Tổ (1-4)',  'a'=>'to, tổ, nhom, group',   'r'=>false],
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
      <thead><tr><th>#</th><th>Họ tên</th><th>Trạng thái</th></tr></thead>
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
// Chuẩn hoá chuỗi: bỏ dấu, thường, trim
function norm(h){
  return(h||'').toString().toLowerCase()
    .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
    .replace(/\s+/g,' ').trim();
}

// Kiểm tra ô có phải là tiêu đề "Họ và tên" không
function isNameHeader(val){
  const n=norm(val);
  return ['ho va ten','ho ten','hoten','fullname','name','ten'].some(a=>n.includes(a));
}

// Kiểm tra ô có phải là tiêu đề "Tên" đơn (cột phụ kế bên) không
function isSurNameHeader(val){
  const n=norm(val);
  return n==='ten'||n==='ho dem'||n==='ho'||n==='last name'||n==='firstname';
}

// Tìm hàng tiêu đề + chỉ số cột họ tên
// Trả về { headerRow, nameCol, extraCol }
// nameCol: cột chứa "Họ và tên" (hoặc họ đệm)
// extraCol: cột tên đơn liền kề (nếu có), ghép = nameCol + extraCol
function findHeaderAndNameCols(rows){
  for(let ri=0;ri<Math.min(rows.length,15);ri++){
    const row=rows[ri];
    for(let ci=0;ci<row.length;ci++){
      if(isNameHeader(row[ci])){
        // Kiểm tra cột kế bên có phải "tên đơn" không
        const nextVal=row[ci+1]??'';
        const extraCol=isSurNameHeader(nextVal)?ci+1:null;
        return { headerRow:ri, nameCol:ci, extraCol };
      }
    }
  }
  return null;
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
      if(rows.length<2){alert('File không có dữ liệu.');return;}

      const found=findHeaderAndNameCols(rows);
      if(!found){
        alert('Không tìm thấy cột "Họ và tên" trong file.\nHãy chắc file có cột tiêu đề chứa "Họ và tên", "Họ tên", hoặc "Name".');
        return;
      }

      const { headerRow, nameCol, extraCol } = found;
      parsedRows=[];

      for(let i=headerRow+1;i<rows.length;i++){
        const r=rows[i];
        // Lấy phần họ đệm
        const part1=String(r[nameCol]??'').trim();
        // Nếu có cột tên riêng, ghép thêm
        const part2=extraCol!=null?String(r[extraCol]??'').trim():'';
        // Ghép: "Họ đệm" + " " + "Tên" (hoặc chỉ part1 nếu không có extra)
        const name=part2?part1+' '+part2:part1;
        if(!name.trim()) continue;
        parsedRows.push({
          name,
          to: colMap.to !== undefined ? (Math.min(8, Math.max(1, parseInt(r[colMap.to]) || 0)) || null) : null,
          hang: null,
          vi_tri: null,
          ban: null,
          chuc_vu: null,
        });
      }

      if(!parsedRows.length){alert('Không tìm thấy dữ liệu học sinh trong file.');return;}
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
    <tr><td>${i+1}</td><td>${r.name}</td>
    <td><span class="tag tag-blue">Mới</span></td></tr>
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
    ['Họ và tên'],
    ['Nguyễn Văn A'],
    ['Trần Thị B'],
    ['Lê Văn C'],
  ]);
  XLSX.utils.book_append_sheet(wb,ws,'Danh sách');
  XLSX.writeFile(wb,'mau_danh_sach_hoc_sinh.xlsx');
}
</script>