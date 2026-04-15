<?php
declare(strict_types=1);

function blog_normalize_category(?string $c): string
{
    static $allowed = ['cam-nang', 'review', 'am-thuc', 'tin-tuc', 'testimonials'];
    $c = $c !== null ? trim($c) : '';
    if ($c === '') {
        return 'cam-nang';
    }
    return in_array($c, $allowed, true) ? $c : 'cam-nang';
}

function blog_slug_is_safe(string $slug): bool
{
    return $slug !== '' && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) === 1;
}

function blog_format_post_date(?string $publishedAt, ?string $createdAt): string
{
    $raw = $publishedAt !== null && trim($publishedAt) !== '' ? $publishedAt : $createdAt;
    if ($raw === null || $raw === '') {
        return '';
    }
    $t = strtotime((string) $raw);

    return $t ? date('d/m/Y', $t) : '';
}
