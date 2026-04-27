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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = (int) ($_POST['user_id'] ?? 0);
    if ($uid > 0 && $uid !== (int) $_SESSION['user_id']) {
        try {
            $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = :id AND role = 'staff'")->execute(['id' => $uid]);
        } catch (Throwable) {
            // ignore
        }
    }
}

header('Location: ' . app_admin_url('staff/list.php'), true, 302);
exit;
