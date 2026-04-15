<?php
declare(strict_types=1);

require_once __DIR__ . '/booking_holidays.php';

function booking_subtotal(float $pricePerPerson, int $adults, int $children): float
{
    return round($pricePerPerson * ($adults + $children * 0.5), 2);
}

/**
 * @return array{holiday_percent:int,holiday_amount:float,subtotal:float,holiday_label:string}
 */
function booking_holiday_addon(float $baseSubtotal, string $departureYmd): array
{
    $holidayPct = booking_holiday_surcharge_percent($departureYmd);
    $holidayAmount = round($baseSubtotal * ($holidayPct / 100.0), 2);

    return [
        'holiday_percent' => $holidayPct,
        'holiday_amount'  => $holidayAmount,
        'subtotal'        => round($baseSubtotal + $holidayAmount, 2),
        'holiday_label'   => booking_holiday_label($holidayPct),
    ];
}

function booking_normalize_coupon_code(?string $code): string
{
    return strtoupper(preg_replace('/\s+/', '', trim((string) $code)));
}

function booking_coupon_is_valid_now(array $c): ?string
{
    if ((int) ($c['is_active'] ?? 0) !== 1) {
        return 'Mã khuyến mãi không còn hiệu lực.';
    }
    $today = new DateTimeImmutable('today');
    if (!empty($c['starts_at'])) {
        $start = new DateTimeImmutable(substr((string) $c['starts_at'], 0, 10));
        if ($today < $start) {
            return 'Mã khuyến mãi chưa có hiệu lực.';
        }
    }
    if (!empty($c['expires_at'])) {
        $end = new DateTimeImmutable(substr((string) $c['expires_at'], 0, 10));
        if ($today > $end) {
            return 'Mã khuyến mãi đã hết hạn.';
        }
    }
    return null;
}

function booking_coupon_min_order_error(array $c, float $subtotal): ?string
{
    $min = (float) ($c['min_order_amount'] ?? 0);
    if ($subtotal < $min) {
        return 'Đơn chưa đạt giá trị tối thiểu để áp dụng mã này (' . number_format($min, 0, ',', '.') . ' đ).';
    }
    return null;
}

function booking_coupon_uses_error(array $c): ?string
{
    $max = $c['max_uses'] ?? null;
    if ($max === null || $max === '') {
        return null;
    }
    if ((int) ($c['used_count'] ?? 0) >= (int) $max) {
        return 'Mã khuyến mãi đã hết lượt sử dụng.';
    }
    return null;
}

function booking_discount_amount(float $subtotal, array $c): float
{
    $type = (string) ($c['discount_type'] ?? 'percent');
    $val = (float) ($c['discount_value'] ?? 0);
    if ($type === 'fixed') {
        return round(min($val, $subtotal), 2);
    }
    return round($subtotal * ($val / 100.0), 2);
}

/**
 * @return array{
 *   error?:string,
 *   base_subtotal:float,
 *   holiday_percent:int,
 *   holiday_amount:float,
 *   subtotal:float,
 *   discount:float,
 *   total:float,
 *   holiday_label:string,
 *   coupon?:array<string,mixed>
 * }
 */
function booking_pricing_with_coupon(
    PDO $pdo,
    float $pricePerPerson,
    int $adults,
    int $children,
    string $departureYmd,
    string $couponNorm
): array {
    $baseSubtotal = booking_subtotal($pricePerPerson, $adults, $children);
    $h = booking_holiday_addon($baseSubtotal, $departureYmd);
    $subtotal = $h['subtotal'];

    $baseOut = [
        'base_subtotal'     => $baseSubtotal,
        'holiday_percent'   => $h['holiday_percent'],
        'holiday_amount'    => $h['holiday_amount'],
        'subtotal'          => $subtotal,
        'holiday_label'     => $h['holiday_label'],
    ];

    if ($couponNorm === '') {
        return array_merge($baseOut, [
            'discount' => 0.0,
            'total'    => $subtotal,
        ]);
    }

    $stmt = $pdo->prepare('SELECT * FROM coupons WHERE code = :c LIMIT 1');
    $stmt->execute(['c' => $couponNorm]);
    $c = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$c) {
        return array_merge($baseOut, [
            'error'    => 'Mã khuyến mãi không tồn tại.',
            'discount' => 0.0,
            'total'    => $subtotal,
        ]);
    }
    foreach ([booking_coupon_is_valid_now($c), booking_coupon_min_order_error($c, $subtotal), booking_coupon_uses_error($c)] as $err) {
        if ($err !== null) {
            return array_merge($baseOut, [
                'error'    => $err,
                'discount' => 0.0,
                'total'    => $subtotal,
            ]);
        }
    }
    $discount = booking_discount_amount($subtotal, $c);
    $total = max(0.0, round($subtotal - $discount, 2));

    return array_merge($baseOut, [
        'discount' => $discount,
        'total'    => $total,
        'coupon'   => $c,
    ]);
}
