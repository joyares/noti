<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /** Standard API envelope: { data, error }. */
    public static function api(mixed $data, ?string $error = null, int $status = 200): never
    {
        self::json(['data' => $data, 'error' => $error], $status);
    }

    public static function redirect(string $path, int $status = 302): never
    {
        header('Location: ' . url($path), true, $status);
        exit;
    }

    public static function notFound(Request $request): never
    {
        if ($request->wantsJson()) {
            self::api(null, 'Not found', 404);
        }
        http_response_code(404);
        echo '404 — not found';
        exit;
    }
}
