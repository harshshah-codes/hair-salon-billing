<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Service;
use App\Repositories\ServiceRepository;

final class ServiceService
{
    public function __construct(
        private ServiceRepository $services,
        private Service $serviceModel,
        private ActivityService $activity
    ) {
    }

    public function create(array $data): int
    {
        $id = $this->serviceModel->create($data);
        $this->activity->log('service.created', 'service', $id, ['name' => $data['name']]);
        return $id;
    }

    public function update(int $id, array $data): void
    {
        $this->serviceModel->update($id, $data);
        $this->activity->log('service.updated', 'service', $id, ['name' => $data['name']]);
    }

    public function delete(int $id): void
    {
        $this->serviceModel->delete($id);
        $this->activity->log('service.deleted', 'service', $id);
    }
}
