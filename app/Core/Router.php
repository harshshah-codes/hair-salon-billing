<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal dependency-free router.
 *
 * Route patterns use {param} placeholders, e.g. /customers/{id}/edit
 */
class Router
{
    private array $routes = [];

    public function __construct(
        private Request $request,
        private Response $response
    ) {}

    public function add(string $method, string $pattern, array $handler, array $middleware = []): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function resolve(): void
    {
        $uri = $this->request->uri();
        $method = $this->request->method();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            $params = $this->match($route['pattern'], $uri);
            if ($params === null) {
                continue;
            }

            // Global CSRF protection for state-changing requests
            if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                CSRF::validate();
            }

            $this->runMiddleware($route['middleware']);

            $this->dispatch($route['handler'], $params);
            return;
        }

        $this->response->abort(404, 'Page not found');
    }

    private function match(string $pattern, string $uri): ?array
    {
        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        if (preg_match($regex, $uri, $matches)) {
            return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        }
        return null;
    }

    private function runMiddleware(array $middleware): void
    {
        foreach ($middleware as $item) {
            $class = is_array($item) ? $item[0] : $item;
            $param = is_array($item) ? ($item[1] ?? null) : null;
            $instance = new $class();
            $instance->handle($this->request, $this->response, $param);
        }
    }

    private function dispatch(array $handler, array $params): void
    {
        [$controllerClass, $method] = $handler;
        if (!class_exists($controllerClass)) {
            $this->response->abort(500, "Controller not found: $controllerClass");
        }
        $controller = new $controllerClass();
        $controller->{$method}(...array_values($params));
    }
}
