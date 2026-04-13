<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$errors = [];
$email = '';

$registerSuccess = false;
if (!empty($_SESSION['register_success'])) {
    $registerSuccess = true;
    unset($_SESSION['register_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')), 'UTF-8');
    $password = (string) ($_POST['password'] ?? '');
    $password = trim($password);

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }
    if ($password === '') {
        $errors[] = 'Vui lòng nhập mật khẩu.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'SELECT id, full_name, password, role, is_active FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, (string) $user['password'])) {
            $errors[] = 'Email hoặc mật khẩu không đúng.';
        } elseif ((int) ($user['is_active'] ?? 1) !== 1) {
            $errors[] = 'Tài khoản đã bị tạm khóa. Liên hệ quản trị viên.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['role'] = (string) $user['role'];
            $_SESSION['full_name'] = (string) $user['full_name'];

            if ($_SESSION['role'] === 'admin') {
                header('Location: ../admin/index.php');
                exit;
            }
            if ($_SESSION['role'] === 'staff') {
                header('Location: ../staff/index.php');
                exit;
            }

            header('Location: ../frontend/index.php');
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
    <title>Đăng nhập - Du Lịch Việt</title>
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
        max-width: 540px;
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
      .auth-page .auth-actions { display:flex; gap:10px; align-items:center; justify-content:space-between; margin-top: 16px; flex-wrap: wrap; }
      .auth-page .auth-btn { display:inline-block; border:none; border-radius: 12px; padding: 12px 18px; cursor:pointer; background: linear-gradient(135deg, var(--primary-color), var(--accent-color)); color:#fff; font-weight: 700; box-shadow: 0 12px 22px rgba(33, 150, 243, .24); }
      .auth-page .auth-link { color: var(--primary-color); font-weight: 600; }
      .auth-page .auth-alert { background:#f8d7da; border:1px solid #f1aeb5; color:#842029; padding: 12px 14px; border-radius: 12px; margin-bottom: 14px; }
      .auth-page .auth-alert--success {
        background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46;
        display: flex; align-items: flex-start; gap: 10px; font-weight: 600;
      }
      .auth-page .auth-alert--success i { margin-top: 2px; color: #059669; }
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
        <h1 class="auth-title">Đăng nhập</h1>
        <p class="auth-subtitle">Đăng nhập để tiếp tục đặt tour và theo dõi các chuyến đi của bạn.</p>

        <?php if ($registerSuccess): ?>
          <div class="auth-alert auth-alert--success" role="status">
            <i class="fas fa-check-circle"></i>
            <span>Đăng ký tài khoản thành công! Vui lòng đăng nhập bằng email và mật khẩu bạn vừa tạo.</span>
          </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
          <div class="auth-alert">
            <ul class="auth-errors">
              <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" action="">
          <div class="auth-field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required />
          </div>
          <div class="auth-field">
            <label for="password">Mật khẩu</label>
            <input id="password" name="password" type="password" minlength="8" required />
          </div>

          <div class="auth-actions">
            <button class="auth-btn" type="submit">Đăng nhập</button>
            <div>
              Chưa có tài khoản?
              <a class="auth-link" href="register.php">Đăng ký</a>
              <br />
              <a class="auth-link" href="forgot_password.php">Quên mật khẩu?</a>
            </div>
          </div>
        </form>
      </div>
    </div>

    <?php require __DIR__ . '/../includes/footer.php'; ?>
    <script src="../assets/js/main.js"></script>
  </body>
</html>

