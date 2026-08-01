<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Database;

/**
 * Base service providing access to shared infrastructure.
 */
abstract class BaseService
{
    protected Database $db;

    public function __construct()
    {
        $this->db = App::getInstance()->db;
    }

    protected function logActivity(string $type, string $description, array $data = []): void
    {
        AuditService::log($type, $description, $data);
    }
}
