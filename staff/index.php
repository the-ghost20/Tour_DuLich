<?php
declare(strict_types=1);

require_once dirname(__DIR__, 1) . '/includes/db.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)) {
    header('Location: ' . app_url('auth/login.php'));
    exit;
}

$toursSoldOut = [];
try {
    $toursSoldOut = $pdo->query(
        "SELECT id, tour_name FROM tours WHERE status = 'hiện' AND available_slots = 0 ORDER BY id DESC LIMIT 15"
    )->fetchAll();
} catch (Throwable) {
    $toursSoldOut = [];
}

$pageTitle    = 'Bảng điều khiển';
$pageSubtitle = 'Tổng quan công việc nhân viên';
$activePage   = 'dashboard';
require dirname(__DIR__, 1) . '/includes/staff_header.php';
?>

<?php if (!empty($toursSoldOut)): ?>
  <div class="alert alert-warning" style="margin-bottom:20px;border-left:4px solid #f59e0b">
    <strong><i class="fas fa-box-open"></i> Có <?= count($toursSoldOut) ?> tour đang hiển thị nhưng đã hết chỗ</strong>
    (khách không thấy trên website).
    <a href="<?= htmlspecialchars(app_staff_url('tours/update_slots.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-sm" style="margin-left:10px">Mở cập nhật số chỗ</a>
    <a href="<?= htmlspecialchars(app_admin_url('tours/list.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-ghost btn-sm" style="margin-left:6px">Quản lý tour (ẩn / sửa)</a>
  </div>
<?php endif; ?>

<div class="data-card">
  <div class="data-card-header">
    <h2 class="data-card-title"><i class="fas fa-hand-wave" style="margin-right:8px;opacity:0.85"></i>Xin chào</h2>
    <p class="data-card-sub">Các chức năng xử lý đơn, blog, đánh giá và liên hệ đang được hoàn thiện. Bạn có thể cập nhật hồ sơ và mật khẩu bất cứ lúc nào.</p>
  </div>
  <div style="display:flex;flex-wrap:wrap;gap:10px;padding:8px 4px 16px">
    <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars(app_staff_url('profile.php'), ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-id-card"></i> Mở hồ sơ cá nhân</a>
    <a class="btn btn-ghost btn-sm" href="<?= htmlspecialchars(app_staff_url('bookings/list.php'), ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-clipboard-list"></i> Đơn đặt tour</a>
  </div>
</div>
<?php require dirname(__DIR__, 1) . '/includes/staff_footer.php';
