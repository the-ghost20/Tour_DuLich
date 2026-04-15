<?php
declare(strict_types=1);

/**
 * Gui email (tich hop PHPMailer sau). Hien tai ghi log.
 */
function app_send_mail(string $to, string $subject, string $bodyHtml): bool
{
    error_log('[mail stub] to=' . $to . ' subject=' . $subject);
    return true;
}
