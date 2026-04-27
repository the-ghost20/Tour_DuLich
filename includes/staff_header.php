<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$activePage   = isset($activePage)   ? (string) $activePage   : 'dashboard';
$pageTitle    = isset($pageTitle)    ? (string) $pageTitle    : 'Nhân viên';
$pageSubtitle = isset($pageSubtitle) ? (string) $pageSubtitle : '';

$staffName = isset($_SESSION['full_name']) ? (string) $_SESSION['full_name'] : 'Nhân viên';
$staffRole = isset($_SESSION['role'])      ? (string) $_SESSION['role']      : 'staff';
$initials  = mb_strtoupper(mb_substr($staffName, 0, 1, 'UTF-8'), 'UTF-8');

function staffH(mixed $v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= staffH($pageTitle) ?> — Du Lịch Việt</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="<?= htmlspecialchars(app_asset_url('css/staff.css'), ENT_QUOTES, 'UTF-8') ?>" />
  <?= $extraHead ?? '' ?>
</head>
<body>

<div class="sidebar-overlay" id="staffSidebarOverlay"></div>

<div class="admin-layout">

  <aside class="admin-sidebar" id="staffSidebar">
    <a href="<?= htmlspecialchars(app_staff_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-brand">
      <div class="brand-icon"><i class="fas fa-suitcase-rolling"></i></div>
      <div class="brand-text">
        <strong>Du Lịch Việt</strong>
        <span>Khu vực nhân viên</span>
      </div>
    </a>

    <nav class="sidebar-nav">
      <div class="nav-section-label">Công việc</div>
      <a href="<?= htmlspecialchars(app_staff_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
        <i class="fas fa-gauge-high"></i> Bảng điều khiển
      </a>
      <a href="<?= htmlspecialchars(app_staff_url('bookings/list.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= $activePage === 'bookings' ? 'active' : '' ?>">
        <i class="fas fa-clipboard-list"></i> Đơn đặt tour
      </a>
      <a href="<?= htmlspecialchars(app_staff_url('tours/update_slots.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= $activePage === 'tour_slots' ? 'active' : '' ?>">
        <i class="fas fa-users-line"></i> Cập nhật chỗ trống
      </a>
      <a href="<?= htmlspecialchars(app_staff_url('reviews/list.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= $activePage === 'reviews' ? 'active' : '' ?>">
        <i class="fas fa-star-half-stroke"></i> Đánh giá
      </a>
      <a href="<?= htmlspecialchars(app_staff_url('blog/list.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= $activePage === 'blog' ? 'active' : '' ?>">
        <i class="fas fa-pen-to-square"></i> Blog
      </a>
      <a href="<?= htmlspecialchars(app_staff_url('contact/list.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= $activePage === 'contact' ? 'active' : '' ?>">
        <i class="fas fa-envelope-open-text"></i> Liên hệ
      </a>
      <div class="nav-section-label">Tài khoản</div>
      <a href="<?= htmlspecialchars(app_staff_url('profile.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item <?= $activePage === 'profile' ? 'active' : '' ?>">
        <i class="fas fa-id-card"></i> Hồ sơ cá nhân
      </a>
      <div class="nav-section-label">Liên kết</div>
      <a href="<?= htmlspecialchars(app_url('frontend/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">
        <i class="fas fa-globe"></i> Trang khách hàng
      </a>
      <?php if ($staffRole === 'admin'): ?>
      <a href="<?= htmlspecialchars(app_admin_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">
        <i class="fas fa-shield-halved"></i> Trang quản trị
      </a>
      <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="user-avatar"><?= staffH($initials) ?></div>
        <div class="user-info">
          <strong><?= staffH($staffName) ?></strong>
          <span><?= $staffRole === 'admin' ? 'Quản trị viên' : 'Nhân viên' ?></span>
        </div>
        <div class="staff-sidebar-user-actions">
          <a href="<?= htmlspecialchars(app_staff_url('profile.php'), ENT_QUOTES, 'UTF-8') ?>" class="staff-sidebar-icon-btn" title="Hồ sơ cá nhân">
            <i class="fas fa-user-gear"></i>
          </a>
          <a href="<?= htmlspecialchars(app_url('auth/logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="staff-sidebar-icon-btn staff-sidebar-icon-btn--logout" title="Đăng xuất">
            <i class="fas fa-right-from-bracket"></i>
          </a>
        </div>
      </div>
    </div>
  </aside>

  <main class="admin-main">
    <header class="admin-topbar">
      <button class="sidebar-toggle" id="staffSidebarToggle" aria-label="Mở hoặc đóng menu">
        <i class="fas fa-bars"></i>
      </button>
      <div class="topbar-title">
        <h1><?= staffH($pageTitle) ?></h1>
        <?php if ($pageSubtitle): ?>
          <p><?= staffH($pageSubtitle) ?></p>
        <?php endif; ?>
      </div>
      <div class="topbar-actions"><?= $topbarActions ?? '' ?></div>
    </header>
    <div class="admin-body staff-body">
