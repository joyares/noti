<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Base-path aware URL (works at noti.test/ and localhost/noti/public/). */
function url(string $path = '/'): string
{
    static $base = null;
    if ($base === null) {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    }

    return $base . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function human_bytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1024 * 1024) {
        return round($bytes / 1024, 1) . ' KB';
    }

    return round($bytes / (1024 * 1024), 1) . ' MB';
}

/** Short date for note rows: "Jul 12" this year, "Jul 12, 2025" otherwise. */
function nice_date(string $timestamp): string
{
    $ts = strtotime($timestamp);
    if ($ts === false) {
        return '';
    }
    $format = date('Y', $ts) === date('Y') ? 'M j' : 'M j, Y';

    return date($format, $ts);
}

function full_date(string $timestamp): string
{
    $ts = strtotime($timestamp);

    return $ts === false ? '' : date('M j, Y · g:i A', $ts);
}

/** Tag slug: "Daily Needs" -> "daily-needs". */
function tag_slug(string $name): string
{
    $slug = strtolower(trim($name));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';

    return trim($slug, '-');
}
