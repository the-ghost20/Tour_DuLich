<?php
declare(strict_types=1);

/**
 * Cấu hình MySQL — chỉnh theo MAMP / XAMPP của bạn.
 *
 * MAMP (macOS, mặc định):
 *   Apache: thường http://localhost:8888
 *   MySQL port: 8889 (Xem MAMP → Preferences → Ports)
 *   User: root / Password: root
 *
 * XAMPP:
 *   MySQL port: 3306, user root, password thường để trống
 */

$dbHost = '127.0.0.1';
$dbName = 'tour_dulich';
$dbUser = 'root';
$dbPass = 'root';
$dbPort = '8889';
