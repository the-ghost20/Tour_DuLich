<?php
declare(strict_types=1);

$query = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== ''
    ? '?' . $_SERVER['QUERY_STRING']
    : '';
header('Location: blog_detail.php' . $query, true, 301);
exit;
