<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\EmployeeRepository;

final class EmployeeController extends ApiController
{
    private EmployeeRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new EmployeeRepository();
    }

    /** GET /api/employees ?search=&status=&page= */
    public function index(): void
    {
        $search = trim((string) $this->request->query('search', ''));
        $status = (string) $this->request->query('status', 'all');
        $page = max(1, (int) $this->request->query('page', 1));
        $perPage = min(100, max(1, (int) $this->request->query('per_page', 15)));

        $this->ok($this->repo->listing($search, $status, $page, $perPage));
    }

    /** GET /api/employees/{id} */
    public function show(int $id): void
    {
        $employee = $this->repo->find($id);
        if (!$employee) {
            $this->error('Employee not found.', 404);
        }
        $this->ok([
            'employee' => $employee,
            'stats' => $this->repo->stats($id),
            'recent_services' => $this->repo->recentServices($id, 15),
            'allocations' => $this->repo->allocations($id, 1, 10),
        ]);
    }

    /** POST /api/employees */
    public function store(): void
    {
        $data = $this->validateInput([
            'name' => 'required|max:160',
            'mobile' => 'required|max:20',
            'email' => 'nullable|email|max:160',
            'designation' => 'nullable|max:120',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'joined_at' => 'nullable|date',
            'status' => 'in:active,inactive',
        ]);

        $data['status'] = $data['status'] ?? 'active';
        $data['joined_at'] = $data['joined_at'] ?? null;
        $id = $this->repo->create($data);
        $this->logActivity('employees.save', 'Employee created: ' . $data['name']);
        $this->ok(['id' => $id, 'employee' => $this->repo->find($id)], 'Employee created successfully.');
    }

    /** PUT/POST /api/employees/{id} */
    public function update(int $id): void
    {
        if (!$this->repo->find($id)) {
            $this->error('Employee not found.', 404);
        }
        $data = $this->validateInput([
            'name' => 'required|max:160',
            'mobile' => 'required|max:20',
            'email' => 'nullable|email|max:160',
            'designation' => 'nullable|max:120',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'joined_at' => 'nullable|date',
            'status' => 'in:active,inactive',
        ]);

        $data['status'] = $data['status'] ?? 'active';
        $data['joined_at'] = $data['joined_at'] ?? null;
        $this->repo->update($id, $data);
        $this->logActivity('employees.save', 'Employee updated: ' . $data['name']);
        $this->ok(['employee' => $this->repo->find($id)], 'Employee updated successfully.');
    }

    /** DELETE /api/employees/{id} */
    public function destroy(int $id): void
    {
        if (!$this->repo->find($id)) {
            $this->error('Employee not found.', 404);
        }
        $this->repo->delete($id);
        $this->logActivity('employees.delete', 'Employee deleted: #' . $id);
        $this->ok(null, 'Employee deleted.');
    }
}
