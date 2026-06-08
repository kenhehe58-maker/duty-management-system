<?php
// ============================================================
// modules/import.php  v2.5 — FIX LỖI TÁCH CỘT TÊN (FILE VNEDU/CƠ SỞ DỮ LIỆU)
// Tự động nhận diện và gộp cột Tên riêng kể cả khi hàng tiêu đề bị trống/gộp ô
// ============================================================
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

<div class="card import-guide">
  <p class="card-title">ℹ️ Định dạng Excel được hỗ trợ</p>
  <p class="muted" style="margin-bottom:10px">
    Tự nhận diện hàng tiêu đề. Hỗ trợ gộp cột <strong>Họ và tên đệm (Cột 1)</strong> + <strong>Tên riêng (Cột 2)</strong> tự động từ file vnEdu/SMAS kể cả khi tiêu đề cột tên bị trống.
  </p>
  <div class="col-grid">
    <?php
    $cols=[
      ['d'=>'Họ và tên','a'=>'ho ten, hoten, name, ten, ho va ten','r'=>true],
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

<div class="upload-area" id="uploadArea"
     ondragover="event.preventDefault();this.classList.add('drag-over')"
     ondragleave="this.classList.remove('drag-over')"
     ondrop="handleDrop(event)"
     onclick="document.getElementById('excelInput').click()"
     role="button" tabindex="0" aria-label="Tải lên file Excel"
     onkeydown="if(event.key==='Enter')document.getElementById('excelInput').click()">
  <span class="upload-icon" aria-hidden="true">📤</span>
  <p class="upload-text">Kéo thả hoặc nhấn để chọn file</p>
  <p class="upload-hint">.xlsx · .xls · .csv — tối đa <?=defined('MAX_UPLOAD_MB') ? MAX_UPLOAD_MB : 5?>MB</p>
</div>
<input type="file" id="excelInput" accept=".xlsx,.xls,.csv"
       aria-label="Chọn file Excel" onchange="parseFile(this.files[0])" style="display:none"/>

<div class="import-options card" id="importOptions" style="display:none">
  <label class="toggle-label">
    <input type="radio" name="importMode" value="merge" checked/> Cập nhật & thêm mới (giữ dữ liệu cũ)
  </label>
  <label class="toggle-label" style="margin-top:6px">
    <input type="radio" name="importMode" value="replace"/> Thay thế toàn bộ (xoá hết rồi nhập lại)
  </label>
</div>

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
      <thead><tr><th>#</th><th>Họ tên sau khi gộp</th><th>Trạng thái</th></tr></thead>
      <tbody id="previewBody"></tbody>
    </table>
  </div>
</div>

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
function norm(h){
  return(h||'').toString().toLowerCase()
    .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
    .replace(/\s+/g,' ').trim();
}

function isNameHeader(val){
  const n=norm(val);
  return ['ho va ten','ho ten','hoten','fullname','name','ten'].some(a=>n.includes(a));
}

// Tìm vị trí tiêu đề chính xác
function findHeaderAndNameCols(rows){
  for(let ri=0;ri<Math.min(rows.length,15);ri++){
    const row=rows[ri];
    if(!row) continue;
    for(let ci=0;ci<row.length;ci++){
      if(isNameHeader(row[ci])){
        // Thuật toán v2.5: Kiểm tra xem 5 dòng dữ liệu bên dưới của cột kế bên có chứa Tên riêng không
        let hasDataNextCol = false;
        let sampleCount = 0;
        
        for(let checkRow = ri + 1; checkRow < Math.min(rows.length, ri + 6); checkRow++){
          if(rows[checkRow] && rows[checkRow][ci+1] !== undefined && String(rows[checkRow][ci+1]).trim() !== '') {
            sampleCount++;
            // Nếu độ dài từ ở cột bên cạnh ngắn (thường chỉ 1 từ như "Anh", "Bảo") -> Đích thị là cột Tên riêng tách rời
            if(String(rows[checkRow][ci+1]).trim().split(' ').length <= 2) {
              hasDataNextCol = true;
            }
          }
        }
        
        // Nếu cột kế bên có dữ liệu tên riêng biệt, đánh dấu gộp cột luôn
        const extraCol = (hasDataNextCol || sampleCount > 0) ? ci + 1 : null;
        return { headerRow: ri, nameCol: ci, extraCol };
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
        alert('Không tìm thấy cột "Họ và tên" trong file Excel.');
        return;
      }

      const { headerRow, nameCol, extraCol } = found;
      parsedRows=[];

      for(let i=headerRow+1; i<rows.length; i++){
        const r=rows[i];
        if(!r) continue;
        
        // Lấy Họ và tên đệm từ cột chính (Ví dụ: "Dương Ngọc")
        const part1=String(r[nameCol]??'').trim();
        
        // Lấy Tên riêng từ cột phụ liền kề (Ví dụ: "Anh")
        const part2=extraCol!=null?String(r[extraCol]??'').trim(): '';
        
        // Bỏ qua dòng số thứ tự trống hoặc dòng tổng kết cuối file của vnEdu
        if(!part1 || part1 === 'undefined' || isNameHeader(part1)) continue;
        
        // Tiến hành ghép đôi hoàn hảo: "Dương Ngọc" + " " + "Anh" = "Dương Ngọc Anh"
        const fullName = part2 ? (part1 + ' ' + part2) : part1;
        
        parsedRows.push({
          id: null,
          name: fullName.replace(/\s+/g,' ').trim(), // Dọn sạch khoảng trắng thừa
          to: 1,
          hang: 0,
          vi_tri: '',
          ban: null,
          chuc_vu: ''
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
    <tr><td>${i+1}</td><td><strong>${r.name}</strong></td>
    <td><span class="tag tag-blue">Mới (Đã gộp)</span></td></tr>
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
  App.toast('Đang nạp danh sách học sinh...','info');
  
  const r=await App.importStudents(parsedRows,mode);
  if(r && r.ok!==false){
    App.toast('Đã nhập '+r.count+' học sinh hoàn tất! ✓','success');
    setTimeout(() => { location.reload(); }, 800);
  } else {
    App.toast('Lỗi: '+(r?.error||'?'),'error');
  }
}

async function clearAll(){
  if(!confirm('Xoá TOÀN BỘ danh sách học sinh? Không thể hoàn tác!')) return;
  const r=await App.importStudents([],'replace');
  if(r && r.ok!==false) location.reload();
}

function downloadTemplate(){
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