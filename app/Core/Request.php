<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public readonly string $method;
    public readonly string $path;
    private ?array $jsonBody = null;

    public function __construct()
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper((string) $_POST['_method']);
            if (in_array($override, ['PATCH', 'PUT', 'DELETE'], true)) {
                $method = $override;
            }
        }
        $this->method = $method;
        $this->path   = $this->resolvePath();
    }

    private function resolvePath(): string
    {
        $uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        } elseif (str_ends_with($base, '/public')) {
            // Apache rewrote /app/... to /app/public/index.php internally:
            // REQUEST_URI still lacks the /public segment.
            $outer = substr($base, 0, -strlen('/public'));
            if ($outer !== '' && str_starts_with($uri, $outer)) {
                $uri = substr($uri, strlen($outer));
            }
        }
        $uri = '/' . ltrim($uri, '/');

        return $uri === '/' ? '/' : rtrim($uri, '/');
    }

    public function isApi(): bool
    {
        return str_starts_with($this->path, '/api/');
    }

    public function wantsJson(): bool
    {
        if ($this->isApi()) {
            return true;
        }
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xrw    = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

        return str_contains($accept, 'application/json') || $xrw === 'fetch';
    }

    /** Body value: JSON body for JSON requests, else $_POST. */
    public function input(string $key, mixed $default = null): mixed
    {
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($ct, 'application/json')) {
            if ($this->jsonBody === null) {
                $this->jsonBody = json_decode(file_get_contents('php://input') ?: '', true) ?: [];
            }

            return $this->jsonBody[$key] ?? $default;
        }

        return $_POST[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+(\S+)$/i', $header, $m)) {
            return $m[1];
        }

        return null;
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

        return $_SERVER[$key] ?? null;
    }
}
