<?php
declare(strict_types=1);

/**
 * Điểm nhấn (JSON mảng chuỗi) + lịch khởi hành (JSON hoặc dòng: ngày [giá] [promo]).
 */

/** @return list<string> */
function tour_highlights_decode(?string $json): array
{
    if ($json === null || trim((string) $json) === '') {
        return [];
    }
    $data = json_decode((string) $json, true);
    if (!is_array($data)) {
        return [];
    }
    $out = [];
    foreach ($data as $row) {
        $s = is_string($row) ? trim($row) : '';
        if ($s !== '') {
            $out[] = $s;
        }
    }
    return $out;
}

/**
 * @return list<array{date:string,price:float,promo:bool}>
 */
function tour_departures_decode(?string $raw, float $defaultPrice): array
{
    if ($raw === null || trim((string) $raw) === '') {
        return [];
    }
    $raw = trim((string) $raw);
    if ($raw !== '' && ($raw[0] === '[')) {
        $data = json_decode($raw, true);
        if (is_array($data)) {
            return tour_departures_normalize_array($data, $defaultPrice);
        }
    }
    return tour_departures_parse_lines($raw, $defaultPrice);
}

/**
 * @param list<mixed> $data
 * @return list<array{date:string,price:float,promo:bool}>
 */
function tour_departures_normalize_array(array $data, float $defaultPrice): array
{
    $out = [];
    foreach ($data as $item) {
        if (!is_array($item)) {
            continue;
        }
        $d = isset($item['date']) ? trim((string) $item['date']) : '';
        if ($d === '') {
            continue;
        }
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $d);
        if (!$dt || $dt->format('Y-m-d') !== $d) {
            continue;
        }
        $p = array_key_exists('price', $item) ? (float) $item['price'] : $defaultPrice;
        if ($p < 0) {
            $p = $defaultPrice;
        }
        $promo = !empty($item['promo']) || !empty($item['highlight']);
        $out[] = ['date' => $d, 'price' => $p, 'promo' => $promo];
    }
    usort($out, static fn ($a, $b) => strcmp($a['date'], $b['date']));
    return $out;
}

/**
 * @return list<array{date:string,price:float,promo:bool}>
 */
function tour_departures_parse_lines(string $text, float $defaultPrice): array
{
    $out = [];
    foreach (preg_split("/\r\n|\r|\n/", $text) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = preg_split('/\s+/', $line);
        if ($parts === false || $parts === []) {
            continue;
        }
        $d = $parts[0];
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $d);
        if (!$dt || $dt->format('Y-m-d') !== $d) {
            continue;
        }
        $p = $defaultPrice;
        $promo = false;
        if (isset($parts[1]) && is_numeric($str = (string) $parts[1])) {
            $p = (float) $str;
        }
        if (isset($parts[2]) && preg_match('/^(1|promo|km)$/iu', (string) $parts[2])) {
            $promo = true;
        }
        $out[] = ['date' => $d, 'price' => $p, 'promo' => $promo];
    }
    usort($out, static fn ($a, $b) => strcmp($a['date'], $b['date']));
    return $out;
}

function tour_highlights_from_textarea(string $text): ?string
{
    $lines = preg_split("/\r\n|\r|\n/", $text);
    if ($lines === false) {
        return null;
    }
    $arr = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $arr[] = $line;
        }
    }
    if ($arr === []) {
        return null;
    }
    $j = json_encode($arr, JSON_UNESCAPED_UNICODE);
    return $j === false ? null : $j;
}

function tour_departures_to_storage(string $text, float $defaultPrice): ?string
{
    $text = trim($text);
    if ($text === '') {
        return null;
    }
    $parsed = tour_departures_decode($text, $defaultPrice);
    if ($parsed === []) {
        return null;
    }
    $j = json_encode($parsed, JSON_UNESCAPED_UNICODE);
    return $j === false ? null : $j;
}

function tour_format_highlight_line(string $line): string
{
    $e = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
    return preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $e) ?? $e;
}

/**
 * Danh sách URL ảnh phụ (JSON mảng hoặc mỗi dòng một URL http/https).
 *
 * @return list<string>
 */
function tour_gallery_urls_decode(?string $raw): array
{
    if ($raw === null || trim((string) $raw) === '') {
        return [];
    }
    $t = trim((string) $raw);
    if ($t !== '' && $t[0] === '[') {
        $data = json_decode($t, true);
        if (is_array($data)) {
            $out = [];
            foreach ($data as $u) {
                $s = is_string($u) ? trim($u) : '';
                if ($s !== '' && tour_gallery_url_looks_valid($s)) {
                    $out[] = $s;
                }
            }
            return array_values(array_unique($out));
        }
    }
    $out = [];
    foreach (preg_split("/\r\n|\r|\n/", $t) as $line) {
        $line = trim($line);
        if ($line !== '' && tour_gallery_url_looks_valid($line)) {
            $out[] = $line;
        }
    }
    return array_values(array_unique($out));
}

function tour_gallery_url_looks_valid(string $url): bool
{
    return (bool) preg_match('#^https?://[^\s]+$#iu', $url);
}

function tour_gallery_urls_from_textarea(string $text): ?string
{
    $urls = tour_gallery_urls_decode($text);
    if ($urls === []) {
        return null;
    }
    $j = json_encode($urls, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return $j === false ? null : $j;
}
