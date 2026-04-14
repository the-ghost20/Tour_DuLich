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

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['full_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $pass  = (string) ($_POST['password'] ?? '');

    if ($name === '' || $email === '' || $pass === '') {
        $err = 'Điền họ tên, email và mật khẩu.';
    } elseif ($phone === '') {
        $err = 'Vui lòng nhập số điện thoại.';
    } elseif ($errs = app_password_policy_errors($pass)) {
        $err = implode(' ', $errs);
    } else {
        try {
            $dup = $pdo->prepare('SELECT id FROM users WHERE email = :e LIMIT 1');
            $dup->execute(['e' => $email]);
            if ($dup->fetch()) {
                $err = 'Email đã được sử dụng.';
            } elseif (app_phone_exists_for_other_user($pdo, $phone, null)) {
                $err = 'Số điện thoại đã được dùng cho tài khoản khác.';
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $pdo->prepare(
                    "INSERT INTO users (full_name, email, password, phone, role, is_active)
                     VALUES (:n,:e,:p,:ph,'staff',1)"
                )->execute(['n' => $name, 'e' => $email, 'p' => $hash, 'ph' => $phone]);
                header('Location: list.php', true, 302);
                exit;
            }
        } catch (Throwable) {
            $err = 'Không tạo được tài khoản.';
        }
    }
}

function h(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$pageTitle    = 'Thêm hồ sơ nhân viên';
$activePage   = 'staff';
$topbarActions = '<a href="list.php" class="topbar-btn topbar-btn-ghost"><i class="fas fa-arrow-left"></i> Danh sách</a>';

require dirname(__DIR__, 2) . '/includes/admin_header.php';
?>

<div class="data-card" style="max-width:520px">
  <?php if ($err): ?>
    <div class="alert alert-danger"><?= h($err) ?></div>
  <?php endif; ?>
  <form method="post" style="display:grid;gap:12px;padding:8px 4px 16px">
    <div>
      <label class="cell-muted" style="font-size:0.8rem">Họ tên</label>
      <input class="form-control" name="full_name" required value="<?= h((string) ($_POST['full_name'] ?? '')) ?>" />
    </div>
    <div>
      <label class="cell-muted" style="font-size:0.8rem">Email (đăng nhập)</label>
      <input class="form-control" type="email" name="email" required value="<?= h((string) ($_POST['email'] ?? '')) ?>" />
    </div>
    <div>
      <label class="cell-muted" style="font-size:0.8rem">Số điện thoại</label>
      <input class="form-control" name="phone" required value="<?= h((string) ($_POST['phone'] ?? '')) ?>" />
    </div>
    <div>
      <label class="cell-muted" style="font-size:0.8rem">Mật khẩu</label>
      <input class="form-control" type="password" name="password" required minlength="8" maxlength="128" autocomplete="new-password" />
    </div>
    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Tạo tài khoản</button>
  </form>
</div>

<?php require dirname(__DIR__, 2) . '/includes/admin_footer.php'; ?>
