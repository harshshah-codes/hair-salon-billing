<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP request abstraction.
 */
class Request
{
    private ?array $jsonBody = null;

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

    public function isJson(): bool
    {
        $type = strtolower($_SERVER['CONTENT_TYPE'] ?? '');
        return strpos($type, 'application/json') !== false;
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? null;
    }

    /**
     * Parsed JSON request body (empty array when none / invalid).
     */
    public function json(): array
    {
        if ($this->jsonBody === null) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw ?: '', true);
            $this->jsonBody = is_array($decoded) ? $decoded : [];
        }
        return $this->jsonBody;
    }

    /**
     * All request input: JSON body merged over POST (JSON wins).
     */
    public function all(): array
    {
        $data = $_POST;
        if ($this->isJson()) {
            $data = array_replace($data, $this->json());
        }
        return $data;
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
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }
        if ($this->isJson()) {
            $json = $this->json();
            return $json[$key] ?? $default;
        }
        return $default;
    }

    public function post(string $key, $default = null)
    {
        return $this->input($key, $default);
    }

    public function only(array $keys): array
    {
        $data = $this->all();
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = $data[$key] ?? null;
        }
        return $out;
    }

    public function has(string $key): bool
    {
        $data = $this->all();
        return isset($data[$key]);
    }

    public function setPostValue(string $key, $value): void
    {
        $_POST[$key] = $value;
    }

    public function int(string $key, int $default = 0): int
    {
        return (int) ($this->input($key, $default));
    }

    public function float(string $key, float $default = 0.0): float
    {
        return (float) ($this->input($key, $default));
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
