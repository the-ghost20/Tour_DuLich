<?php
declare(strict_types=1);

/**
 * Cấu hình chuyển khoản VietQR — chỉnh theo tài khoản thật của doanh nghiệp.
 *
 * - BIN: 6 chữ số mã ngân hàng (xem https://vietqr.io hoặc hỗ trợ NH).
 * - Số TK: chỉ số, không khoảng trắng (hoặc có khoảng trắng sẽ được bỏ khi tạo QR).
 * - Tên chủ TK: nên IN HOA, không dấu để hiển thị đúng trên app ngân hàng.
 */
$paymentBankDisplayName = 'Ngân hàng TMCP Ngoại Thương Việt Nam (Vietcombank)';
$paymentBankBin         = '970436';
$paymentAccountNumber   = '0123456789';
$paymentAccountHolder   = 'CONG TY DU LICH VIET NAM';

/**
 * Nội dung chuyển khoản gắn vào QR (ngắn, ASCII).
 */
function payment_transfer_memo(int $bookingId): string
{
    return 'DON' . $bookingId;
}

function payment_bank_config_ready(string $bankBin, string $accountNo): bool
{
    $accountNo = preg_replace('/\s+/', '', $accountNo);
    return $bankBin !== '' && $accountNo !== '' && preg_match('/^\d{6}$/', $bankBin) === 1;
}

/**
 * URL ảnh QR VietQR (img.vietqr.io).
 */
function payment_vietqr_image_url(
    string $bankBin,
    string $accountNo,
    int $amountVnd,
    string $addInfo,
    string $accountName
): string {
    $accountNo = preg_replace('/\s+/', '', $accountNo);
    $template  = 'compact2';
    $base      = sprintf(
        'https://img.vietqr.io/image/%s-%s-%s.png',
        $bankBin,
        $accountNo,
        $template
    );

    return $base . '?' . http_build_query(
        [
            'amount'      => $amountVnd,
            'addInfo'     => $addInfo,
            'accountName' => $accountName,
        ],
        '',
        '&',
        PHP_QUERY_RFC3986
    );
}
