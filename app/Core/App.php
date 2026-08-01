<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Lightweight application container / service locator.
 */
final class App
{
    private static ?App $instance = null;

    public array $config = [];
    public array $database = [];
    public Request $request;
    public Response $response;
    public Session $session;
    public Database $db;

    private function __construct() {}

    public static function getInstance(): App
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function config(string $key, $default = null)
    {
        $app = self::getInstance();
        $value = $app->config;
        foreach (explode('.', $key) as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }
        return $value;
    }

    public function setting(string $key, $default = null)
    {
        $row = $this->db->fetch(
            'SELECT `value` FROM `settings` WHERE `key` = ?',
            [$key]
        );
        return $row ? $row['value'] : $default;
    }
}
