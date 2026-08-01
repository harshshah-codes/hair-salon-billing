<?php

declare(strict_types=1);

namespace App\Core;

/**
 * CSRF token generation and validation.
 */
class CSRF
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . self::token() . '">';
    }

    public static function check(?string $token = null): bool
    {
        $expected = $_SESSION['_csrf'] ?? '';
        $supplied = $token ?? ($_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        if (!is_string($expected) || !is_string($supplied) || $expected === '' || $supplied === '') {
            return false;
        }
        return hash_equals($expected, $supplied);
    }

    public static function validate(): void
    {
        if (!self::check()) {
            $app = App::getInstance();
            if ($app->request->isAjax()) {
                $app->response->json(['success' => false, 'message' => 'Session expired. Please refresh the page.'], 419);
            }
            http_response_code(419);
            $app->session->flash('error', 'Your session has expired. Please try again.');
            $app->response->redirect('/');
        }
    }

    public static function reset(): void
    {
        unset($_SESSION['_csrf']);
    }
}
