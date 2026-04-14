<?php
declare(strict_types=1);

/**
 * Đặt lại tài khoản demo (id 1–6): email Gmail + mật khẩu "password".
 *
 * Cách dùng (một lần):
 * 1. Tạo file rỗng: database/.enable_demo_setup (cùng thư mục với sample_data.sql)
 * 2. Mở trình duyệt: .../auth/demo_account_setup.php (không mở trực tiếp file trong dev-scripts).
 * 3. Bấm nút xác nhận. File .enable_demo_setup sẽ bị xóa sau khi thành công.
 *
 * Nếu không thấy trang (404): file .enable_demo_setup chưa tồn tại hoặc sai đường dẫn project.
 */
$projectRoot = dirname(__DIR__, 2);
$enableFile = $projectRoot . '/database/.enable_demo_setup';
if (!is_file($enableFile)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Không tìm thấy. Tạo file rỗng `database/.enable_demo_setup` rồi tải lại trang này.\n";
    exit;
}

require_once $projectRoot . '/includes/db.php';

$accounts = [
    1 => 'admin.dulichviet@gmail.com',
    2 => 'staff.dulichviet@gmail.com',
    3 => 'user1.dulichviet@gmail.com',
    4 => 'user2.dulichviet@gmail.com',
    5 => 'user3.dulichviet@gmail.com',
    6 => 'user4.dulichviet@gmail.com',
];

$rows = [];
try {
    $st = $pdo->query('SELECT id, email, LEFT(password, 30) AS pw_prefix FROM users WHERE id BETWEEN 1 AND 6 ORDER BY id');
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $rows = [];
}

$done = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    try {
        $hash = password_hash('password', PASSWORD_DEFAULT);
        $upd = $pdo->prepare('UPDATE users SET email = :e, password = :p WHERE id = :id');
        foreach ($accounts as $id => $email) {
            $upd->execute(['e' => $email, 'p' => $hash, 'id' => $id]);
        }
        if (is_file($enableFile)) {
            @unlink($enableFile);
        }
        $done = true;
    } catch (Throwable $e) {
        $error = 'Lỗi CSDL: ' . $e->getMessage();
    }
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Thiết lập tài khoản demo</title>
  <style>
    body { font-family: system-ui, sans-serif; max-width: 640px; margin: 2rem auto; padding: 0 1rem; line-height: 1.5; }
    code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; }
    table { border-collapse: collapse; width: 100%; margin: 1rem 0; font-size: 0.9rem; }
    th, td { border: 1px solid #e2e8f0; padding: 8px; text-align: left; }
    .ok { color: #047857; }
    .err { color: #b91c1c; }
    button { padding: 10px 16px; font-weight: 600; cursor: pointer; }
  </style>
</head>
<body>
  <h1>Thiết lập tài khoản demo</h1>
  <p>Database: <code><?= htmlspecialchars((string) ($dbName ?? ''), ENT_QUOTES, 'UTF-8') ?></code>
     — Host: <code><?= htmlspecialchars((string) ($dbHost ?? ''), ENT_QUOTES, 'UTF-8') ?></code>
     — Port: <code><?= htmlspecialchars((string) ($dbPort ?? ''), ENT_QUOTES, 'UTF-8') ?></code></p>

  <?php if ($done): ?>
    <p class="ok"><strong>Đã cập nhật.</strong> Đăng nhập:</p>
    <ul>
      <li>Admin: <code>admin.dulichviet@gmail.com</code> / <code>password</code></li>
    </ul>
    <p>File <code>database/.enable_demo_setup</code> đã được gỡ để khóa lại trang này.</p>
    <p><a href="<?= htmlspecialchars(app_url('auth/login.php'), ENT_QUOTES, 'UTF-8') ?>">→ Đăng nhập</a></p>
  <?php else: ?>
    <?php if ($error): ?>
      <p class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <p>Trạng thái hiện tại (user id 1–6):</p>
    <?php if ($rows === []): ?>
      <p class="err">Không đọc được bảng <code>users</code> hoặc chưa có dòng id 1–6. Hãy import <code>database/tour_management.sql</code> và <code>database/sample_data.sql</code>.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>id</th><th>email trong DB</th><th>password (30 ký tự đầu)</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= (int) $r['id'] ?></td>
              <td><?= htmlspecialchars((string) $r['email'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><code><?= htmlspecialchars((string) $r['pw_prefix'], ENT_QUOTES, 'UTF-8') ?>…</code></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <p>Bấm nút bên dưới để ghi đè email + mật khẩu <code>password</code> (hash do PHP tạo) cho id 1–6.</p>
    <form method="post">
      <input type="hidden" name="confirm" value="yes" />
      <button type="submit">Đặt lại tài khoản demo</button>
    </form>
  <?php endif; ?>
</body>
</html>
