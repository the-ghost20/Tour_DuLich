<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

$errors = [];

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

            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            $_SESSION['register_success'] = true;

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
    <link rel="stylesheet" href="../assets/css/style.css" />
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
        padding: 14px 16px;
        border-radius: 14px;
        border: 2px solid transparent;
        background: #f3f6f9;
        outline: none;
        transition: all 0.25s ease;
        color: #1a2942;
        font-size: 0.95rem;
      }
      .auth-page .auth-field input:focus,
      .auth-page .auth-field select:focus {
        border-color: var(--primary-color);
        background: #fff;
        box-shadow: 0 4px 12px rgba(33, 150, 243, 0.1);
        transform: translateY(-2px);
      }
      .auth-page .auth-phone-group {
        display: grid;
        grid-template-columns: 170px 1fr;
        gap: 10px;
      }
      .auth-page .auth-actions { display: flex; flex-direction: column; gap: 14px; margin-top: 20px; }
      .auth-page .auth-btn { width: 100%; border:none; border-radius: 10px; padding: 12px 18px; cursor:pointer; background: #00acc1; color:#fff; font-weight: 700; font-size: 0.95rem; transition: transform 0.2s; }
      .auth-page .auth-btn:hover { background: #0097a7; transform: translateY(-1px); }
      .auth-page .auth-link { color: #00acc1; font-weight: 600; }
      .auth-page .auth-alert { background:#fff3cd; border:1px solid #ffe69c; color:#664d03; padding: 12px 14px; border-radius: 12px; margin-bottom: 14px; }
      .auth-page .auth-alert--error { background:#f8d7da; border-color:#f1aeb5; color:#842029; }
      .auth-page .auth-errors { margin:0; padding-left:18px; }
    </style>
  </head>
  <body>
    <?php
      $u = '../frontend/';
      $a = '';
      $activePage = '';
      require __DIR__ . '/../includes/header.php';
    ?>
    <div class="container auth-page">
      <div class="auth-container">
        <div class="auth-header-center">
          <div class="auth-header-icon" style="font-size: 54px; color: #00acc1; margin-bottom: 8px;">
            <i class="fas fa-user-plus" style="background: #e0f7fa; border-radius: 50%; padding: 12px; box-shadow: 0 4px 10px rgba(0,172,193,0.2);"></i>
          </div>
          <h1 class="auth-title" style="text-align: center; font-size: 1.8rem; color: #333; margin-top: 16px;">Đăng ký</h1>
          <p class="auth-subtitle" style="text-align: center; color: #666; font-size: 0.9rem;">Tạo tài khoản để đặt tour nhanh và quản lý hành trình của bạn.</p>
        </div>

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
            <label for="full_name"><i class="fas fa-user" style="color: #00acc1; margin-right: 4px;"></i> Họ tên</label>
            <input id="full_name" name="full_name" type="text" value="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>" placeholder="Nhập họ tên của bạn" required />
          </div>
          <div class="auth-field">
            <label for="email"><i class="fas fa-envelope" style="color: #00acc1; margin-right: 4px;"></i> Email</label>
            <input id="email" name="email" type="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" placeholder="Nhập email của bạn" required />
          </div>
          <div class="auth-field">
            <label for="phone"><i class="fas fa-phone" style="color: #00acc1; margin-right: 4px;"></i> Số điện thoại</label>
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
            <label for="password"><i class="fas fa-lock" style="color: #00acc1; margin-right: 4px;"></i> Mật khẩu</label>
            <input id="password" name="password" type="password" minlength="8" placeholder="Tạo mật khẩu" required />
          </div>

          <div class="auth-actions">
            <button class="auth-btn" type="submit">Tạo tài khoản</button>
            <div style="text-align: center; color: #576680; font-size: 0.92rem; margin-top: 4px;">
              Đã có tài khoản?
              <a class="auth-link" href="login.php">Đăng nhập ngay</a>
            </div>
          </div>
        </form>
      </div>
    </div>

    <?php require __DIR__ . '/../includes/footer.php'; ?>
    <script src="../assets/js/main.js"></script>
  </body>
</html>

