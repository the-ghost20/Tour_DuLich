<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$errors = [];
$email = '';
$phone = '';
$isVerified = isset($_SESSION['forgot_password_user_id']) && (int) $_SESSION['forgot_password_user_id'] > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'verify');

    if ($action === 'verify') {
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email không hợp lệ.';
        }
        if ($phone === '') {
            $errors[] = 'Vui lòng nhập số điện thoại.';
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare(
                "SELECT id FROM users WHERE email = :email AND phone = :phone LIMIT 1"
            );
            $stmt->execute([
                'email' => $email,
                'phone' => $phone,
            ]);
            $user = $stmt->fetch();

            if (!$user) {
                $errors[] = 'Thông tin không chính xác.';
                unset($_SESSION['forgot_password_user_id']);
                $isVerified = false;
            } else {
                $_SESSION['forgot_password_user_id'] = (int) $user['id'];
                $_SESSION['forgot_password_email'] = $email;
                $_SESSION['forgot_password_phone'] = $phone;
                $isVerified = true;
            }
        }
    }

    if ($action === 'reset') {
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        $userId = (int) ($_SESSION['forgot_password_user_id'] ?? 0);
        $email = (string) ($_SESSION['forgot_password_email'] ?? '');
        $phone = (string) ($_SESSION['forgot_password_phone'] ?? '');

        if ($userId <= 0) {
            $errors[] = 'Phiên xác minh đã hết hạn. Vui lòng nhập lại thông tin.';
            $isVerified = false;
        }
        if (mb_strlen($newPassword, 'UTF-8') < 8) {
            $errors[] = 'Mật khẩu mới phải từ 8 ký tự trở lên.';
        }
        if ($confirmPassword !== $newPassword) {
            $errors[] = 'Xác nhận mật khẩu không khớp.';
        }

        if (empty($errors) && $userId > 0) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
            $update->execute([
                'password' => $hashedPassword,
                'id' => $userId,
            ]);

            unset($_SESSION['forgot_password_user_id'], $_SESSION['forgot_password_email'], $_SESSION['forgot_password_phone']);
            header('Location: login.php');
            exit;
        } else {
            $isVerified = $userId > 0;
        }
    }
}
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Quên mật khẩu - Du Lịch Việt</title>
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
      .auth-page .auth-field input {
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid rgba(31, 44, 68, 0.16);
        outline: none;
        background: #fff;
      }
      .auth-page .auth-field input:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(33, 150, 243, .14);
      }
      .auth-page .auth-actions {
        display:flex;
        gap:10px;
        align-items:center;
        justify-content:space-between;
        margin-top: 16px;
        flex-wrap: wrap;
      }
      .auth-page .auth-btn {
        display:inline-block;
        border:none;
        border-radius: 12px;
        padding: 12px 18px;
        cursor:pointer;
        background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
        color:#fff;
        font-weight: 700;
        box-shadow: 0 12px 22px rgba(33, 150, 243, .24);
      }
      .auth-page .auth-link { color: var(--primary-color); font-weight: 600; }
      .auth-page .auth-alert {
        background:#f8d7da;
        border:1px solid #f1aeb5;
        color:#842029;
        padding: 12px 14px;
        border-radius: 12px;
        margin-bottom: 14px;
      }
      .auth-page .auth-success {
        background:#e8f7ee;
        border:1px solid #bce3c7;
        color:#0d5f2a;
        padding: 12px 14px;
        border-radius: 12px;
        margin-bottom: 14px;
      }
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
        <h1 class="auth-title">Quên mật khẩu</h1>
        <p class="auth-subtitle">Xác minh email và số điện thoại để đặt lại mật khẩu.</p>

        <?php if (!empty($errors)): ?>
          <div class="auth-alert">
            <ul class="auth-errors">
              <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <?php if (!$isVerified): ?>
          <form method="post" action="">
            <input type="hidden" name="action" value="verify" />
            <div class="auth-field">
              <label for="email">Email đã đăng ký</label>
              <input id="email" name="email" type="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required />
            </div>
            <div class="auth-field">
              <label for="phone">Số điện thoại đã đăng ký</label>
              <input id="phone" name="phone" type="text" value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>" required />
            </div>

            <div class="auth-actions">
              <button class="auth-btn" type="submit">Xác minh</button>
              <a class="auth-link" href="login.php">Quay lại đăng nhập</a>
            </div>
          </form>
        <?php else: ?>
          <div class="auth-success">
            Xác minh thành công. Vui lòng nhập mật khẩu mới.
          </div>

          <form method="post" action="">
            <input type="hidden" name="action" value="reset" />
            <div class="auth-field">
              <label for="new_password">Mật khẩu mới</label>
              <input id="new_password" name="new_password" type="password" minlength="8" required />
            </div>
            <div class="auth-field">
              <label for="confirm_password">Xác nhận mật khẩu mới</label>
              <input id="confirm_password" name="confirm_password" type="password" minlength="8" required />
            </div>

            <div class="auth-actions">
              <button class="auth-btn" type="submit">Đổi mật khẩu</button>
              <a class="auth-link" href="login.php">Quay lại đăng nhập</a>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <?php require __DIR__ . '/includes/footer.php'; ?>
    <script src="js/script.js"></script>
  </body>
</html>
