<?php
declare(strict_types=1);

/**
 * Lịch trình chi tiết tour — lưu JSON trong cột `itinerary`.
 * Admin nhập theo khối: mỗi ngày bắt đầu bằng dòng "=== NGÀY 1 ===", dòng tiếp là tiêu đề, phần còn lại là nội dung.
 *
 * @return list<array{title:string,body:string}>
 */
function tour_itinerary_decode(?string $json): array
{
    if ($json === null || trim((string) $json) === '') {
        return [];
    }
    $data = json_decode((string) $json, true);
    if (!is_array($data)) {
        return [];
    }
    $out = [];
    foreach ($data as $item) {
        if (!is_array($item)) {
            continue;
        }
        $title = isset($item['title']) ? trim((string) $item['title']) : '';
        $body = isset($item['body']) ? trim((string) $item['body']) : '';
        if ($title === '' && $body === '') {
            continue;
        }
        if ($title === '') {
            $title = 'Ngày ' . (string) (count($out) + 1);
        }
        $out[] = ['title' => $title, 'body' => $body];
    }
    return $out;
}

/** Biến mảng ngày thành văn bản cho form admin */
function tour_itinerary_to_plaintext(array $days): string
{
    if ($days === []) {
        return '';
    }
    $parts = [];
    foreach ($days as $i => $d) {
        $n = $i + 1;
        $parts[] = '=== NGÀY ' . $n . ' ===';
        $parts[] = $d['title'];
        if ($d['body'] !== '') {
            $parts[] = $d['body'];
        }
        $parts[] = '';
    }
    return rtrim(implode("\n", $parts));
}

/** null = xóa lịch trình; JsonException nếu không encode được */
function tour_itinerary_from_plaintext(string $raw): ?string
{
    $raw = str_replace("\r\n", "\n", trim($raw));
    if ($raw === '') {
        return null;
    }

    $lines = explode("\n", $raw);
    $out = [];
    $state = 0;
    $title = '';
    $bodyLines = [];

    $flush = static function () use (&$out, &$title, &$bodyLines): void {
        if ($title === '' && $bodyLines === []) {
            return;
        }
        $body = trim(implode("\n", $bodyLines));
        $t = $title === '' ? 'Chi tiết' : $title;
        $out[] = ['title' => $t, 'body' => $body];
        $title = '';
        $bodyLines = [];
    };

    foreach ($lines as $line) {
        $trim = trim($line);
        if (preg_match('/^===\s*NGÀY\s+\d+\s*===\s*$/iu', $trim)) {
            $flush();
            $state = 1;
            continue;
        }
        if ($state === 1) {
            if ($trim === '') {
                continue;
            }
            $title = $trim;
            $state = 2;
            continue;
        }
        if ($state === 2) {
            $bodyLines[] = $line;
        }
    }
    $flush();

    if ($out === []) {
        $head = trim($lines[0] ?? '');
        $rest = array_slice($lines, 1);
        $body = trim(implode("\n", $rest));
        if ($head === '') {
            return null;
        }
        $out[] = ['title' => $head, 'body' => $body];
    }

    $enc = json_encode($out, JSON_UNESCAPED_UNICODE);
    return $enc === false ? null : $enc;
}
