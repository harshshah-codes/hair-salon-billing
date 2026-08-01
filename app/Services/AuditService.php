<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Request;

class AuditService
{
    public static function log(string $type, string $description, array $data = []): void
    {
        $app = App::getInstance();
        $db = $app->db;

        $userAgent = '';
        $ip = '';
        if (isset($app->request)) {
            $ip = $app->request->ip();
            $userAgent = $app->request->userAgent();
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
            $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
        }

        $db->insert('activity_logs', [
            'user_id' => $app->session->userId(),
            'type' => $type,
            'description' => substr($description, 0, 255),
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'ip' => $ip,
            'user_agent' => $userAgent,
        ]);
    }
}
