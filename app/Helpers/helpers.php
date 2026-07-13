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
    $path = ltrim($path, '/');
    // Cache-bust on file change so browsers never serve stale CSS/JS.
    $file = BASE_PATH . '/public/assets/' . $path;
    $v    = is_file($file) ? (string) filemtime($file) : '0';

    return url('assets/' . $path) . '?v=' . $v;
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

/** Avatar: profile picture if set, else the initial letter. */
function avatar_html(array $user, string $class = 'avatar'): string
{
    if (!empty($user['avatar_path'])) {
        $src = url('/avatar') . '?v=' . substr(md5($user['avatar_path']), 0, 8);

        return '<span class="' . e($class) . '"><img src="' . e($src) . '" alt=""></span>';
    }

    return '<span class="' . e($class) . '">'
        . e(mb_strtoupper(mb_substr($user['display_name'] ?? '?', 0, 1))) . '</span>';
}

/** Tag slug: "Daily Needs" -> "daily-needs". */
function tag_slug(string $name): string
{
    $slug = strtolower(trim($name));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';

    return trim($slug, '-');
}
