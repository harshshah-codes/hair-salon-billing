<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Thin static session wrapper with flash (get-and-forget) support,
 * persistent "old input" data and a non-consuming validation-errors store.
 */
final class Session
{
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, $_SESSION);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value = null): mixed
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }
        $flash = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $flash;
    }

    public static function setOld(array $data): void
    {
        $_SESSION['_old'] = $data;
    }

    public static function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['_old'][$key] ?? $default;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
        session_regenerate_id(true);
    }

    public static function user(): ?array
    {
        return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
    }

    public static function userId(): ?int
    {
        $user = self::user();
        return isset($user['id']) ? (int) $user['id'] : null;
    }

    public static function isAuthenticated(): bool
    {
        return self::user() !== null;
    }

    public static function setErrors(array $errors): void
    {
        $_SESSION['_errors'] = $errors;
    }

    /** All validation errors (does not consume). */
    public static function errorsAll(): array
    {
        return $_SESSION['_errors'] ?? [];
    }

    /** Consuming reader for the legacy flash "errors" key. */
    public static function errors(): array
    {
        $errors = $_SESSION['_flash']['errors'] ?? [];
        unset($_SESSION['_flash']['errors']);
        return $errors;
    }

    public static function hasErrors(): bool
    {
        return self::errorsAll() !== [] || !empty($_SESSION['_flash']['errors']);
    }

    /** Alias for the consuming flash reader (legacy call sites). */
    public static function getFlash(string $key): mixed
    {
        return self::flash($key);
    }

    public static function sweep(): void
    {
        unset($_SESSION['_flash'], $_SESSION['_old'], $_SESSION['_errors']);
    }
}
