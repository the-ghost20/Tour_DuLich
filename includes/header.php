<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/**
 * Usage:
 *   $activePage = 'tours'; // optional
 *   $u = ''; $a = '../auth/';       // trong thư mục frontend/
 *   $u = '../frontend/'; $a = '';  // trong thư mục auth/
 *   require __DIR__ . '/../includes/header.php';
 */
$activePage = isset($activePage) ? (string) $activePage : '';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$u = isset($u) ? (string) $u : '';
$a = isset($a) ? (string) $a : '../auth/';

$isLoggedIn = !empty($_SESSION['user_id']);
$currentUserName = isset($_SESSION['full_name']) ? (string) $_SESSION['full_name'] : '';
$userRole = isset($_SESSION['role']) ? (string) $_SESSION['role'] : '';
$isAdmin  = $isLoggedIn && $userRole === 'admin';
$isStaff  = $isLoggedIn && $userRole === 'staff';
$isCustomerNav = !$isLoggedIn || (!$isAdmin && !$isStaff);
?>
<!-- NAVIGATION BAR -->
<nav class="navbar" aria-label="Điều hướng chính">
  <div class="navbar-container">
    <div class="navbar-start">
    <a href="<?= htmlspecialchars($u . 'index.php', ENT_QUOTES, 'UTF-8') ?>" class="navbar-logo">
      <span class="navbar-logo-mark" aria-hidden="true"><i class="fas fa-map-marker-alt"></i></span>
      <span class="navbar-logo-text">Du lịch Việt</span>
    </a>

    <div class="navbar-links">
      <ul class="navbar-menu">
        <li><a href="<?= htmlspecialchars($u . 'index.php', ENT_QUOTES, 'UTF-8') ?>" class="nav-link <?= $activePage === 'home' ? 'active' : '' ?>">Trang chủ</a></li>
        <li><a href="<?= htmlspecialchars($u . 'about.php', ENT_QUOTES, 'UTF-8') ?>" class="nav-link <?= $activePage === 'about' ? 'active' : '' ?>">Giới thiệu</a></li>
        <li><a href="<?= htmlspecialchars($u . 'tours.php', ENT_QUOTES, 'UTF-8') ?>" class="nav-link <?= $activePage === 'tours' ? 'active' : '' ?>">Tour</a></li>
        <li><a href="<?= htmlspecialchars($u . 'pricing.php', ENT_QUOTES, 'UTF-8') ?>" class="nav-link <?= $activePage === 'pricing' ? 'active' : '' ?>">Bảng giá</a></li>
        <li><a href="<?= htmlspecialchars($u . 'blog.php', ENT_QUOTES, 'UTF-8') ?>" class="nav-link <?= $activePage === 'blog' ? 'active' : '' ?>">Blog</a></li>
        <?php if ($isCustomerNav): ?>
        <li><a href="<?= htmlspecialchars($u . 'wishlist.php', ENT_QUOTES, 'UTF-8') ?>" class="nav-link <?= $activePage === 'wishlist' ? 'active' : '' ?>">Yêu thích</a></li>
        <?php endif; ?>
        <li><a href="#contact" class="nav-link">Liên hệ</a></li>
      </ul>
    </div>
    </div>

    <div class="navbar-right">
      <div class="search-box">
        <input type="search" placeholder="Tìm tour, điểm đến…" class="search-input" autocomplete="off" />
        <i class="fas fa-search" aria-hidden="true"></i>
      </div>
      <?php if (!$isLoggedIn): ?>
        <div class="auth-header-actions">
          <a
            class="btn-login"
            href="<?= htmlspecialchars($a . 'login.php', ENT_QUOTES, 'UTF-8') ?>"
            id="header-login-trigger"
            data-login-trigger="1"
          >
            ĐĂNG NHẬP
          </a>
        </div>
      <?php else: ?>
        <div class="user-menu">
          <button class="user-menu-btn" type="button">
            <span class="user-menu-btn-label">Xin chào, <?= htmlspecialchars($currentUserName !== '' ? $currentUserName : 'Tài khoản', ENT_QUOTES, 'UTF-8') ?></span>
            <i class="fas fa-chevron-down" aria-hidden="true"></i>
          </button>
          <div class="user-menu-dropdown">
            <?php if ($isAdmin): ?>
              <a href="<?= htmlspecialchars(app_admin_url('index.php'), ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-tachometer-alt"></i> Bảng điều khiển</a>
              <a href="<?= htmlspecialchars(app_admin_url('users/list.php'), ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-users"></i> Khách hàng</a>
              <a href="<?= htmlspecialchars(app_admin_url('staff/list.php'), ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-user-tie"></i> Nhân viên</a>
              <a href="<?= htmlspecialchars($u . 'profile.php', ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-id-card"></i> Hồ sơ cá nhân</a>
            <?php elseif ($isStaff): ?>
              <a href="<?= htmlspecialchars(app_staff_url('index.php'), ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-gauge-high"></i> Khu vực nhân viên</a>
              <a href="<?= htmlspecialchars(app_staff_url('bookings/list.php'), ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-clipboard-list"></i> Đơn đặt tour</a>
              <a href="<?= htmlspecialchars(app_staff_url('profile.php'), ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-id-card"></i> Hồ sơ cá nhân</a>
            <?php else: ?>
              <a href="<?= htmlspecialchars($u . 'profile.php', ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-id-card"></i> Hồ sơ cá nhân</a>
              <a href="<?= htmlspecialchars($u . 'wishlist.php', ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-heart"></i> Tour yêu thích</a>
              <a href="<?= htmlspecialchars($u . 'my_bookings.php', ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-receipt"></i> Lịch sử đặt tour</a>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($a . 'logout.php', ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div class="mobile-toggle">
      <i class="fas fa-bars"></i>
    </div>
  </div>
</nav>

