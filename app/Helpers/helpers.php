<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\CSRF;
use App\Core\Session;

if (!function_exists('e')) {
    /**
     * Escape output for HTML (XSS protection).
     */
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        return App::getInstance()->response->url($path);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): void
    {
        App::getInstance()->response->redirect($path);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return CSRF::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return CSRF::field();
    }
}

if (!function_exists('old')) {
    function old(string $key, $default = null)
    {
        $session = new Session();
        $old = $session->old();
        return $old[$key] ?? $default;
    }
}

if (!function_exists('setting')) {
    function setting(string $key, $default = null)
    {
        return App::getInstance()->setting($key, $default);
    }
}

if (!function_exists('view')) {
    /**
     * Render a view and return its output as a string.
     */
    function view(string $template, array $data = [], string $layout = 'plain'): string
    {
        $view = new \App\Core\View();
        ob_start();
        $view->render($template, $data, $layout);
        return (string) ob_get_clean();
    }
}

if (!function_exists('partial')) {
    /**
     * Render a partial inline with no layout.
     */
    function partial(string $template, array $data = []): void
    {
        (new \App\Core\View())->render($template, $data, 'plain');
    }
}

if (!function_exists('error')) {
    /**
     * Validation error message for a single field.
     */
    function error(string $key): ?string
    {
        $errors = (new Session())->errorsAll();
        $message = $errors[$key] ?? null;
        if (is_array($message)) {
            $message = $message[0] ?? null;
        }
        return is_string($message) ? $message : null;
    }
}

if (!function_exists('business_name')) {
    function business_name(): string
    {
        return (string) setting('business_name', App::config('app.name', 'Nirav Hair Storm'));
    }
}

if (!function_exists('auth_check')) {
    function auth_check(): bool
    {
        return (new Session())->isAuthenticated();
    }
}

if (!function_exists('auth_user')) {
    function auth_user(): ?array
    {
        return (new Session())->user();
    }
}

if (!function_exists('auth_id')) {
    function auth_id(): ?int
    {
        return (new Session())->userId();
    }
}

if (!function_exists('can')) {
    function can(string $permission): bool
    {
        $user = auth_user();
        if (!$user) {
            return false;
        }
        return \App\Core\Access::can($user['role_slug'] ?? '', $user['permissions'] ?? [], $permission);
    }
}

if (!function_exists('money')) {
    function money($amount, bool $withSymbol = true): string
    {
        $value = number_format((float) $amount, 2);
        return $withSymbol ? '₹' . $value : $value;
    }
}

if (!function_exists('format_date')) {
    function format_date($date, string $format = 'd M Y'): string
    {
        if (empty($date)) {
            return '—';
        }
        $ts = is_numeric($date) ? (int) $date : strtotime((string) $date);
        return $ts ? date($format, $ts) : '—';
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime($date): string
    {
        if (empty($date)) {
            return '—';
        }
        $ts = strtotime((string) $date);
        return $ts ? date('d M Y, h:i A', $ts) : '—';
    }
}

if (!function_exists('time_ago')) {
    function time_ago($date): string
    {
        if (empty($date)) {
            return '—';
        }
        $ts = strtotime((string) $date);
        $diff = time() - $ts;
        if ($diff < 60) {
            return 'Just now';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . 'm ago';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . 'h ago';
        }
        if ($diff < 604800) {
            return floor($diff / 86400) . 'd ago';
        }
        return date('d M Y', $ts);
    }
}

if (!function_exists('active_class')) {
    function active_class(string $segment, string $current): string
    {
        return $segment === $current ? ' active' : '';
    }
}

if (!function_exists('badge_class')) {
    /**
     * Map a status string to a bootstrap badge color.
     */
    function badge_class(string $status): string
    {
        $map = [
            'active' => 'success',
            'inactive' => 'muted',
            'paid' => 'success',
            'issued' => 'info',
            'partially_paid' => 'warning',
            'draft' => 'secondary',
            'cancelled' => 'danger',
            'expired' => 'danger',
            'exhausted' => 'warning',
            'purchase' => 'primary',
            'debit' => 'danger',
            'credit' => 'success',
            'adjust' => 'info',
            'expire' => 'muted',
        ];
        return $map[strtolower($status)] ?? 'secondary';
    }
}

if (!function_exists('payment_method_icon')) {
    function payment_method_icon(string $method): string
    {
        $map = [
            'cash' => 'fa-solid fa-money-bill-1',
            'card' => 'fa-solid fa-credit-card',
            'upi' => 'fa-solid fa-mobile-screen-button',
            'bank' => 'fa-solid fa-building-columns',
            'other' => 'fa-solid fa-wallet',
        ];
        return $map[strtolower($method)] ?? 'fa-solid fa-wallet';
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('upload_url')) {
    function upload_url(?string $path): string
    {
        if (empty($path)) {
            return '';
        }
        return url('uploads/' . ltrim($path, '/'));
    }
}

if (!function_exists('avatar_or_initials')) {
    /**
     * Returns an <img> or an initials placeholder for a person.
     */
    function avatar_or_initials(string $name, ?string $photo = null, int $size = 40): string
    {
        $initials = '';
        $parts = preg_split('/\s+/', trim($name));
        foreach ((array) $parts as $part) {
            if ($initials === '' || count((array) $parts) === 1) {
                $initials .= mb_strtoupper(mb_substr($part, 0, 1));
            }
        }
        if ($photo) {
            return '<img src="' . e(upload_url($photo)) . '" class="avatar" alt="' . e($name) . '" style="width:' . $size . 'px;height:' . $size . 'px">';
        }
        $hsl = (crc32($name) % 360);
        return '<span class="avatar avatar-initials" style="width:' . $size . 'px;height:' . $size . 'px;background:hsl(' . $hsl . ', 55%, 40%)">' . e($initials) . '</span>';
    }
}

if (!function_exists('read_more')) {
    function read_more(?string $text, int $limit = 80): string
    {
        if ($text === null || $text === '') {
            return '—';
        }
        if (mb_strlen($text) <= $limit) {
            return e($text);
        }
        return e(mb_substr($text, 0, $limit)) . '&hellip;';
    }
}
