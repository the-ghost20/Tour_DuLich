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

$userId = (int) $_SESSION['user_id'];
$errors = [];
$success = null;

$stmt = $pdo->prepare(
    'SELECT id, full_name, email, phone, role, is_active, created_at FROM users WHERE id = :id LIMIT 1'
);
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: ' . app_url('auth/login.php'));
    exit;
}

if ((int) $user['is_active'] !== 1) {
    session_destroy();
    header('Location: ' . app_url('auth/login.php'));
    exit;
}

$fullName = (string) $user['full_name'];
$email    = (string) $user['email'];
$phone    = (string) $user['phone'];
$role     = (string) $user['role'];
$created  = $user['created_at'] ? date('d/m/Y', strtotime((string) $user['created_at'])) : '—';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'profile');

    if ($action === 'profile') {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $phone    = trim((string) ($_POST['phone'] ?? ''));
        $newEmail = trim((string) ($_POST['email'] ?? ''));

        if ($fullName === '') {
            $errors[] = 'Vui lòng nhập họ và tên.';
        }
        if ($newEmail === '' || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email không hợp lệ.';
        }

        if ($newEmail !== $email) {
            $chk = $pdo->prepare('SELECT id FROM users WHERE email = :e AND id <> :id LIMIT 1');
            $chk->execute(['e' => $newEmail, 'id' => $userId]);
            if ($chk->fetch()) {
                $errors[] = 'Email này đã được sử dụng bởi tài khoản khác.';
            }
        }

        if (empty($errors)) {
            $upd = $pdo->prepare(
                'UPDATE users SET full_name = :fn, phone = :ph, email = :em WHERE id = :id'
            );
            $upd->execute([
                'fn' => $fullName,
                'ph' => $phone,
                'em' => $newEmail,
                'id' => $userId,
            ]);
            $_SESSION['full_name'] = $fullName;
            $email = $newEmail;
            $success = 'Đã cập nhật thông tin hồ sơ.';
        }
    }

    if ($action === 'password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $newPass = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        $stmtPw = $pdo->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
        $stmtPw->execute(['id' => $userId]);
        $rowPw = $stmtPw->fetch();

        if (!$rowPw || !password_verify($current, (string) $rowPw['password'])) {
            $errors[] = 'Mật khẩu hiện tại không đúng.';
        }
        if (mb_strlen($newPass, 'UTF-8') < 6) {
            $errors[] = 'Mật khẩu mới cần ít nhất 6 ký tự.';
        }
        if ($newPass !== $confirm) {
            $errors[] = 'Xác nhận mật khẩu mới không khớp.';
        }

        if (empty($errors)) {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE users SET password = :p WHERE id = :id')->execute([
                'p' => $hash,
                'id' => $userId,
            ]);
            $success = 'Đã đổi mật khẩu thành công.';
        }
    }
}

function h(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$roleLabel = $role === 'admin' ? 'Quản trị viên' : 'Nhân viên';

$pageTitle    = 'Hồ sơ cá nhân';
$pageSubtitle = 'Xem và chỉnh sửa thông tin tài khoản của bạn';
$activePage   = 'profile';

require dirname(__DIR__, 1) . '/includes/staff_header.php';
?>

<?php if ($success): ?>
  <div class="alert alert-success" style="margin-bottom:16px"><?= h($success) ?></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
  <div class="alert alert-danger" style="margin-bottom:16px">
    <ul style="margin:0;padding-left:1.2rem">
      <?php foreach ($errors as $err): ?>
        <li><?= h($err) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="data-card" style="margin-bottom:16px">
  <div class="data-card-header">
    <h2 class="data-card-title"><i class="fas fa-id-card" style="margin-right:8px;opacity:0.85"></i>Tóm tắt tài khoản</h2>
  </div>
  <div style="display:grid;gap:12px;padding:4px 4px 12px">
    <div style="display:flex;flex-wrap:wrap;gap:16px;align-items:center">
      <div>
        <div class="cell-muted" style="font-size:0.75rem">Vai trò</div>
        <div><span class="badge <?= $role === 'admin' ? 'badge-info' : 'badge-success' ?>"><?= h($roleLabel) ?></span></div>
      </div>
      <div>
        <div class="cell-muted" style="font-size:0.75rem">Ngày tham gia</div>
        <div><?= h($created) ?></div>
      </div>
    </div>
  </div>
</div>

<div class="form-grid">
  <div class="data-card">
    <div class="data-card-header">
      <h2 class="data-card-title"><i class="fas fa-user-pen" style="margin-right:8px;opacity:0.85"></i>Thông tin liên hệ</h2>
      <p class="data-card-sub">Họ tên, email đăng nhập và số điện thoại</p>
    </div>
    <form method="post" style="display:grid;gap:12px;padding:8px 4px 16px">
      <input type="hidden" name="action" value="profile" />
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Họ và tên</label>
        <input class="form-control" name="full_name" required value="<?= h($fullName) ?>" autocomplete="name" />
      </div>
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Email đăng nhập</label>
        <input class="form-control" type="email" name="email" required value="<?= h($email) ?>" autocomplete="email" />
      </div>
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Số điện thoại</label>
        <input class="form-control" name="phone" value="<?= h($phone) ?>" autocomplete="tel" />
      </div>
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-floppy-disk"></i> Lưu thông tin</button>
    </form>
  </div>

  <div class="data-card">
    <div class="data-card-header">
      <h2 class="data-card-title"><i class="fas fa-key" style="margin-right:8px;opacity:0.85"></i>Đổi mật khẩu</h2>
      <p class="data-card-sub">Nhập mật khẩu hiện tại và mật khẩu mới</p>
    </div>
    <form method="post" style="display:grid;gap:12px;padding:8px 4px 16px">
      <input type="hidden" name="action" value="password" />
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Mật khẩu hiện tại</label>
        <input class="form-control" type="password" name="current_password" required autocomplete="current-password" />
      </div>
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Mật khẩu mới (tối thiểu 6 ký tự)</label>
        <input class="form-control" type="password" name="new_password" required minlength="6" autocomplete="new-password" />
      </div>
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Xác nhận mật khẩu mới</label>
        <input class="form-control" type="password" name="confirm_password" required minlength="6" autocomplete="new-password" />
      </div>
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-lock"></i> Cập nhật mật khẩu</button>
    </form>
  </div>
</div>

<?php require dirname(__DIR__, 1) . '/includes/staff_footer.php';
