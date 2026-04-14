<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$errors = [];
$success = null;

$stmt = $pdo->prepare(
    'SELECT id, full_name, email, phone, role FROM users WHERE id = :id LIMIT 1'
);
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}

if ((string) $user['role'] === 'admin') {
    header('Location: ../admin/index.php');
    exit;
}
if ((string) $user['role'] === 'staff') {
    header('Location: ../staff/index.php');
    exit;
}

$fullName = (string) $user['full_name'];
$email = (string) $user['email'];
$phone = (string) $user['phone'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'profile');

    if ($action === 'profile') {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $newEmail = trim((string) ($_POST['email'] ?? ''));

        if ($fullName === '') {
            $errors[] = 'Vui lòng nhập họ tên.';
        }
        if ($phone === '') {
            $errors[] = 'Vui lòng nhập số điện thoại.';
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
        if ($phone !== '' && app_phone_exists_for_other_user($pdo, $phone, $userId)) {
            $errors[] = 'Số điện thoại này đã được dùng cho tài khoản khác.';
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
            $success = 'Đã cập nhật thông tin cá nhân.';
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
        $errors = array_merge($errors, app_password_policy_errors($newPass));
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

?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Hồ sơ cá nhân - Du Lịch Việt</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  </head>
  <body>
    <?php
      $activePage = '';
      require __DIR__ . '/../includes/header.php';
    ?>

    <section class="profile-hero">
      <div class="container">
        <h1><i class="fas fa-user-circle"></i> Hồ sơ cá nhân</h1>
        <p>Cập nhật thông tin liên hệ và bảo mật tài khoản.</p>
      </div>
    </section>

    <div class="container profile-layout">
      <?php if ($success): ?>
        <div class="profile-flash profile-flash--ok"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
      <?php if (!empty($errors)): ?>
        <div class="profile-flash profile-flash--err">
          <ul>
            <?php foreach ($errors as $err): ?>
              <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div class="profile-grid">
        <section class="profile-card">
          <h2>Thông tin liên hệ</h2>
          <form method="post" class="profile-form">
            <input type="hidden" name="action" value="profile" />
            <div class="profile-field">
              <label for="full_name">Họ và tên</label>
              <input id="full_name" name="full_name" type="text" required
                value="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div class="profile-field">
              <label for="email">Email</label>
              <input id="email" name="email" type="email" required
                value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div class="profile-field">
              <label for="phone">Số điện thoại</label>
              <input id="phone" name="phone" type="text" required
                value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <button type="submit" class="profile-btn">Lưu thay đổi</button>
          </form>
        </section>

        <section class="profile-card">
          <h2>Đổi mật khẩu</h2>
          <form method="post" class="profile-form">
            <input type="hidden" name="action" value="password" />
            <div class="profile-field">
              <label for="current_password">Mật khẩu hiện tại</label>
              <input id="current_password" name="current_password" type="password" autocomplete="current-password" />
            </div>
            <div class="profile-field">
              <label for="new_password">Mật khẩu mới</label>
              <input id="new_password" name="new_password" type="password" minlength="8" maxlength="128" autocomplete="new-password" />
            </div>
            <div class="profile-field">
              <label for="confirm_password">Xác nhận mật khẩu mới</label>
              <input id="confirm_password" name="confirm_password" type="password" minlength="8" maxlength="128" autocomplete="new-password" />
            </div>
            <button type="submit" class="profile-btn profile-btn--secondary">Cập nhật mật khẩu</button>
          </form>
        </section>
      </div>
    </div>

    <?php require __DIR__ . '/../includes/footer.php'; ?>
    <script src="../assets/js/main.js"></script>
  </body>
</html>
