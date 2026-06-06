<?php
session_start();
define('APP_ROOT',    __DIR__);
define('APP_VERSION', '2.1.0');
require_once APP_ROOT.'/includes/config.php';
require_once APP_ROOT.'/includes/helpers.php';
require_once APP_ROOT.'/includes/db.php';

$tab = $_GET['tab'] ?? 'diagram';
if (!in_array($tab, ['diagram','schedule','assign','report','import'])) $tab='diagram';

$cls     = DB::getClassSettings();
$CN      = $cls['class_name'];
define('CLASS_NAME',   $CN);
define('NUM_TO',       (int)$cls['num_to']);
define('NUM_ROWS',     (int)$cls['num_rows']);
define('NUM_SIDE_ROWS', 6);
?><!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="theme-color" content="#2C7A2C"/>
<title>Trực Nhật – <?= htmlspecialchars($CN) ?></title>
<link rel="stylesheet" href="https://unpkg.com/@tabler/icons-webfont@2.47.0/tabler-icons.min.css"/>
<script src="https://unpkg.com/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://unpkg.com/sortablejs@1.15.3/Sortable.min.js"></script>
<link rel="stylesheet" href="assets/css/app.css"/>
<!-- FIX: Load api.js TRƯỚC khi body render để openModal sẵn sàng -->
<script src="assets/js/api.js"></script>
<script src="assets/js/app.js"></script>
</head>
<body data-tab="<?= $tab ?>">

<header class="app-header" role="banner">
  <div class="header-brand">
    <span aria-hidden="true">📋</span>
    <div>
      <b class="brand-title">Trực Nhật</b>
      <small class="brand-sub"><?= htmlspecialchars($CN) ?></small>
    </div>
  </div>
  <nav class="header-tabs" role="navigation" aria-label="Điều hướng chính">
    <?php
    $TABS=[
      'diagram'  =>['e'=>'🗺️','l'=>'Sơ đồ'],
      'schedule' =>['e'=>'📅','l'=>'Lịch trực'],
      'assign'   =>['e'=>'👤','l'=>'Phân công'],
      'report'   =>['e'=>'📸','l'=>'Báo cáo'],
      'import'   =>['e'=>'📊','l'=>'Excel'],
    ];
    foreach($TABS as $k=>$t){ $a=$k===$tab; ?>
    <a href="?tab=<?=$k?>" class="nav-tab<?=$a?' active':''?>"
       aria-label="<?=$t['l']?>" aria-current="<?=$a?'page':'false'?>">
      <span aria-hidden="true"><?=$t['e']?></span>
      <span class="tab-label"><?=$t['l']?></span>
    </a>
    <?php } ?>
  </nav>
  <button class="btn-hdr" onclick="openModal('settingsModal')"
          aria-label="Cài đặt lớp học" title="Cài đặt lớp học">
    <i class="ti ti-settings" aria-hidden="true"></i>
  </button>
</header>

<main class="app-main" id="appMain">
<?php
$f=APP_ROOT.'/modules/'.$tab.'.php';
if(file_exists($f)) include $f;
else echo '<p class="empty-state">Module not found: '.$tab.'</p>';
?>
</main>

<div id="toastContainer" aria-live="polite" aria-atomic="true"></div>

<!-- Modal cài đặt lớp -->
<div id="settingsModal" class="popup hidden" role="dialog" aria-modal="true" aria-labelledby="settingsTitle">
  <div class="popup-box">
    <div class="popup-header">
      <span id="settingsTitle">⚙️ Cài đặt lớp học</span>
      <button onclick="closeModal('settingsModal')" aria-label="Đóng">✕</button>
    </div>
    <div class="popup-body">
      <label class="field-label" for="setClassName">Tên lớp</label>
      <input id="setClassName" class="field-input" value="<?=htmlspecialchars($CN)?>" placeholder="Lớp 10A1"/>
      <label class="field-label" for="setSchoolYear" style="margin-top:12px">Năm học</label>
      <input id="setSchoolYear" class="field-input" value="<?=htmlspecialchars($cls['school_year'])?>" placeholder="2024-2025"/>
      <label class="field-label" for="setNumTo" style="margin-top:12px">Số tổ</label>
      <input id="setNumTo" class="field-input" type="number" min="2" max="8" value="<?=NUM_TO?>"/>
      <label class="field-label" for="setNumRows" style="margin-top:12px">Số hàng bàn</label>
      <input id="setNumRows" class="field-input" type="number" min="2" max="12" value="<?=NUM_ROWS?>"/>
    </div>
    <div class="popup-footer">
      <button class="btn-secondary" onclick="closeModal('settingsModal')">Hủy</button>
      <button class="btn-primary" onclick="saveSettings()">💾 Lưu & tải lại</button>
    </div>
  </div>
</div>

<script>
// Init sau khi DOM sẵn sàng
document.addEventListener('DOMContentLoaded', () => App.init());

async function saveSettings(){
  const r=await App.post('api/v1/settings.php',{
    class_name:  document.getElementById('setClassName').value,
    school_year: document.getElementById('setSchoolYear').value,
    num_to:      +document.getElementById('setNumTo').value,
    num_rows:    +document.getElementById('setNumRows').value,
  });
  if(r.ok!==false) location.reload();
  else App.toast('Lỗi lưu cài đặt','error');
}
</script>
</body>
</html>
