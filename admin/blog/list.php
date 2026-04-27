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

$postsOk = false;
$posts   = [];
try {
    $posts = $pdo->query(
        "SELECT p.*, u.full_name AS author_name
         FROM blog_posts p
         LEFT JOIN users u ON u.id = p.author_id
         ORDER BY p.created_at DESC"
    )->fetchAll();
    $postsOk = true;
} catch (Throwable) {
    $postsOk = false;
}

$feedback = [];
try {
    $feedback = $pdo->query(
        "SELECT f.*, u.full_name, u.email
         FROM blog_feedback f
         LEFT JOIN users u ON u.id = f.user_id
         ORDER BY f.created_at DESC
         LIMIT 100"
    )->fetchAll();
} catch (Throwable) {
    $feedback = [];
}

function h(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$pageTitle    = 'Blog (admin)';
$pageSubtitle = 'Bài viết + phản hồi từ trang blog khách';
$activePage   = 'blog_admin';

$topbarActions = $postsOk
    ? '<a href="add.php" class="topbar-btn topbar-btn-primary"><i class="fas fa-plus"></i> Viết bài</a>'
    : '';

require dirname(__DIR__, 2) . '/includes/admin_header.php';
?>

<?php if (!$postsOk): ?>
  <div class="alert alert-warning">
    Chưa có bảng <code>blog_posts</code>. Import <code>database/migrations/001_admin_coupons_blog.sql</code>.
  </div>
<?php else: ?>
  <div class="data-card" style="margin-bottom:24px">
    <div class="data-card-header">
      <div>
        <div class="data-card-title">Bài viết</div>
        <div class="data-card-sub"><?= count($posts) ?> bài</div>
      </div>
    </div>
    <div style="overflow-x:auto">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Tiêu đề</th>
            <th>Slug</th>
            <th>Trạng thái</th>
            <th>Xuất bản</th>
            <th>Tác giả</th>
            <th class="cell-right"></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($posts)): ?>
            <tr><td colspan="6"><div class="empty-state"><i class="fas fa-newspaper"></i><p>Chưa có bài.</p></div></td></tr>
          <?php else: ?>
            <?php foreach ($posts as $p): ?>
              <tr>
                <td class="cell-bold"><?= h($p['title']) ?></td>
                <td class="cell-muted"><?= h($p['slug']) ?></td>
                <td><?= h($p['status']) === 'published' ? '<span class="badge badge-success">Đã đăng</span>' : '<span class="badge badge-neutral">Nháp</span>' ?></td>
                <td class="cell-muted"><?= $p['published_at'] ? h($p['published_at']) : '—' ?></td>
                <td><?= h($p['author_name'] ?? '—') ?></td>
                <td class="cell-right">
                  <?php if ((string) ($p['status'] ?? '') === 'published'): ?>
                    <a href="<?= h(app_url('frontend/blog_detail.php?slug=' . rawurlencode((string) $p['slug']))) ?>" class="btn btn-ghost btn-sm btn-icon" title="Xem bài"><i class="fas fa-eye"></i></a>
                  <?php endif; ?>
                  <a href="edit.php?id=<?= (int) $p['id'] ?>" class="btn btn-ghost btn-sm btn-icon"><i class="fas fa-pen"></i></a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<div class="data-card">
  <div class="data-card-header">
    <div>
      <div class="data-card-title">Phản hồi / đánh giá nội dung blog</div>
      <div class="data-card-sub">Từ form trên <code>frontend/blog.php</code></div>
    </div>
  </div>
  <div style="overflow-x:auto">
    <table class="admin-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Khách</th>
          <th class="cell-right">Sao</th>
          <th>Bình luận</th>
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
              <td style="max-width:300px;white-space:pre-wrap"><?= h($f['comment']) ?></td>
              <td class="cell-muted"><?= date('d/m/Y H:i', strtotime((string) $f['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require dirname(__DIR__, 2) . '/includes/admin_footer.php'; ?>
