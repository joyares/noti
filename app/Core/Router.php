<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<string, array<int, array{pattern:string, regex:string, handler:array}>> */
    private array $routes = [];

    public function get(string $pattern, array $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, array $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function patch(string $pattern, array $handler): void
    {
        $this->add('PATCH', $pattern, $handler);
    }

    public function delete(string $pattern, array $handler): void
    {
        $this->add('DELETE', $pattern, $handler);
    }

    private function add(string $method, string $pattern, array $handler): void
    {
        $regex = preg_replace('/\{([a-z_]+)\}/', '(?P<$1>[^/]+)', $pattern);
        $this->routes[$method][] = [
            'pattern' => $pattern,
            'regex'   => '#^' . $regex . '$#',
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes[$request->method] ?? [] as $route) {
            if (preg_match($route['regex'], $request->path, $m)) {
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                [$class, $action] = $route['handler'];
                $controller = new $class($request);
                $controller->$action(...array_values(array_map('urldecode', $params)));

                return;
            }
        }

        Response::notFound($request);
    }
}
