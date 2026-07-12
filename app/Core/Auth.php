<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\ApiToken;
use App\Models\User;

final class Auth
{
    private static ?array $user = null;

    public static function loginWeb(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        self::$user = $user;
    }

    public static function logoutWeb(): void
    {
        $_SESSION = [];
        session_destroy();
        self::$user = null;
    }

    public static function userWeb(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }
        $id = $_SESSION['user_id'] ?? null;
        if ($id === null) {
            return null;
        }
        self::$user = (new User())->find((int) $id);

        return self::$user;
    }

    public static function userApi(Request $request): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }
        $token = $request->bearerToken();
        if ($token === null) {
            return null;
        }
        self::$user = (new ApiToken())->userForToken($token);

        return self::$user;
    }
}
