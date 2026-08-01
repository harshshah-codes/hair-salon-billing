<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use App\Repositories\EmployeeRepository;

final class EmployeeService
{
    public function __construct(
        private EmployeeRepository $employees,
        private Employee $employeeModel,
        private ActivityService $activity
    ) {
    }

    public function create(array $data): int
    {
        $id = $this->employeeModel->create($data);
        $this->activity->log('employee.created', 'employee', $id, ['name' => $data['name']]);
        return $id;
    }

    public function update(int $id, array $data): void
    {
        $this->employeeModel->update($id, $data);
        $this->activity->log('employee.updated', 'employee', $id, ['name' => $data['name']]);
    }

    public function delete(int $id): void
    {
        $this->employeeModel->delete($id);
        $this->activity->log('employee.deleted', 'employee', $id);
    }
}
