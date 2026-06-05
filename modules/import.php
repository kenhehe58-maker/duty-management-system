<?php
// ============================================================
//  modules/import.php
//  Module 5: Nhập danh sách học sinh từ Excel / CSV
//  Phân công: Dev D
//  Chức năng:
//    - Hỗ trợ .xlsx, .xls, .csv
//    - Tự động nhận diện cột (header linh hoạt, không phân biệt thứ tự)
//    - Preview trước khi lưu
//    - Tuỳ chọn: ghi đè toàn bộ hoặc chỉ cập nhật/thêm mới
//    - Nhập thủ công từng học sinh
//    - Export mẫu Excel
// ============================================================
$students = DB::getStudents();
?>

<div class="module-import">

  <div class="module-bar">
    <h2 class="module-title"><i class="ti ti-file-spreadsheet"></i> Nhập danh sách học sinh</h2>
    <div class="bar-actions">
      <a href="api/export_template.php" class="btn-secondary" download>
        <i class="ti ti-download"></i> Tải file mẫu
      </a>
    </div>
  </div>

  <!-- Hướng dẫn cột Excel -->
  <div class="card import-guide">
    <p class="card-title"><i class="ti ti-info-circle"></i> Định dạng Excel được hỗ trợ</p>
    <p class="muted" style="margin-bottom:10px">
      Ứng dụng tự nhận diện cột qua <strong>tên tiêu đề hàng đầu</strong>.
      Thứ tự cột không quan trọng. Chấp nhận tiêu đề tiếng Việt có dấu hoặc không dấu.
    </p>
    <div class="col-grid">
      <?php
      $colSpec = [
        ['aliases' => ['stt', 'so thu tu', 'số thứ tự', '#'],         'desc' => 'STT',            'req' => false],
        ['aliases' => ['ho ten', 'họ tên', 'hoten', 'name', 'ten'],   'desc' => 'Họ và tên',      'req' => true],
        ['aliases' => ['to', 'tổ', 'nhom', 'nhóm', 'group'],          'desc' => 'Tổ (1–4)',       'req' => true],
        ['aliases' => ['ban', 'bàn', 'desk'],                          'desc' => 'Số bàn (1–6)',   'req' => false],
        ['aliases' => ['vi tri', 'vị trí', 'pos', 'position'],         'desc' => 'Vị trí (T/P)',  'req' => false],
        ['aliases' => ['hang', 'hàng', 'row'],                         'desc' => 'Hàng (1–6)',     'req' => false],
        ['aliases' => ['mssv', 'id', 'ma', 'mã'],                      'desc' => 'Mã HS (tuỳ)',   'req' => false],
      ];
      foreach ($colSpec as $col): ?>
        <div class="col-chip <?= $col['req'] ? 'col-req' : 'col-opt' ?>">
          <span class="col-name"><?= $col['desc'] ?></span>
          <span class="col-aliases"><?= implode(', ', array_slice($col['aliases'], 0, 3)) ?></span>
          <span class="col-badge"><?= $col['req'] ? 'Bắt buộc' : 'Tuỳ chọn' ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Upload area -->
  <div class="upload-area" id="uploadArea"
       ondragover="event.preventDefault(); this.classList.add('drag-over')"
       ondragleave="this.classList.remove('drag-over')"
       ondrop="handleFileDrop(event)"
       onclick="document.getElementById('excelInput').click()">
    <i class="ti ti-upload upload-icon"></i>
    <p class="upload-text">Kéo thả hoặc nhấn để chọn file</p>
    <p class="upload-hint">.xlsx · .xls · .csv — tối đa <?= MAX_UPLOAD_MB ?>MB</p>
  </div>
  <input type="file" id="excelInput" accept=".xlsx,.xls,.csv"
         onchange="parseExcelFile(this.files[0])" style="display:none" />

  <!-- Tùy chọn nhập -->
  <div class="import-options card" style="margin-top:1rem;display:none" id="importOptions">
    <label class="toggle-label">
      <input type="radio" name="importMode" value="merge" checked />
      Cập nhật & thêm mới (giữ dữ liệu hiện tại)
    </label>
    <label class="toggle-label" style="margin-top:6px">
      <input type="radio" name="importMode" value="replace" />
      Thay thế toàn bộ (xoá hết dữ liệu cũ)
    </label>
  </div>

  <!-- Preview bảng -->
  <div id="previewWrap" style="display:none">
    <div class="preview-header">
      <span id="previewCount" class="preview-count"></span>
      <div style="display:flex;gap:8px;">
        <button class="btn-secondary" onclick="clearPreview()">
          <i class="ti ti-x"></i> Huỷ
        </button>
        <button class="btn-primary" onclick="confirmImport()">
          <i class="ti ti-check"></i> Xác nhận nhập
        </button>
      </div>
    </div>
    <div class="table-wrap">
      <table class="data-table" id="previewTable">
        <thead>
          <tr>
            <th>#</th><th>Họ tên</th><th>Tổ</th><th>Hàng</th><th>Vị trí</th><th>Trạng thái</th>
          </tr>
        </thead>
        <tbody id="previewBody"></tbody>
      </table>
    </div>
  </div>

  <!-- Danh sách hiện tại -->
  <div class="card" style="margin-top:1.5rem">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
      <p class="card-title">
        <i class="ti ti-users"></i> Danh sách hiện tại
        <span class="tag-gray"><?= count($students) ?> học sinh</span>
      </p>
      <button class="btn-danger-sm" onclick="clearAllStudents()" <?= empty($students) ? 'disabled' : '' ?>>
        <i class="ti ti-trash"></i> Xoá tất cả
      </button>
    </div>
    <?php if (empty($students)): ?>
      <p class="muted">Chưa có dữ liệu học sinh.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr><th>#</th><th>Họ tên</th><th>Tổ</th><th>Hàng</th><th>Vị trí</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($students as $i => $s): ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td><?= htmlspecialchars($s['name']) ?></td>
                <td>Tổ <?= $s['to'] ?></td>
                <td><?= $s['hang'] ? 'H'.$s['hang'] : '—' ?></td>
                <td><?= $s['vi_tri'] ?: '—' ?></td>
                <td>
                  <button class="btn-icon-xs" onclick="openEditStudent(<?= $s['id'] ?>)">
                    <i class="ti ti-edit"></i>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

