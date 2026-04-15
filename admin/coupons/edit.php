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

$id = (int) ($_GET['id'] ?? 0);
$row = null;
if ($id > 0) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM coupons WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
    } catch (Throwable) {
        $row = null;
    }
}

$err = '';
if ($row && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim((string) ($_POST['code'] ?? '')));
    $type = (string) ($_POST['discount_type'] ?? 'percent');
    $val  = (float) ($_POST['discount_value'] ?? 0);
    $min  = (float) ($_POST['min_order_amount'] ?? 0);
    $starts = trim((string) ($_POST['starts_at'] ?? ''));
    $ends   = trim((string) ($_POST['expires_at'] ?? ''));
    $active = isset($_POST['is_active']) ? 1 : 0;
    $used   = (int) ($_POST['used_count'] ?? $row['used_count']);
    $maxRaw = trim((string) ($_POST['max_uses'] ?? ''));
    $maxu   = $maxRaw === '' ? null : (int) $_POST['max_uses'];

    if ($code === '' || $val <= 0) {
        $err = 'Nhập mã và giá trị hợp lệ.';
    } elseif ($type === 'percent' && $val > 100) {
        $err = 'Phần trăm không quá 100.';
    } else {
        try {
            $pdo->prepare(
                'UPDATE coupons SET code=:c, discount_type=:t, discount_value=:v, min_order_amount=:m,
                 max_uses=:mu, used_count=:uc, starts_at=:s, expires_at=:e, is_active=:a WHERE id=:id'
            )->execute([
                'c'  => $code,
                't'  => $type === 'fixed' ? 'fixed' : 'percent',
                'v'  => $val,
                'm'  => $min,
                'mu' => $maxu,
                'uc' => max(0, $used),
                's'  => $starts === '' ? null : $starts,
                'e'  => $ends === '' ? null : $ends,
                'a'  => $active,
                'id' => $id,
            ]);
            header('Location: list.php', true, 302);
            exit;
        } catch (Throwable) {
            $err = 'Không lưu được.';
        }
    }
}

function h(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$pageTitle    = 'Sửa mã giảm giá';
$activePage   = 'coupons';
$topbarActions = '<a href="list.php" class="topbar-btn topbar-btn-ghost"><i class="fas fa-arrow-left"></i> Danh sách</a>';

require dirname(__DIR__, 2) . '/includes/admin_header.php';
?>

<?php if (!$row): ?>
  <div class="data-card"><p class="cell-muted">Không tìm thấy.</p><a href="list.php" class="btn btn-ghost btn-sm">← Quay lại</a></div>
<?php else: ?>
  <div class="data-card" style="max-width:560px">
    <?php if ($err): ?>
      <div class="alert alert-danger"><?= h($err) ?></div>
    <?php endif; ?>
    <form method="post" style="display:grid;gap:12px;padding:8px 4px 16px">
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Mã</label>
        <input class="form-control" name="code" required value="<?= h((string) ($_POST['code'] ?? $row['code'])) ?>" />
      </div>
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Loại</label>
        <select class="form-control" name="discount_type">
          <?php $t = (string) ($_POST['discount_type'] ?? $row['discount_type']); ?>
          <option value="percent" <?= $t !== 'fixed' ? 'selected' : '' ?>>Phần trăm</option>
          <option value="fixed" <?= $t === 'fixed' ? 'selected' : '' ?>>Số tiền cố định</option>
        </select>
      </div>
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Giá trị</label>
        <input class="form-control" type="number" step="0.01" name="discount_value" required value="<?= h((string) ($_POST['discount_value'] ?? $row['discount_value'])) ?>" />
      </div>
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Đơn tối thiểu</label>
        <input class="form-control" type="number" step="1000" name="min_order_amount" value="<?= h((string) ($_POST['min_order_amount'] ?? $row['min_order_amount'])) ?>" />
      </div>
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Đã dùng / tối đa</label>
        <div style="display:flex;gap:8px">
          <input class="form-control" type="number" name="used_count" value="<?= h((string) ($_POST['used_count'] ?? $row['used_count'])) ?>" />
          <input class="form-control" type="number" name="max_uses" placeholder="Max" value="<?= h((string) ($_POST['max_uses'] ?? ($row['max_uses'] ?? ''))) ?>" />
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div>
          <label class="cell-muted" style="font-size:0.8rem">Bắt đầu</label>
          <input class="form-control" type="date" name="starts_at" value="<?= h((string) ($_POST['starts_at'] ?? ($row['starts_at'] ? substr((string) $row['starts_at'], 0, 10) : ''))) ?>" />
        </div>
        <div>
          <label class="cell-muted" style="font-size:0.8rem">Hết hạn</label>
          <input class="form-control" type="date" name="expires_at" value="<?= h((string) ($_POST['expires_at'] ?? ($row['expires_at'] ? substr((string) $row['expires_at'], 0, 10) : ''))) ?>" />
        </div>
      </div>
      <label style="display:flex;align-items:center;gap:8px">
        <?php
          $actChk = isset($_POST['code'])
              ? isset($_POST['is_active'])
              : ((int) $row['is_active'] === 1);
        ?>
        <input type="checkbox" name="is_active" value="1" <?= $actChk ? 'checked' : '' ?> /> Kích hoạt
      </label>
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Cập nhật</button>
    </form>
  </div>
<?php endif; ?>

<?php require dirname(__DIR__, 2) . '/includes/admin_footer.php'; ?>
