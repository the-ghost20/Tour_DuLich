<?php
declare(strict_types=1);

/**
 * Đồng bộ cột tours.available_slots với đơn đặt:
 * - Trừ chỗ khi tạo đơn (đang giữ chỗ từ lúc chờ duyệt).
 * - Cộng lại khi đơn chuyển sang đã hủy.
 */
function booking_guest_total(int $adults, int $children): int
{
    return max(0, $adults + $children);
}

function booking_consume_tour_slots(PDO $pdo, int $tourId, int $guests): bool
{
    if ($guests <= 0) {
        return true;
    }
    $st = $pdo->prepare(
        'UPDATE tours SET available_slots = available_slots - :g
         WHERE id = :id AND available_slots >= :g2'
    );
    $st->execute(['g' => $guests, 'g2' => $guests, 'id' => $tourId]);

    return $st->rowCount() === 1;
}

function booking_release_tour_slots(PDO $pdo, int $tourId, int $guests): void
{
    if ($guests <= 0) {
        return;
    }
    $pdo->prepare(
        'UPDATE tours SET available_slots = available_slots + :g WHERE id = :id'
    )->execute(['g' => $guests, 'id' => $tourId]);
}

/**
 * Chỉ hoàn chỗ khi thực sự chuyển sang đã hủy (tránh cộng hai lần).
 */
function booking_release_slots_if_cancelled(
    PDO $pdo,
    string $oldStatus,
    string $newStatus,
    int $tourId,
    int $guests
): void {
    if ($newStatus !== 'đã hủy' || $oldStatus === 'đã hủy' || $guests <= 0) {
        return;
    }
    booking_release_tour_slots($pdo, $tourId, $guests);
}
