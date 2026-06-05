<?php
// ============================================================
//  QUẢN LÝ TRỰC NHẬT LỚP HỌC  — index.php
//  Module: Entry Point / Shell
//  Phân công: Dev A (layout chính + routing tab)
// ============================================================
session_start();
define('APP_ROOT', __DIR__);
define('APP_VERSION', '1.0.0');

require_once APP_ROOT . '/includes/config.php';
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/db.php';

$tab = $_GET['tab'] ?? 'diagram';
$validTabs = ['diagram', 'schedule', 'assign', 'report', 'import'];
if (!in_array($tab, $validTabs)) $tab = 'diagram';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Quản lý Trực Nhật Lớp Học</title>
  <link rel="stylesheet" href="assets/css/app.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
</head>
<body>
  <header class="app-header">
    <div class="header-brand">
      <i class="ti ti-calendar-check"></i>
      <span>Quản lý Trực Nhật</span>
      <span class="version-badge">v<?= APP_VERSION ?></span>
    </div>
    <nav class="header-tabs">
      <?php
      $tabs = [
        'diagram'  => ['icon' => 'ti-layout-grid',       'label' => 'Sơ đồ lớp'],
        'schedule' => ['icon' => 'ti-calendar',          'label' => 'Lịch trực'],
        'assign'   => ['icon' => 'ti-user-check',        'label' => 'Phân công'],
        'report'   => ['icon' => 'ti-camera',            'label' => 'Báo cáo ảnh'],
        'import'   => ['icon' => 'ti-file-spreadsheet',  'label' => 'Nhập Excel'],
      ];
      foreach ($tabs as $key => $t):
        $active = $key === $tab ? 'active' : '';
      ?>
        <a href="?tab=<?= $key ?>" class="nav-tab <?= $active ?>">
          <i class="ti <?= $t['icon'] ?>"></i>
          <span><?= $t['label'] ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="header-actions">
      <button class="btn-icon" onclick="exportData()" title="Xuất dữ liệu">
        <i class="ti ti-download"></i>
      </button>
    </div>
  </header>

  <main class="app-main">
    <?php
    $moduleFile = APP_ROOT . '/modules/' . $tab . '.php';
    if (file_exists($moduleFile)) {
        include $moduleFile;
    } else {
        echo '<div class="empty-state"><p>Module không tìm thấy.</p></div>';
    }
    ?>
  </main>

  <script src="assets/js/app.js"></script>
  <script src="assets/js/store.js"></script>
  <script>AppStore.init();</script>
</body>
</html>