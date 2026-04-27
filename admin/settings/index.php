<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ' . app_url('auth/login.php'));
    exit;
}

$mysqlVer = '';
try {
    $mysqlVer = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
} catch (Throwable) {
    $mysqlVer = '—';
}

function h(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$pageTitle    = 'Cài đặt hệ thống';
$pageSubtitle = 'Thông tin runtime (không chỉnh mật khẩu DB tại đây)';
$activePage   = 'settings';

require dirname(__DIR__, 2) . '/includes/admin_header.php';
?>

<div class="data-card" style="max-width:640px">
  <div style="display:grid;gap:14px;padding:8px 4px 16px">
    <div>
      <div class="cell-muted" style="font-size:0.75rem;text-transform:uppercase">PHP</div>
      <div><?= h(PHP_VERSION) ?></div>
    </div>
    <div>
      <div class="cell-muted" style="font-size:0.75rem;text-transform:uppercase">MySQL</div>
      <div><?= h($mysqlVer) ?></div>
    </div>
    <div>
      <div class="cell-muted" style="font-size:0.75rem;text-transform:uppercase">Database</div>
      <div><?= h($dbName ?? 'tour_dulich') ?></div>
    </div>
    <div>
      <div class="cell-muted" style="font-size:0.75rem;text-transform:uppercase">Cấu hình kết nối</div>
      <p class="cell-muted" style="font-size:0.88rem;margin:6px 0 0">
        Host / port / user / mật khẩu được đặt trong file <code>includes/config.php</code> trên server.
      </p>
    </div>
    <div>
      <div class="cell-muted" style="font-size:0.75rem;text-transform:uppercase">Migration bổ sung</div>
      <p class="cell-muted" style="font-size:0.88rem;margin:6px 0 0">
        Mã giảm giá &amp; bài blog: <code>database/migrations/001_admin_coupons_blog.sql</code>
      </p>
    </div>
  </div>
</div>

<?php require dirname(__DIR__, 2) . '/includes/admin_footer.php'; ?>
