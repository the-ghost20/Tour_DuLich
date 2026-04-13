<?php
declare(strict_types=1);

/**
 * Phụ thu theo ngày khởi hành (Y-m-d).
 * - 20%: Tết Nguyên Đán + cận (khoảng dương lịch cập nhật theo năm trong booking_holiday_tet_ranges()).
 * - 10%: Tết Dương 1/1; 30/4–2/5; Quốc khánh 1–3/9; Giỗ Tổ Hùng Vương (cửa sổ ước lượng theo năm).
 * Chỉnh danh sách trong file này khi cần khớp lịch thực tế.
 */

function booking_holiday_tet_ranges(): array
{
    return [
        ['2025-01-25', '2025-02-05'],
        ['2026-02-06', '2026-02-18'],
        ['2027-01-24', '2027-02-05'],
        ['2028-01-12', '2028-01-24'],
        ['2029-02-01', '2029-02-12'],
        ['2030-01-22', '2030-02-03'],
    ];
}

/** Giỗ Tổ (10/3 âm): cửa sổ ±1 ngày quanh ngày dương lịch ước lượng. */
function booking_holiday_hung_king_ranges(): array
{
    return [
        ['2025-04-06', '2025-04-08'],
        ['2026-04-25', '2026-04-27'],
        ['2027-04-14', '2027-04-16'],
        ['2028-05-02', '2028-05-04'],
        ['2029-04-21', '2029-04-23'],
        ['2030-04-10', '2030-04-12'],
    ];
}

function booking_holiday_surcharge_percent(string $ymd): int
{
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $ymd);
    if (!$d || $d->format('Y-m-d') !== $ymd) {
        return 0;
    }

    $pct = 0;

    foreach (booking_holiday_tet_ranges() as $range) {
        [$from, $to] = $range;
        $df = new DateTimeImmutable($from);
        $dt = new DateTimeImmutable($to);
        if ($d >= $df && $d <= $dt) {
            $pct = max($pct, 20);
        }
    }

    $md = $d->format('m-d');
    if ($md === '01-01') {
        $pct = max($pct, 10);
    }
    if (in_array($md, ['04-30', '05-01', '05-02'], true)) {
        $pct = max($pct, 10);
    }
    if (in_array($md, ['09-01', '09-02', '09-03'], true)) {
        $pct = max($pct, 10);
    }

    foreach (booking_holiday_hung_king_ranges() as $range) {
        [$from, $to] = $range;
        $df = new DateTimeImmutable($from);
        $dt = new DateTimeImmutable($to);
        if ($d >= $df && $d <= $dt) {
            $pct = max($pct, 10);
        }
    }

    return $pct;
}

function booking_holiday_label(int $pct): string
{
    if ($pct <= 0) {
        return '';
    }
    if ($pct >= 20) {
        return 'Phụ thu cao điểm Tết (+20%)';
    }

    return 'Phụ thu ngày lễ (+10%)';
}
