<?php
declare(strict_types=1);

/**
 * Admin shared sidebar + topbar partial.
 * Variables expected before include:
 *   $activePage  string  e.g. 'dashboard', 'tours', 'bookings', 'users'
 *   $pageTitle   string  e.g. 'Tổng quan'
 *   $pageSubtitle string optional
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$activePage   = isset($activePage)   ? (string) $activePage   : 'dashboard';
$pageTitle    = isset($pageTitle)    ? (string) $pageTitle    : 'Tổng quan';
$pageSubtitle = isset($pageSubtitle) ? (string) $pageSubtitle : '';

$adminName = isset($_SESSION['full_name']) ? (string) $_SESSION['full_name'] : 'Admin';
$adminRole = isset($_SESSION['role'])      ? (string) $_SESSION['role']      : 'admin';
$initials  = mb_strtoupper(mb_substr($adminName, 0, 1, 'UTF-8'), 'UTF-8');

// Count pending bookings for badge
$pendingCount = 0;
try {
    $res = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'chờ duyệt'");
    $pendingCount = (int) $res->fetchColumn();
} catch (Throwable) {}

function adminH(mixed $v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= adminH($pageTitle) ?> — Admin Du Lịch Việt</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="<?= isset($cssDepth) ? $cssDepth : '../' ?>admin/css/admin.css" />
  <?= $extraHead ?? '' ?>
</head>
<body>

<!-- SIDEBAR OVERLAY (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="admin-layout">

  <!-- ============ SIDEBAR ============ -->
  <aside class="admin-sidebar" id="adminSidebar">

    <a href="../admin/index.php" class="sidebar-brand">
      <div class="brand-icon"><i class="fas fa-map-marked-alt"></i></div>
      <div class="brand-text">
        <strong>Du Lịch Việt</strong>
        <span>Admin Panel</span>
      </div>
    </a>

    <nav class="sidebar-nav">

      <div class="nav-section-label">Tổng quan</div>
      <a href="../admin/index.php" class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
        <i class="fas fa-chart-pie"></i> Tổng quan
      </a>

      <div class="nav-section-label">Quản lý</div>
      <a href="../admin/tours.php" class="nav-item <?= $activePage === 'tours' ? 'active' : '' ?>">
        <i class="fas fa-route"></i> Tour Du lịch
      </a>
      <a href="../admin/bookings.php" class="nav-item <?= $activePage === 'bookings' ? 'active' : '' ?>">
        <i class="fas fa-calendar-check"></i> Đặt tour
        <?php if ($pendingCount > 0): ?>
          <span class="nav-badge"><?= $pendingCount ?></span>
        <?php endif; ?>
      </a>
      <a href="../admin/users.php" class="nav-item <?= $activePage === 'users' ? 'active' : '' ?>">
        <i class="fas fa-users"></i> Khách hàng
      </a>

      <?php if ($adminRole === 'admin'): ?>
      <div class="nav-section-label">Hệ thống</div>
      <a href="../admin/staff.php" class="nav-item <?= $activePage === 'staff' ? 'active' : '' ?>">
        <i class="fas fa-user-tie"></i> Nhân viên
      </a>
      <?php endif; ?>

      <div class="nav-section-label">Liên kết</div>
      <a href="../frontend/index.php" class="nav-item" target="_blank">
        <i class="fas fa-external-link-alt"></i> Trang khách hàng
      </a>

    </nav>

    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="user-avatar"><?= adminH($initials) ?></div>
        <div class="user-info">
          <strong><?= adminH($adminName) ?></strong>
          <span><?= $adminRole === 'admin' ? 'Quản trị viên' : 'Nhân viên' ?></span>
        </div>
        <a href="../frontend/logout.php" class="sidebar-user-logout" title="Đăng xuất">
          <i class="fas fa-sign-out-alt"></i>
        </a>
      </div>
    </div>

  </aside>

  <!-- ============ MAIN ============ -->
  <main class="admin-main">

    <!-- TOPBAR -->
    <header class="admin-topbar">
      <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">
        <i class="fas fa-bars"></i>
      </button>
      <div class="topbar-title">
        <h1><?= adminH($pageTitle) ?></h1>
        <?php if ($pageSubtitle): ?>
          <p><?= adminH($pageSubtitle) ?></p>
        <?php endif; ?>
      </div>
      <div class="topbar-actions">
        <?= $topbarActions ?? '' ?>
      </div>
    </header>

    <!-- PAGE BODY -->
    <div class="admin-body">
