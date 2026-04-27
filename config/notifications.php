<?php
declare(strict_types=1);

// Email configuration
const NOTIFY_EMAIL_FROM = 'no-reply@tourdulich.local';
const NOTIFY_EMAIL_NAME = 'Du Lịch Việt';

// SMS configuration
const NOTIFY_SMS_PROVIDER = '';
const NOTIFY_TWILIO_ACCOUNT_SID = '';
const NOTIFY_TWILIO_AUTH_TOKEN = '';
const NOTIFY_TWILIO_FROM_NUMBER = '';

function send_email_notification(string $to, string $subject, string $htmlBody, string $plainText = null): bool
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log("[notify] Invalid email address: {$to}");
        return false;
    }

    $plainText = $plainText ?? strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
    $subject = mb_encode_mimeheader($subject, 'UTF-8');
    $boundary = md5((string) microtime(true));

    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
    $headers[] = 'From: ' . mb_encode_mimeheader(NOTIFY_EMAIL_NAME, 'UTF-8') . ' <' . NOTIFY_EMAIL_FROM . '>';
    $headers[] = 'Reply-To: ' . NOTIFY_EMAIL_FROM;
    $headers[] = 'X-Mailer: PHP/' . phpversion();

    $body = "--{$boundary}\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= trim($plainText) . "\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= trim($htmlBody) . "\r\n";
    $body .= "--{$boundary}--";

    $success = @mail($to, $subject, $body, implode("\r\n", $headers));
    if (!$success) {
        error_log("[notify] Failed to send email to {$to}");
    }

    return $success;
}

function send_sms_notification(string $phone, string $message): bool
{
    $normalized = normalize_sms_phone($phone);
    if ($normalized === '') {
        error_log("[notify] Invalid SMS phone: {$phone}");
        return false;
    }

    if (NOTIFY_SMS_PROVIDER === 'twilio' && NOTIFY_TWILIO_ACCOUNT_SID !== '' && NOTIFY_TWILIO_AUTH_TOKEN !== '' && NOTIFY_TWILIO_FROM_NUMBER !== '') {
        $endpoint = 'https://api.twilio.com/2010-04-01/Accounts/' . NOTIFY_TWILIO_ACCOUNT_SID . '/Messages.json';
        $payload = http_build_query([
            'To' => $normalized,
            'From' => NOTIFY_TWILIO_FROM_NUMBER,
            'Body' => $message,
        ]);

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, NOTIFY_TWILIO_ACCOUNT_SID . ':' . NOTIFY_TWILIO_AUTH_TOKEN);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode >= 400) {
            error_log("[notify] Twilio SMS failed to {$normalized}: HTTP {$httpCode} - {$curlError} - {$response}");
            return false;
        }

        return true;
    }

    error_log("[notify] SMS provider not configured, message to {$normalized}: {$message}");
    return false;
}

function normalize_sms_phone(string $phone): string
{
    $clean = preg_replace('/[^+0-9]/', '', $phone);
    if ($clean === '') {
        return '';
    }

    if (strpos($clean, '+') !== 0) {
        $clean = '+' . ltrim($clean, '0');
    }

    return $clean;
}

function send_user_welcome_notification(string $fullName, string $email, string $phone): void
{
    $subject = 'Chào mừng đến với Du Lịch Việt';
    $html = "<p>Xin chào <strong>{$fullName}</strong>,</p>" .
            '<p>Cảm ơn bạn đã đăng ký tài khoản tại Du Lịch Việt. Giờ đây bạn có thể đặt tour, lưu tour yêu thích và quản lý lịch trình dễ dàng.</p>' .
            '<p>Chúc bạn có những chuyến đi thật vui và nhiều trải nghiệm.</p>' .
            '<p>Trân trọng,<br>Đội ngũ Du Lịch Việt</p>';
    $text = "Xin chào {$fullName},\n\n" .
            'Cảm ơn bạn đã đăng ký tài khoản tại Du Lịch Việt. Giờ đây bạn có thể đặt tour, lưu tour yêu thích và quản lý lịch trình dễ dàng.\n\n' .
            'Trân trọng,\nĐội ngũ Du Lịch Việt';

    send_email_notification($email, $subject, $html, $text);
    if ($phone !== '') {
        send_sms_notification($phone, "Chào mừng {$fullName} đến với Du Lịch Việt! Cảm ơn bạn đã đăng ký.");
    }
}

function send_booking_notification(string $fullName, string $email, string $phone, int $bookingId, string $tourName, int $adults, int $children, float $totalAmount): void
{
    $subject = 'Xác nhận đặt tour Du Lịch Việt';
    $html = "<p>Xin chào <strong>{$fullName}</strong>,</p>" .
            "<p>Đơn đặt tour của bạn đã được ghi nhận với mã <strong>#{$bookingId}</strong>.</p>" .
            '<ul>' .
            "<li><strong>Tour:</strong> {$tourName}</li>" .
            "<li><strong>Người lớn:</strong> {$adults}</li>" .
            "<li><strong>Trẻ em:</strong> {$children}</li>" .
            "<li><strong>Tổng tiền:</strong> " . number_format($totalAmount, 0, ',', '.') . ' đ</li>' .
            '</ul>' .
            '<p>Chúng tôi sẽ liên hệ bạn sớm để xác nhận chi tiết và hướng dẫn thanh toán.</p>' .
            '<p>Trân trọng,<br>Du Lịch Việt</p>';
    $text = "Xin chào {$fullName},\n\n" .
            "Đơn đặt tour của bạn đã được ghi nhận với mã #{$bookingId}.\n" .
            "Tour: {$tourName}\n" .
            "Người lớn: {$adults}\n" .
            "Trẻ em: {$children}\n" .
            "Tổng tiền: " . number_format($totalAmount, 0, ',', '.') . " đ\n\n" .
            'Chúng tôi sẽ liên hệ bạn sớm để xác nhận chi tiết và hướng dẫn thanh toán.\n\n' .
            'Trân trọng,\nDu Lịch Việt';

    send_email_notification($email, $subject, $html, $text);
    if ($phone !== '') {
        send_sms_notification($phone, "Đặt tour thành công #{$bookingId}: {$tourName} - Tổng " . number_format($totalAmount, 0, ',', '.') . " đ.");
    }
}

function send_password_reset_notification(string $fullName, string $email, string $phone): void
{
    $subject = 'Mật khẩu của bạn đã được thay đổi';
    $html = "<p>Xin chào <strong>{$fullName}</strong>,</p>" .
            '<p>Mật khẩu đăng nhập của bạn tại Du Lịch Việt đã được cập nhật thành công.</p>' .
            '<p>Nếu bạn không thực hiện yêu cầu này, vui lòng liên hệ với chúng tôi ngay.</p>' .
            '<p>Trân trọng,<br>Du Lịch Việt</p>';
    $text = "Xin chào {$fullName},\n\n" .
            'Mật khẩu đăng nhập của bạn tại Du Lịch Việt đã được cập nhật thành công.\n\n' .
            'Nếu bạn không thực hiện yêu cầu này, vui lòng liên hệ với chúng tôi ngay.\n\n' .
            'Trân trọng,\nDu Lịch Việt';

    send_email_notification($email, $subject, $html, $text);
    if ($phone !== '') {
        send_sms_notification($phone, 'Mật khẩu Du Lịch Việt của bạn đã được thay đổi thành công.');
    }
}
