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

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string) ($_POST['title'] ?? ''));
    $slug  = trim((string) ($_POST['slug'] ?? ''));
    $ex    = trim((string) ($_POST['excerpt'] ?? ''));
    $body  = (string) ($_POST['body'] ?? '');
    $stat  = (string) ($_POST['status'] ?? 'draft');
    $pub   = trim((string) ($_POST['published_at'] ?? ''));
    $aid   = (int) $_SESSION['user_id'];

    if ($title === '') {
        $err = 'Nhập tiêu đề.';
    } else {
        if ($slug === '') {
            $slug = vn_slug($title);
        } else {
            $slug = vn_slug($slug);
        }
        if ($stat !== 'published') {
            $stat = 'draft';
        }
        $pubAt = null;
        if ($stat === 'published') {
            if ($pub !== '') {
                $tmp = str_replace('T', ' ', $pub);
                $pubAt = preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $tmp) ? $tmp . ':00' : date('Y-m-d H:i:s', strtotime($tmp) ?: time());
            } else {
                $pubAt = date('Y-m-d H:i:s');
            }
        }
        try {
            $pdo->prepare(
                'INSERT INTO blog_posts (title, slug, excerpt, body, status, published_at, author_id)
                 VALUES (:t,:s,:e,:b,:st,:p,:a)'
            )->execute([
                't'  => $title,
                's'  => $slug,
                'e'  => $ex === '' ? null : $ex,
                'b'  => $body,
                'st' => $stat,
                'p'  => $pubAt,
                'a'  => $aid,
            ]);
            header('Location: list.php', true, 302);
            exit;
        } catch (Throwable) {
            $err = 'Không lưu được (slug trùng hoặc chưa có bảng blog_posts).';
        }
    }
}

function h(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$pageTitle    = 'Viết bài blog';
$pageSubtitle = 'Soạn nội dung mới';
$activePage   = 'blog';
$topbarActions = '<a href="list.php" class="topbar-btn topbar-btn-ghost"><i class="fas fa-arrow-left"></i> Danh sách</a>';

require dirname(__DIR__, 2) . '/includes/staff_header.php';
?>

<div class="data-card" style="max-width:720px">
  <?php if ($err): ?>
    <div class="alert alert-danger"><?= h($err) ?></div>
  <?php endif; ?>
  <form method="post" style="display:grid;gap:12px;padding:8px 4px 16px">
    <div>
      <label class="cell-muted" style="font-size:0.8rem">Tiêu đề</label>
      <input class="form-control" name="title" required value="<?= h((string) ($_POST['title'] ?? '')) ?>" />
    </div>
    <div>
      <label class="cell-muted" style="font-size:0.8rem">Slug (tùy chọn)</label>
      <input class="form-control" name="slug" value="<?= h((string) ($_POST['slug'] ?? '')) ?>" />
    </div>
    <div>
      <label class="cell-muted" style="font-size:0.8rem">Tóm tắt</label>
      <input class="form-control" name="excerpt" value="<?= h((string) ($_POST['excerpt'] ?? '')) ?>" />
    </div>
    <div>
      <label class="cell-muted" style="font-size:0.8rem">Nội dung (HTML)</label>
      <textarea class="form-control" name="body" rows="10" style="font-family:monospace;font-size:0.85rem"><?= h((string) ($_POST['body'] ?? '')) ?></textarea>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Trạng thái</label>
        <select class="form-control" name="status">
          <option value="draft" <?= (($_POST['status'] ?? '') === 'published') ? '' : 'selected' ?>>Nháp</option>
          <option value="published" <?= (($_POST['status'] ?? '') === 'published') ? 'selected' : '' ?>>Xuất bản</option>
        </select>
      </div>
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Ngày xuất bản</label>
        <input class="form-control" type="datetime-local" name="published_at" value="<?= h((string) ($_POST['published_at'] ?? '')) ?>" />
      </div>
    </div>
    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Lưu</button>
  </form>
</div>

<?php require dirname(__DIR__, 2) . '/includes/staff_footer.php';
