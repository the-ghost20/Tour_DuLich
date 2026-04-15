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
    $code = strtoupper(trim((string) ($_POST['code'] ?? '')));
    $type = (string) ($_POST['discount_type'] ?? 'percent');
    $val  = (float) ($_POST['discount_value'] ?? 0);
    $min  = (float) ($_POST['min_order_amount'] ?? 0);
    $maxu = ($_POST['max_uses'] ?? '') === '' ? null : (int) $_POST['max_uses'];
    $starts = trim((string) ($_POST['starts_at'] ?? ''));
    $ends   = trim((string) ($_POST['expires_at'] ?? ''));
    $active = isset($_POST['is_active']) ? 1 : 0;

    if ($code === '' || $val <= 0) {
        $err = 'Nhập mã và giá trị giảm hợp lệ.';
    } elseif ($type === 'percent' && $val > 100) {
        $err = 'Phần trăm không quá 100.';
    } else {
        try {
            $pdo->prepare(
                'INSERT INTO coupons (code, discount_type, discount_value, min_order_amount, max_uses, starts_at, expires_at, is_active)
                 VALUES (:c,:t,:v,:m,:mu,:s,:e,:a)'
            )->execute([
                'c'  => $code,
                't'  => $type === 'fixed' ? 'fixed' : 'percent',
                'v'  => $val,
                'm'  => $min,
                'mu' => $maxu,
                's'  => $starts === '' ? null : $starts,
                'e'  => $ends === '' ? null : $ends,
                'a'  => $active,
            ]);
            header('Location: list.php', true, 302);
            exit;
        } catch (Throwable) {
            $err = 'Không lưu được (mã trùng hoặc chưa có bảng coupons).';
        }
    }
}

function h(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$pageTitle    = 'Thêm mã giảm giá';
$activePage   = 'coupons';
$topbarActions = '<a href="list.php" class="topbar-btn topbar-btn-ghost"><i class="fas fa-arrow-left"></i> Danh sách</a>';

require dirname(__DIR__, 2) . '/includes/admin_header.php';
?>

<div class="data-card" style="max-width:560px">
  <?php if ($err): ?>
    <div class="alert alert-danger"><?= h($err) ?></div>
  <?php endif; ?>
  <form method="post" style="display:grid;gap:12px;padding:8px 4px 16px">
    <div>
      <label class="cell-muted" style="font-size:0.8rem">Mã</label>
      <input class="form-control" name="code" required value="<?= h((string) ($_POST['code'] ?? '')) ?>" />
    </div>
    <div>
      <label class="cell-muted" style="font-size:0.8rem">Loại</label>
      <select class="form-control" name="discount_type">
        <option value="percent" <?= (($_POST['discount_type'] ?? '') === 'fixed') ? '' : 'selected' ?>>Phần trăm</option>
        <option value="fixed" <?= (($_POST['discount_type'] ?? '') === 'fixed') ? 'selected' : '' ?>>Số tiền cố định</option>
      </select>
    </div>
    <div>
      <label class="cell-muted" style="font-size:0.8rem">Giá trị (% hoặc đ)</label>
      <input class="form-control" type="number" step="0.01" name="discount_value" required value="<?= h((string) ($_POST['discount_value'] ?? '')) ?>" />
    </div>
    <div>
      <label class="cell-muted" style="font-size:0.8rem">Đơn tối thiểu (đ)</label>
      <input class="form-control" type="number" step="1000" name="min_order_amount" value="<?= h((string) ($_POST['min_order_amount'] ?? '0')) ?>" />
    </div>
    <div>
      <label class="cell-muted" style="font-size:0.8rem">Giới hạn lượt dùng (để trống = không giới hạn)</label>
      <input class="form-control" type="number" name="max_uses" value="<?= h((string) ($_POST['max_uses'] ?? '')) ?>" />
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Bắt đầu</label>
        <input class="form-control" type="date" name="starts_at" value="<?= h((string) ($_POST['starts_at'] ?? '')) ?>" />
      </div>
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Hết hạn</label>
        <input class="form-control" type="date" name="expires_at" value="<?= h((string) ($_POST['expires_at'] ?? '')) ?>" />
      </div>
    </div>
    <label style="display:flex;align-items:center;gap:8px;font-size:0.9rem">
      <input type="checkbox" name="is_active" value="1" <?= !isset($_POST['code']) || isset($_POST['is_active']) ? 'checked' : '' ?> /> Kích hoạt
    </label>
    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Lưu</button>
  </form>
</div>

<?php require dirname(__DIR__, 2) . '/includes/admin_footer.php'; ?>
