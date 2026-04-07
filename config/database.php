<?php
declare(strict_types=1);

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'tour_dulich';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS');
$dbPort = getenv('DB_PORT') ?: '3306';

if ($dbPass === false) {
    $dbPass = '';
}

$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";

$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $pdoOptions);
} catch (PDOException $firstException) {
    if ($dbUser === 'root' && $dbPass === '') {
        $pdo = new PDO($dsn, $dbUser, 'root', $pdoOptions);
    } else {
        throw $firstException;
    }
}
