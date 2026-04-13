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
