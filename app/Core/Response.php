<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP response helpers.
 */
class Response
{
    public function status(int $code): self
    {
        http_response_code($code);
        return $this;
    }

    public function header(string $name, string $value): self
    {
        header("$name: $value");
        return $this;
    }

    public function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function redirect(string $path): void
    {
        header('Location: ' . $this->url($path));
        exit;
    }

    public function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? null;
        $path = $referer ?? App::config('app.url') . '/dashboard';
        header('Location: ' . $path);
        exit;
    }

    public function abort(int $status = 404, string $message = 'Not Found'): void
    {
        http_response_code($status);
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        if (str_starts_with($uri, '/api')) {
            $this->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }
        $layout = is_file(APP_PATH . '/Views/partials/error.php')
            ? APP_PATH . '/Views/partials/error.php'
            : null;
        if ($layout) {
            $view = new View();
            $view->render('partials/error', [
                'status' => $status,
                'message' => $message,
                'title' => $status,
            ], 'plain');
        } else {
            echo '<h1>' . $status . '</h1><p>' . $message . '</p>';
        }
        exit;
    }

    public function download(string $filename, string $content, string $mime = 'application/octet-stream'): void
    {
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }

    public function url(string $path = ''): string
    {
        $base = rtrim(App::config('app.url'), '/');
        return $base . '/' . ltrim($path, '/');
    }
}
