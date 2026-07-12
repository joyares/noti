<?php

declare(strict_types=1);

namespace App\Core;

final class App
{
    private static array $config = [];

    public static function init(array $config): void
    {
        self::$config = $config;
    }

    public static function config(string $key, mixed $default = null): mixed
    {
        return self::$config[$key] ?? $default;
    }
}
