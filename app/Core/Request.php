<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP request abstraction.
 */
class Request
{
    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    public function isAjax(): bool
    {
        $h = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        return strtolower($h) === 'xmlhttprequest';
    }

    /**
     * Path component of the URL without the query string.
     */
    public function uri(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $uri = (string) ($uri ?? '/');
        $base = '/' . trim(parse_url(App::config('app.url'), PHP_URL_PATH) ?? '', '/');
        if ($base !== '/' && strpos($uri, $base) === 0) {
            $uri = substr($uri, strlen($base));
        }
        $uri = $uri === '' ? '/' : $uri;
        return '/' . ltrim($uri, '/');
    }

    public function path(): string
    {
        return $this->uri();
    }

    public function query(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    public function input(string $key, $default = null)
    {
        if (strpos($key, '.') !== false) {
            $value = $this->all();
            foreach (explode('.', $key) as $part) {
                if (!is_array($value) || !array_key_exists($part, $value)) {
                    return $default;
                }
                $value = $value[$part];
            }
            return $value;
        }
        return $_POST[$key] ?? $default;
    }

    public function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    public function all(): array
    {
        return $_POST;
    }

    public function only(array $keys): array
    {
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $this->input($key);
        }
        return $data;
    }

    public function has(string $key): bool
    {
        return isset($_POST[$key]);
    }

    public function setPostValue(string $key, $value): void
    {
        $_POST[$key] = $value;
    }

    public function int(string $key, int $default = 0): int
    {
        return (int) ($_POST[$key] ?? $default);
    }

    public function float(string $key, float $default = 0.0): float
    {
        return (float) ($_POST[$key] ?? $default);
    }

    public function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    public function userAgent(): string
    {
        return substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    }
}
