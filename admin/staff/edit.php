<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ' . app_admin_url('index.php'));
    exit;
}

$id = (int) ($_GET['id'] ?? 0);
$row = null;
if ($id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND role = 'staff' LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
    } catch (Throwable) {
        $row = null;
    }
}

$err = '';
if ($row && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim((string) ($_POST['full_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $pass  = (string) ($_POST['password'] ?? '');
    $active = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '' || $email === '') {
        $err = 'Điền họ tên và email.';
    } elseif ($phone === '') {
        $err = 'Vui lòng nhập số điện thoại.';
    } else {
        try {
            $dup = $pdo->prepare('SELECT id FROM users WHERE email = :e AND id != :id LIMIT 1');
            $dup->execute(['e' => $email, 'id' => $id]);
            if ($dup->fetch()) {
                $err = 'Email đã được sử dụng.';
            } elseif (app_phone_exists_for_other_user($pdo, $phone, $id)) {
                $err = 'Số điện thoại đã được dùng cho tài khoản khác.';
            } else {
                if ($pass !== '') {
                    $pwErrs = app_password_policy_errors($pass);
                    if ($pwErrs !== []) {
                        $err = implode(' ', $pwErrs);
                    } else {
                        $hash = password_hash($pass, PASSWORD_DEFAULT);
                        $pdo->prepare(
                            'UPDATE users SET full_name=:n, email=:e, phone=:ph, password=:p, is_active=:a WHERE id=:id AND role=\'staff\''
                        )->execute(['n' => $name, 'e' => $email, 'ph' => $phone, 'p' => $hash, 'a' => $active, 'id' => $id]);
                        header('Location: list.php', true, 302);
                        exit;
                    }
                } else {
                    $pdo->prepare(
                        'UPDATE users SET full_name=:n, email=:e, phone=:ph, is_active=:a WHERE id=:id AND role=\'staff\''
                    )->execute(['n' => $name, 'e' => $email, 'ph' => $phone, 'a' => $active, 'id' => $id]);
                    header('Location: list.php', true, 302);
                    exit;
                }
            }
        } catch (Throwable) {
            $err = 'Không cập nhật được.';
        }
    }
}

function h(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$pageTitle    = 'Sửa hồ sơ nhân viên';
$activePage   = 'staff';
$topbarActions = '<a href="list.php" class="topbar-btn topbar-btn-ghost"><i class="fas fa-arrow-left"></i> Danh sách</a>';

require dirname(__DIR__, 2) . '/includes/admin_header.php';
?>

<?php if (!$row): ?>
  <div class="data-card"><p class="cell-muted">Không tìm thấy.</p><a href="list.php" class="btn btn-ghost btn-sm">← Quay lại</a></div>
<?php else: ?>
  <div class="data-card" style="max-width:520px">
    <?php if ($err): ?>
      <div class="alert alert-danger"><?= h($err) ?></div>
    <?php endif; ?>
    <form method="post" style="display:grid;gap:12px;padding:8px 4px 16px">
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Họ tên</label>
        <input class="form-control" name="full_name" required value="<?= h((string) ($_POST['full_name'] ?? $row['full_name'])) ?>" />
      </div>
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Email</label>
        <input class="form-control" type="email" name="email" required value="<?= h((string) ($_POST['email'] ?? $row['email'])) ?>" />
      </div>
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Số điện thoại</label>
        <input class="form-control" name="phone" value="<?= h((string) ($_POST['phone'] ?? $row['phone'])) ?>" />
      </div>
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Mật khẩu mới (để trống nếu giữ nguyên)</label>
        <input class="form-control" type="password" name="password" autocomplete="new-password" />
      </div>
      <label style="display:flex;align-items:center;gap:8px">
        <?php $ia = isset($_POST['full_name']) ? isset($_POST['is_active']) : ((int) $row['is_active'] === 1); ?>
        <input type="checkbox" name="is_active" value="1" <?= $ia ? 'checked' : '' ?> /> Tài khoản hoạt động
      </label>
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Cập nhật</button>
    </form>
  </div>
<?php endif; ?>

<?php require dirname(__DIR__, 2) . '/includes/admin_footer.php'; ?>
