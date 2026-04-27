<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)) {
    header('Location: ' . app_url('auth/login.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . app_admin_url('users/list.php'));
    exit;
}

$uid = (int) ($_POST['user_id'] ?? 0);
$redirect = trim((string) ($_POST['redirect'] ?? ''));
$adminPrefix = app_project_web_base() . '/admin/';
if ($redirect === '' || !str_starts_with($redirect, $adminPrefix)) {
    $redirect = app_admin_url('users/list.php');
}

if ($uid > 0) {
    try {
        $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = :id AND role = 'user' LIMIT 1");
        $stmt->execute(['id' => $uid]);
        $cur = $stmt->fetchColumn();
        if ($cur !== false) {
            $new = (int) $cur === 1 ? 0 : 1;
            $pdo->prepare('UPDATE users SET is_active = :a WHERE id = :id AND role = \'user\'')->execute(['a' => $new, 'id' => $uid]);
        }
    } catch (Throwable) {
        // ignore
    }
}

header('Location: ' . $redirect, true, 302);
exit;
