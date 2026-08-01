<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\ServiceRepository;

final class ServiceController extends ApiController
{
    private ServiceRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new ServiceRepository();
    }

    /** GET /api/services ?search=&status=&category=&page= */
    public function index(): void
    {
        $search = trim((string) $this->request->query('search', ''));
        $status = (string) $this->request->query('status', 'all');
        $category = (string) $this->request->query('category', '');
        $page = max(1, (int) $this->request->query('page', 1));
        $perPage = min(100, max(1, (int) $this->request->query('per_page', 15)));

        $this->ok([
            ...$this->repo->listing($search, $status, $category, $page, $perPage),
            'categories' => $this->repo->categories(),
        ]);
    }

    /** GET /api/services/{id} */
    public function show(int $id): void
    {
        $service = $this->repo->find($id);
        if (!$service) {
            $this->error('Service not found.', 404);
        }
        $this->ok(['service' => $service]);
    }

    /** POST /api/services */
    public function store(): void
    {
        $data = $this->validateInput([
            'name' => 'required|max:160',
            'category' => 'nullable|max:100',
            'duration_minutes' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable',
            'status' => 'in:active,inactive',
        ]);

        $data['status'] = $data['status'] ?? 'active';
        $id = $this->repo->create($data);
        $this->logActivity('services.create', 'Service created: ' . $data['name']);
        $this->ok(['id' => $id, 'service' => $this->repo->find($id)], 'Service created successfully.');
    }

    /** PUT/POST /api/services/{id} */
    public function update(int $id): void
    {
        if (!$this->repo->find($id)) {
            $this->error('Service not found.', 404);
        }
        $data = $this->validateInput([
            'name' => 'required|max:160',
            'category' => 'nullable|max:100',
            'duration_minutes' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable',
            'status' => 'in:active,inactive',
        ]);

        $data['status'] = $data['status'] ?? 'active';
        $this->repo->update($id, $data);
        $this->logActivity('services.update', 'Service updated: ' . $data['name']);
        $this->ok(['service' => $this->repo->find($id)], 'Service updated successfully.');
    }

    /** DELETE /api/services/{id} */
    public function destroy(int $id): void
    {
        if (!$this->repo->find($id)) {
            $this->error('Service not found.', 404);
        }
        $this->repo->delete($id);
        $this->logActivity('services.delete', 'Service deleted: #' . $id);
        $this->ok(null, 'Service deleted.');
    }
}
