<?php

declare(strict_types=1);

/**
 * Application bootstrap: autoloader, config, helpers, session.
 */

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('UPLOAD_PATH', BASE_PATH . '/public/uploads');
define('DATABASE_PATH', BASE_PATH . '/database');
define('VIEW_PATH', APP_PATH . '/Views');

if (is_file(BASE_PATH . '/.env')) {
    foreach (file(BASE_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key   = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last  = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $map = [
        'Controllers' => 'Controllers',
        'Core' => 'Core',
        'Models' => 'Models',
        'Services' => 'Services',
        'Repositories' => 'Repositories',
        'Helpers' => 'Helpers',
        'Middleware' => 'Middleware',
    ];
    $parts = explode('\\', $relative);
    $ns = array_shift($parts);
    $dir = $map[$ns] ?? null;
    if ($dir === null) {
        return;
    }
    $file = APP_PATH . '/' . $dir . '/' . implode('/', $parts) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

require_once APP_PATH . '/Helpers/helpers.php';

$app = App\Core\App::getInstance();
$app->config = require BASE_PATH . '/config/config.php';
$app->database = require BASE_PATH . '/config/database.php';

date_default_timezone_set($app->config['app']['timezone']);

if (!empty($app->config['app']['debug'])) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
}

set_exception_handler(function (Throwable $e): void {
    http_response_code(500);
    App\Core\Logger::error('Uncaught ' . get_class($e), [
        'message' => $e->getMessage(),
        'file'    => $e->getFile() . ':' . $e->getLine(),
        'trace'   => array_slice(explode("\n", $e->getTraceAsString()), 0, 12),
    ]);
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    if (str_starts_with($uri, '/api')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => $app->config['app']['debug'] ? $e->getMessage() : 'Internal server error.',
        ]);
        exit;
    }
    if (!empty($app->config['app']['debug'])) {
        echo '<pre style="font-family:monospace;padding:20px;background:#111;color:#f87171;white-space:pre-wrap;">';
        echo htmlspecialchars((string) $e);
        echo '</pre>';
    } else {
        echo view('errors/500', ['message' => 'Something went wrong on our side.'], 'plain');
    }
    exit;
});

set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    App\Core\Logger::error('PHP ' . $severity, [
        'message' => $message,
        'file'    => $file . ':' . $line,
    ]);
    return false;
});

register_shutdown_function(function (): void {
    $error = error_get_last();
    if ($error === null) {
        return;
    }
    if (in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        App\Core\Logger::error('Fatal ' . $error['type'], [
            'message' => $error['message'],
            'file'    => $error['file'] . ':' . $error['line'],
        ]);
    }
});

if (session_status() === PHP_SESSION_NONE) {
    session_name($app->config['session']['name']);
    session_set_cookie_params([
        'lifetime' => $app->config['session']['lifetime'],
        'path' => '/',
        'httponly' => $app->config['session']['cookie_httponly'],
        'secure' => $app->config['session']['cookie_secure'],
        'samesite' => 'Lax',
    ]);
    session_start();
}

$app->request = new App\Core\Request();
$app->response = new App\Core\Response();
$app->session = new App\Core\Session();
$app->db = new App\Core\Database($app->database);

$router = new App\Core\Router($app->request, $app->response);
$routes = require BASE_PATH . '/routes/web.php';
foreach ($routes as $route) {
    [$method, $pattern, $handler] = $route;
    $middleware = $route[3] ?? [];
    $router->add($method, $pattern, $handler, $middleware);
}
$apiRoutes = require BASE_PATH . '/routes/api.php';
foreach ($apiRoutes as $route) {
    [$method, $pattern, $handler] = $route;
    $middleware = $route[3] ?? [];
    $router->add($method, $pattern, $handler, $middleware);
}
$router->resolve();
