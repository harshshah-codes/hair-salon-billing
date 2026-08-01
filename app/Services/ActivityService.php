<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ActivityLogRepository;

final class ActivityService
{
    public function __construct(private ActivityLogRepository $logs)
    {
    }

    public function log(string $action, ?string $entityType = null, ?int $entityId = null, array $data = []): void
    {
        $this->logs->log($action, $entityType, $entityId, $data);
    }

    public function recent(int $limit = 20): array
    {
        return $this->logs->recent($limit);
    }
}
