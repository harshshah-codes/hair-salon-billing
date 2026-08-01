<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal file logger writing to storage/logs/app.log.
 */
class Logger
{
    private static ?string $file = null;

    public static function file(?string $path = null): void
    {
        self::$file = $path;
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    private static function write(string $level, string $message, array $context = []): void
    {
        $path = self::$file
            ?? (defined('STORAGE_PATH') ? STORAGE_PATH . '/logs/app.log' : sys_get_temp_dir() . '/app.log');

        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $line = sprintf(
            "[%s] %-7s %s%s%s",
            date('Y-m-d H:i:s'),
            $level,
            $message,
            $context ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '',
            PHP_EOL
        );

        @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    }
}
