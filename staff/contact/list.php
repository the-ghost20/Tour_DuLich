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

$feedback = [];
$tableOk = true;
try {
    $feedback = $pdo->query(
        "SELECT f.*, u.full_name, u.email
         FROM blog_feedback f
         LEFT JOIN users u ON u.id = f.user_id
         ORDER BY f.created_at DESC
         LIMIT 200"
    )->fetchAll();
} catch (Throwable) {
    $feedback = [];
    $tableOk = false;
}

function h(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$pageTitle    = 'Liên hệ & góp ý';
$pageSubtitle = 'Phản hồi từ form đánh giá nội dung blog (trang blog khách)';
$activePage   = 'contact';

require dirname(__DIR__, 2) . '/includes/staff_header.php';
?>

<?php if (!$tableOk): ?>
  <div class="alert alert-warning">
    Chưa có bảng <code>blog_feedback</code>. Hãy import schema đầy đủ từ <code>database/tour_management.sql</code> (hoặc tạo bảng tương ứng).
  </div>
<?php else: ?>
  <div class="data-card">
    <div class="data-card-header">
      <div>
        <div class="data-card-title">Phản hồi blog</div>
        <div class="data-card-sub"><?= count($feedback) ?> mục gần nhất</div>
      </div>
    </div>
    <div style="overflow-x:auto">
      <table class="admin-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Khách</th>
            <th class="cell-right">Sao</th>
            <th>Bình luận / góp ý</th>
            <th>Ngày</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($feedback)): ?>
            <tr><td colspan="5"><div class="empty-state"><i class="fas fa-comments"></i><p>Chưa có phản hồi.</p></div></td></tr>
          <?php else: ?>
            <?php foreach ($feedback as $f): ?>
              <tr>
                <td>#<?= (int) $f['id'] ?></td>
                <td>
                  <div><?= $f['full_name'] ? h($f['full_name']) : '<span class="cell-muted">Khách (chưa đăng nhập)</span>' ?></div>
                  <?php if (!empty($f['email'])): ?><div class="cell-muted" style="font-size:0.8rem"><?= h($f['email']) ?></div><?php endif; ?>
                </td>
                <td class="cell-right"><span class="badge badge-info"><?= (int) $f['rating'] ?>/5</span></td>
                <td style="max-width:360px;white-space:pre-wrap"><?= h($f['comment']) ?></td>
                <td class="cell-muted"><?= date('d/m/Y H:i', strtotime((string) $f['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require dirname(__DIR__, 2) . '/includes/staff_footer.php';
