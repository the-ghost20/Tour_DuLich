<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

$errors = [];
$successMessage = null;

$fullName = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($fullName === '') {
        $errors[] = 'Vui lòng nhập họ tên.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }
    if ($phone === '') {
        $errors[] = 'Vui lòng nhập số điện thoại.';
    }
    if (mb_strlen($password, 'UTF-8') < 8) {
        $errors[] = 'Mật khẩu phải từ 8 ký tự trở lên.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $existing = $stmt->fetch();

        if ($existing) {
            $errors[] = 'Email này đã được đăng ký. Vui lòng dùng email khác.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare(
                "INSERT INTO users (full_name, email, password, phone, role)
                 VALUES (:full_name, :email, :password, :phone, 'user')"
            );
            $insert->execute([
                'full_name' => $fullName,
                'email' => $email,
                'password' => $hashedPassword,
                'phone' => $phone,
            ]);

            header('Location: login.php');
            exit;
        }
    }
}

?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Đăng ký - Du Lịch Việt</title>
    <link rel="stylesheet" href="css/styles.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <style>
      .auth-page {
        min-height: calc(100vh - 220px);
        display: flex;
        align-items: center;
        padding: 42px 0;
      }
      .auth-page .auth-container {
        width: 100%;
        max-width: 560px;
        margin: 0 auto;
        background: linear-gradient(160deg, #ffffff 0%, #f7fbff 100%);
        border-radius: 24px;
        box-shadow: 0 24px 55px rgba(13, 36, 79, 0.16);
        padding: 30px;
        border: 1px solid rgba(33, 150, 243, 0.14);
      }
      .auth-page .auth-title { margin: 0 0 6px 0; font-size: 2rem; }
      .auth-page .auth-subtitle { margin: 0 0 20px; color: #64748b; }
      .auth-page .auth-field { display:flex; flex-direction:column; gap:8px; margin-bottom:14px; }
      .auth-page .auth-field label { font-weight: 600; color: #1f2c44; }
      .auth-page .auth-field input,
      .auth-page .auth-field select {
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid rgba(31, 44, 68, 0.16);
        outline: none;
        background: #fff;
      }
      .auth-page .auth-field input:focus,
      .auth-page .auth-field select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(33, 150, 243, .14);
      }
      .auth-page .auth-phone-group {
        display: grid;
        grid-template-columns: 170px 1fr;
        gap: 10px;
      }
      .auth-page .auth-actions { display:flex; gap:10px; align-items:center; justify-content:space-between; margin-top: 16px; flex-wrap: wrap; }
      .auth-page .auth-btn { display:inline-block; border:none; border-radius: 12px; padding: 12px 18px; cursor:pointer; background: linear-gradient(135deg, var(--primary-color), var(--accent-color)); color:#fff; font-weight: 700; box-shadow: 0 12px 22px rgba(33, 150, 243, .24); }
      .auth-page .auth-link { color: var(--primary-color); font-weight: 600; }
      .auth-page .auth-alert { background:#fff3cd; border:1px solid #ffe69c; color:#664d03; padding: 12px 14px; border-radius: 12px; margin-bottom: 14px; }
      .auth-page .auth-alert--error { background:#f8d7da; border-color:#f1aeb5; color:#842029; }
      .auth-page .auth-errors { margin:0; padding-left:18px; }
    </style>
  </head>
  <body>
    <?php
      $activePage = '';
      require __DIR__ . '/includes/header.php';
    ?>
    <div class="container auth-page">
      <div class="auth-container">
        <h1 class="auth-title">Đăng ký</h1>
        <p class="auth-subtitle">Tạo tài khoản để đặt tour nhanh và quản lý hành trình của bạn.</p>

        <?php if (!empty($errors)): ?>
          <div class="auth-alert auth-alert--error">
            <ul class="auth-errors">
              <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" action="">
          <div class="auth-field">
            <label for="full_name">Họ tên</label>
            <input id="full_name" name="full_name" type="text" value="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>" required />
          </div>
          <div class="auth-field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required />
          </div>
          <div class="auth-field">
            <label for="phone">Số điện thoại</label>
            <div class="auth-phone-group">
              <select id="phone_code" name="phone_code" aria-label="Mã quốc gia">
                <option value="+84" selected>Việt Nam (+84)</option>
                <option value="+1">Mỹ (+1)</option>
                <option value="+81">Nhật Bản (+81)</option>
                <option value="+82">Hàn Quốc (+82)</option>
                <option value="+86">Trung Quốc (+86)</option>
              </select>
              <input id="phone" name="phone" type="text" value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>" placeholder="Nhập số điện thoại" required />
            </div>
          </div>
          <div class="auth-field">
            <label for="password">Mật khẩu</label>
            <input id="password" name="password" type="password" minlength="8" required />
            <small style="color: var(--text-light);">Tối thiểu 8 ký tự</small>
          </div>

          <div class="auth-actions">
            <button class="auth-btn" type="submit">Tạo tài khoản</button>
            <div>
              Đã có tài khoản?
              <a class="auth-link" href="login.php">Đăng nhập</a>
            </div>
          </div>
        </form>
      </div>
    </div>

    <?php require __DIR__ . '/includes/footer.php'; ?>
    <script src="js/script.js"></script>
  </body>
</html>

