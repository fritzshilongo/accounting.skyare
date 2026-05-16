<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];
    private Database $db;
    private RequestContext $context;

    public function __construct(Database $db, RequestContext $context)
    {
        $this->db = $db;
        $this->context = $context;
    }

    public function get(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->map('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->map('POST', $path, $handler, $middleware);
    }

    public function map(string $method, string $path, callable|array $handler, array $middleware = []): void
    {
        $this->routes[$method][$path] = [
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $route = $this->routes[$method][$path] ?? null;

        if ($route === null) {
            http_response_code(404);
            echo 'Not Found';
            return;
        }

        foreach ($route['middleware'] as $middlewareClass) {
            $middleware = new $middlewareClass();
            $ok = $middleware->handle($this->db, $this->context);
            if ($ok !== true) {
                return;
            }
        }

        if (!is_callable($route['handler'])) {
            http_response_code(500);
            echo 'Invalid route handler.';
            return;
        }

        call_user_func($route['handler'], $this->db, $this->context);
    }
}
