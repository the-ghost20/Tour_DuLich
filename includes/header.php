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
?>
<!-- NAVIGATION BAR -->
<nav class="navbar">
  <div class="navbar-container">
    <a href="<?= htmlspecialchars($u . 'index.php', ENT_QUOTES, 'UTF-8') ?>" class="navbar-logo">
      <i class="fas fa-map-marker-alt"></i>
      <span>Du lịch Việt</span>
    </a>

    <ul class="navbar-menu">
      <li><a href="<?= htmlspecialchars($u . 'index.php', ENT_QUOTES, 'UTF-8') ?>" class="nav-link <?= $activePage === 'home' ? 'active' : '' ?>">TRANG CHỦ</a></li>
      <li><a href="<?= htmlspecialchars($u . 'about.php', ENT_QUOTES, 'UTF-8') ?>" class="nav-link <?= $activePage === 'about' ? 'active' : '' ?>">GIỚI THIỆU</a></li>
      <li><a href="<?= htmlspecialchars($u . 'tours.php', ENT_QUOTES, 'UTF-8') ?>" class="nav-link <?= $activePage === 'tours' ? 'active' : '' ?>">TOUR DU LỊCH</a></li>
      <li><a href="<?= htmlspecialchars($u . 'pricing.php', ENT_QUOTES, 'UTF-8') ?>" class="nav-link <?= $activePage === 'pricing' ? 'active' : '' ?>">BẢNG GIÁ</a></li>
      <li><a href="<?= htmlspecialchars($u . 'blog.php', ENT_QUOTES, 'UTF-8') ?>" class="nav-link <?= $activePage === 'blog' ? 'active' : '' ?>">BLOG</a></li>
      <li><a href="<?= htmlspecialchars($u . 'wishlist.php', ENT_QUOTES, 'UTF-8') ?>" class="nav-link <?= $activePage === 'wishlist' ? 'active' : '' ?>">YÊU THÍCH</a></li>
      <li><a href="#contact" class="nav-link">LIÊN HỆ</a></li>
    </ul>

    <div class="navbar-right">
      <div class="search-box">
        <input type="text" placeholder="Tra cứu..." class="search-input" />
        <i class="fas fa-search"></i>
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
            Xin chào, <?= htmlspecialchars($currentUserName !== '' ? $currentUserName : 'Tài khoản', ENT_QUOTES, 'UTF-8') ?>
            <i class="fas fa-chevron-down"></i>
          </button>
          <div class="user-menu-dropdown">
            <a href="<?= htmlspecialchars($u . 'profile.php', ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-id-card"></i> Hồ sơ cá nhân</a>
            <a href="<?= htmlspecialchars($u . 'wishlist.php', ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-heart"></i> Tour yêu thích</a>
            <a href="<?= htmlspecialchars($u . 'my_bookings.php', ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-receipt"></i> Lịch sử đặt tour</a>
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

