<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
if (!isset($pdo)) {
    require_once __DIR__ . '/db.php';
}

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
  <link rel="stylesheet" href="<?= htmlspecialchars(app_asset_url('css/admin.css'), ENT_QUOTES, 'UTF-8') ?>" />
  <?= $extraHead ?? '' ?>
</head>
<body>

<!-- SIDEBAR OVERLAY (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="admin-layout">

  <!-- ============ SIDEBAR ============ -->
  <aside class="admin-sidebar" id="adminSidebar">

    <a href="<?= htmlspecialchars(app_admin_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-brand">
      <div class="brand-icon"><i class="fas fa-map-marked-alt"></i></div>
      <div class="brand-text">
        <strong>Du Lịch Việt</strong>
        <span>Admin Panel</span>
      </div>
    </a>

    <nav class="sidebar-nav">

      <div class="nav-section-label">Tổng quan</div>
      <a href="<?= htmlspecialchars(app_admin_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
        <i class="fas fa-chart-pie"></i> Tổng quan
      </a>

      <div class="nav-section-label">Quản lý</div>
      <a href="<?= htmlspecialchars(app_admin_url('tours/list.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= $activePage === 'tours' ? 'active' : '' ?>">
        <i class="fas fa-route"></i> Tour Du lịch
      </a>
      <a href="<?= htmlspecialchars(app_admin_url('bookings/list.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= $activePage === 'bookings' ? 'active' : '' ?>">
        <i class="fas fa-calendar-check"></i> Đặt tour
        <?php if ($pendingCount > 0): ?>
          <span class="nav-badge"><?= $pendingCount ?></span>
        <?php endif; ?>
      </a>
      <a href="<?= htmlspecialchars(app_admin_url('users/list.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= $activePage === 'users' ? 'active' : '' ?>">
        <i class="fas fa-users"></i> Khách hàng
      </a>
      <a href="<?= htmlspecialchars(app_admin_url('categories/list.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= $activePage === 'categories' ? 'active' : '' ?>">
        <i class="fas fa-tags"></i> Danh mục tour
      </a>
      <a href="<?= htmlspecialchars(app_admin_url('reviews/list.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= $activePage === 'reviews' ? 'active' : '' ?>">
        <i class="fas fa-star"></i> Đánh giá
      </a>
      <a href="<?= htmlspecialchars(app_admin_url('cancel_requests/list.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= $activePage === 'cancel_requests' ? 'active' : '' ?>">
        <i class="fas fa-ban"></i> Yêu cầu hủy tour
      </a>
      <a href="<?= htmlspecialchars(app_admin_url('coupons/list.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= $activePage === 'coupons' ? 'active' : '' ?>">
        <i class="fas fa-ticket-alt"></i> Mã giảm giá
      </a>
      <a href="<?= htmlspecialchars(app_admin_url('blog/list.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= $activePage === 'blog_admin' ? 'active' : '' ?>">
        <i class="fas fa-newspaper"></i> Blog (admin)
      </a>
      <a href="<?= htmlspecialchars(app_admin_url('reports/revenue.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= $activePage === 'reports' ? 'active' : '' ?>">
        <i class="fas fa-chart-line"></i> Báo cáo doanh thu
      </a>

      <?php if ($adminRole === 'admin'): ?>
      <div class="nav-section-label">Hệ thống</div>
      <a href="<?= htmlspecialchars(app_admin_url('settings/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= $activePage === 'settings' ? 'active' : '' ?>">
        <i class="fas fa-cog"></i> Cài đặt hệ thống
      </a>
      <a href="<?= htmlspecialchars(app_admin_url('staff/list.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= $activePage === 'staff' ? 'active' : '' ?>">
        <i class="fas fa-user-tie"></i> Nhân viên
      </a>
      <?php endif; ?>

      <div class="nav-section-label">Liên kết</div>
      <a href="<?= htmlspecialchars(app_url('frontend/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">
        <i class="fas fa-globe"></i> Trang khách hàng
      </a>

    </nav>

    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="user-avatar"><?= adminH($initials) ?></div>
        <div class="user-info">
          <strong><?= adminH($adminName) ?></strong>
          <span><?= $adminRole === 'admin' ? 'Quản trị viên' : 'Nhân viên' ?></span>
        </div>
        <a href="<?= htmlspecialchars(app_url('auth/logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-user-logout" title="Đăng xuất">
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
