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

$flash = '';
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $did = (int) $_POST['delete_id'];
    if ($did > 0) {
        try {
            $pdo->prepare('DELETE FROM tour_reviews WHERE id = :id')->execute(['id' => $did]);
            $flash = 'Đã xóa đánh giá.';
        } catch (Throwable) {
            $flash     = 'Không xóa được.';
            $flashType = 'danger';
        }
    }
}

$rows = [];
try {
    $rows = $pdo->query(
        "SELECT r.id, r.rating, r.comment, r.created_at,
                t.tour_name, t.destination,
                u.full_name, u.email
         FROM tour_reviews r
         JOIN tours t ON t.id = r.tour_id
         JOIN users u ON u.id = r.user_id
         ORDER BY r.created_at DESC"
    )->fetchAll();
} catch (Throwable) {
    $rows = [];
}

function h(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$pageTitle    = 'Đánh giá tour';
$pageSubtitle = 'Nhận xét từ khách (có thể xóa nội dung không phù hợp)';
$activePage   = 'reviews';

require dirname(__DIR__, 2) . '/includes/staff_header.php';
?>

<?php if ($flash): ?>
  <div class="alert alert-<?= h($flashType) ?>"><i class="fas fa-info-circle"></i> <?= h($flash) ?></div>
<?php endif; ?>

<div class="data-card">
  <div style="overflow-x:auto">
    <table class="admin-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Tour</th>
          <th>Khách</th>
          <th class="cell-right">Sao</th>
          <th>Nội dung</th>
          <th>Ngày</th>
          <th class="cell-right"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="7"><div class="empty-state"><i class="fas fa-star"></i><p>Chưa có đánh giá.</p></div></td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td>#<?= (int) $r['id'] ?></td>
              <td>
                <div class="cell-bold"><?= h($r['tour_name']) ?></div>
                <div class="cell-muted" style="font-size:0.8rem"><?= h($r['destination']) ?></div>
              </td>
              <td>
                <div><?= h($r['full_name']) ?></div>
                <div class="cell-muted" style="font-size:0.8rem"><?= h($r['email']) ?></div>
              </td>
              <td class="cell-right"><span class="badge badge-info"><?= (int) $r['rating'] ?>/5</span></td>
              <td style="max-width:280px;white-space:pre-wrap;font-size:0.88rem"><?= h($r['comment']) ?></td>
              <td class="cell-muted"><?= date('d/m/Y', strtotime((string) $r['created_at'])) ?></td>
              <td class="cell-right">
                <form method="post" style="margin:0" onsubmit="return confirm('Xóa đánh giá này?')">
                  <input type="hidden" name="delete_id" value="<?= (int) $r['id'] ?>" />
                  <button type="submit" class="btn btn-danger-ghost btn-sm btn-icon"><i class="fas fa-trash"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require dirname(__DIR__, 2) . '/includes/staff_footer.php';
