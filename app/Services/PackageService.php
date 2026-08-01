<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Package;
use App\Repositories\PackageRepository;

final class PackageService
{
    public function __construct(
        private PackageRepository $packages,
        private Package $packageModel,
        private ActivityService $activity
    ) {
    }

    public function create(array $data): int
    {
        $id = $this->packageModel->create($data);
        $this->activity->log('package.created', 'package', $id, ['name' => $data['name']]);
        return $id;
    }

    public function update(int $id, array $data): void
    {
        $this->packageModel->update($id, $data);
        $this->activity->log('package.updated', 'package', $id, ['name' => $data['name']]);
    }

    public function delete(int $id): void
    {
        $this->packageModel->delete($id);
        $this->activity->log('package.deleted', 'package', $id);
    }
}
