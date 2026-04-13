<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/db.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)) {
    header('Location: ' . app_url('auth/login.php'));
    exit;
}
$pageTitle = 'Don can xu ly';
$activePage = 'bookings';
require dirname(__DIR__, 2) . '/includes/staff_header.php';
?>
<div class="data-card">
  <p class="cell-muted">Trang staff dang duoc xay dung.</p>
  <p><a class="btn btn-ghost btn-sm" href="<?= htmlspecialchars(app_staff_url('index.php'), ENT_QUOTES, 'UTF-8') ?>">Ve staff dashboard</a></p>
</div>
<?php require dirname(__DIR__, 2) . '/includes/staff_footer.php';
