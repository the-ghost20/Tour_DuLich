<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Location: ' . app_staff_url('bookings/list.php'), true, 302);
exit;
