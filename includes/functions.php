<?php
declare(strict_types=1);

/**
 * Web path prefix to project root (no trailing slash), e.g. /Tour_DuLich */
function app_project_web_base(): string
{
    $sn = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $dir = rtrim(dirname($sn), '/');
    if ($dir === '' || $dir === '.') {
        return '';
    }
    $base = preg_replace('#(?:/admin|/staff|/auth|/frontend)(?:/.*)?$#', '', $dir);
    return $base === $dir ? $dir : (string) $base;
}

function app_url(string $path): string
{
    $b = app_project_web_base();
    $path = ltrim($path, '/');
    return ($b === '' ? '' : $b) . '/' . $path;
}

function app_admin_url(string $path): string
{
    return app_url('admin/' . ltrim($path, '/'));
}

function app_staff_url(string $path): string
{
    return app_url('staff/' . ltrim($path, '/'));
}

function app_asset_url(string $path): string
{
    return app_url('assets/' . ltrim($path, '/'));
}

/** Slug ASCII cho danh mục / blog (tiếng Việt không dấu). */
function vn_slug(string $text): string
{
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = strtr($text, [
        'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a', 'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a',
        'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
        'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
        'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o',
        'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
        'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
        'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
        'đ' => 'd',
    ]);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim((string) $text, '-');
    return $text !== '' ? $text : 'muc-' . time();
}

/**
 * Kiểm tra độ dài mật khẩu: tối thiểu 8, tối đa 128 ký tự (không rỗng).
 *
 * @return list<string> Danh sách thông báo lỗi (rỗng nếu hợp lệ).
 */
function app_password_policy_errors(string $password): array
{
    $errors = [];
    $len = mb_strlen($password, 'UTF-8');
    if ($len < 8) {
        $errors[] = 'Mật khẩu phải có độ dài tối thiểu 8 ký tự (khuyến nghị 8–12 ký tự).';
    }
    if ($len > 128) {
        $errors[] = 'Mật khẩu không được vượt quá 128 ký tự.';
    }

    return $errors;
}

function app_normalize_phone_digits(string $phone): string
{
    return preg_replace('/\D+/', '', $phone);
}

/** Hai chuỗi SĐT có cùng một số (hỗ trợ 0xxxx / +84). */
function app_phones_equivalent(string $a, string $b): bool
{
    $da = app_normalize_phone_digits($a);
    $db = app_normalize_phone_digits($b);
    if ($da === '' || $db === '') {
        return false;
    }
    if (str_starts_with($da, '84') && strlen($da) >= 10) {
        $da = '0' . substr($da, 2);
    }
    if (str_starts_with($db, '84') && strlen($db) >= 10) {
        $db = '0' . substr($db, 2);
    }

    return $da === $db;
}

/**
 * Số điện thoại đã được tài khoản khác dùng (so khớp theo chữ số, không phân biệt định dạng).
 */
function app_phone_exists_for_other_user(PDO $pdo, string $phone, ?int $exceptUserId = null): bool
{
    $phone = trim($phone);
    if ($phone === '') {
        return false;
    }
    $stmt = $pdo->query('SELECT id, phone FROM users');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $uid = (int) $row['id'];
        if ($exceptUserId !== null && $uid === $exceptUserId) {
            continue;
        }
        if (app_phones_equivalent($phone, (string) $row['phone'])) {
            return true;
        }
    }

    return false;
}
