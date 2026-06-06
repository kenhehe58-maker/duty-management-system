<?php
// ============================================================
//  index.php — Entry point
// ============================================================
session_start();
define('APP_ROOT',    __DIR__);
define('APP_VERSION', '2.0.0');

require_once APP_ROOT . '/includes/config.php';
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/db.php';

$tab       = $_GET['tab'] ?? 'diagram';
$validTabs = ['diagram','schedule','assign','report','import'];
if (!in_array($tab, $validTabs)) $tab = 'diagram';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
  <meta name="theme-color" content="#2E86C1"/>
  <title>Trực Nhật – <?= CLASS_NAME ?></title>

  <!-- Icon font: dùng unpkg thay vì jsdelivr để tránh Tracking Prevention -->
  <link rel="stylesheet"
    href="https://unpkg.com/@tabler/icons-webfont@2.47.0/tabler-icons.min.css"
    crossorigin="anonymous"/>

  <!-- XLSX parser -->
  <script src="https://unpkg.com/xlsx@0.18.5/dist/xlsx.full.min.js" crossorigin="anonymous"></script>
  <!-- SortableJS -->
  <script src="https://unpkg.com/sortablejs@1.15.3/Sortable.min.js" crossorigin="anonymous"></script>

  <link rel="stylesheet" href="assets/css/app.css"/>
</head>
<body>

<!-- ── HEADER ─────────────────────────────────────────────── -->
<header class="app-header" role="banner">
  <div class="header-brand">
    <span class="brand-icon">📋</span>
    <div class="brand-text">
      <span class="brand-title">Trực Nhật</span>
      <span class="brand-sub"><?= CLASS_NAME ?></span>
    </div>
  </div>

  <nav class="header-tabs" role="navigation" aria-label="Tab chính">
    <?php
    $tabs = [
      'diagram'  => ['emoji'=>'🗺️', 'label'=>'Sơ đồ'],
      'schedule' => ['emoji'=>'📅', 'label'=>'Lịch trực'],
      'assign'   => ['emoji'=>'👤', 'label'=>'Phân công'],
      'report'   => ['emoji'=>'📸', 'label'=>'Báo cáo ảnh'],
      'import'   => ['emoji'=>'📊', 'label'=>'Nhập Excel'],
    ];
    foreach ($tabs as $key => $t):
    ?>
      <a href="?tab=<?= $key ?>"
         class="nav-tab <?= $key===$tab?'active':'' ?>"
         aria-current="<?= $key===$tab?'page':'false' ?>">
        <span class="tab-emoji" aria-hidden="true"><?= $t['emoji'] ?></span>
        <span class="tab-label"><?= $t['label'] ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
</header>

<!-- ── MAIN ───────────────────────────────────────────────── -->
<main class="app-main" id="appMain">
  <?php
  $file = APP_ROOT . '/modules/' . $tab . '.php';
  if (file_exists($file)) include $file;
  else echo '<div class="empty-state"><p>Module không tìm thấy.</p></div>';
  ?>
</main>

<!-- ── TOAST ──────────────────────────────────────────────── -->
<div id="toastContainer" aria-live="polite" aria-atomic="true"></div>

<script src="assets/js/store.js"></script>
<script src="assets/js/app.js"></script>
<script>AppStore.init();</script>
</body>
</html>