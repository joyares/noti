<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . self::token() . '">';
    }

    public static function validate(Request $request): bool
    {
        $sent = $request->input('_csrf') ?? $request->header('X-CSRF-Token');

        return is_string($sent) && hash_equals(self::token(), $sent);
    }
}