</div><!-- /.module-import -->

<script>
// ── Column name normalizer ──────────────────────────────────
function normalizeHeader(h) {
  return (h || '').toString().toLowerCase()
    .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
    .replace(/\s+/g,' ').trim();
}

const COL_MAP = {
  name:    ['ho ten','hoten','ten','name','fullname','ho va ten'],
  to:      ['to','nhom','group','tof'],
  hang:    ['hang','row','hang ban'],
  vi_tri:  ['vi tri','vitri','pos','position','cho','seat'],
  ban:     ['ban','desk','so ban'],
  id:      ['id','mssv','ma','ma hs','mã','mã hs'],
};

function detectColumns(headers) {
  const norm = headers.map(normalizeHeader);
  const map  = {};
  for (const [field, aliases] of Object.entries(COL_MAP)) {
    const idx = norm.findIndex(h => aliases.some(a => h.includes(a)));
    if (idx >= 0) map[field] = idx;
  }
  return map;
}

let parsedRows = [];

function parseExcelFile(file) {
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    try {
      const wb   = XLSX.read(new Uint8Array(e.target.result), { type:'array' });
      const ws   = wb.Sheets[wb.SheetNames[0]];
      const rows = XLSX.utils.sheet_to_json(ws, { header:1, defval:'' });
      if (rows.length < 2) { alert('File không có dữ liệu.'); return; }

      const headers = rows[0];
      const colMap  = detectColumns(headers);

      if (colMap.name === undefined || colMap.to === undefined) {
        alert('Không tìm thấy cột "Họ tên" hoặc "Tổ".\nHãy đảm bảo hàng đầu tiên là tiêu đề cột.');
        return;
      }

      parsedRows = [];
      for (let i = 1; i < rows.length; i++) {
        const r    = rows[i];
        const name = String(r[colMap.name] ?? '').trim();
        if (!name) continue;
        const to   = parseInt(r[colMap.to] ?? 1) || 1;
        parsedRows.push({
          id:     Date.now() + i,
          name,
          to:     Math.min(4, Math.max(1, to)),
          hang:   parseInt(r[colMap.hang] ?? '') || null,
          vi_tri: String(r[colMap.vi_tri] ?? '').toUpperCase().substring(0,1) || null,
          ban:    parseInt(r[colMap.ban]  ?? '') || null,
        });
      }
      showPreview(parsedRows);
      document.getElementById('importOptions').style.display = '';
    } catch(err) {
      alert('Lỗi đọc file: ' + err.message);
    }
  };
  reader.readAsArrayBuffer(file);
}

function handleFileDrop(e) {
  e.preventDefault();
  document.getElementById('uploadArea').classList.remove('drag-over');
  const file = e.dataTransfer.files[0];
  if (file) parseExcelFile(file);
}

function showPreview(rows) {
  document.getElementById('previewWrap').style.display = '';
  document.getElementById('previewCount').textContent = rows.length + ' học sinh sẽ được nhập';
  const tbody = document.getElementById('previewBody');
  tbody.innerHTML = rows.map((r,i) => `
    <tr>
      <td>${i+1}</td>
      <td>${r.name}</td>
      <td>Tổ ${r.to}</td>
      <td>${r.hang ? 'H'+r.hang : '—'}</td>
      <td>${r.vi_tri || '—'}</td>
      <td><span class="tag-blue">Mới</span></td>
    </tr>
  `).join('');
}

function clearPreview() {
  parsedRows = [];
  document.getElementById('previewWrap').style.display = 'none';
  document.getElementById('importOptions').style.display = 'none';
}

async function confirmImport() {
  if (!parsedRows.length) return;
  const mode = document.querySelector('input[name=importMode]:checked')?.value || 'merge';
  const res  = await AppStore.importStudents(parsedRows, mode);
  if (res.ok) { alert(`Đã nhập ${res.count} học sinh.`); location.reload(); }
  else alert('Lỗi: ' + (res.error || 'Không xác định'));
}

async function clearAllStudents() {
  if (!confirm('Xoá toàn bộ danh sách học sinh? Không thể hoàn tác.')) return;
  await AppStore.importStudents([], 'replace');
  location.reload();
}
</script>