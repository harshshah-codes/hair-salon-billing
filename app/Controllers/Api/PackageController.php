<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\PackageRepository;

final class PackageController extends ApiController
{
    private PackageRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new PackageRepository();
    }

    /** GET /api/packages ?search=&status=&page= */
    public function index(): void
    {
        $search = trim((string) $this->request->query('search', ''));
        $status = (string) $this->request->query('status', 'all');
        $page = max(1, (int) $this->request->query('page', 1));
        $perPage = min(100, max(1, (int) $this->request->query('per_page', 15)));

        $this->ok($this->repo->listing($search, $status, $page, $perPage));
    }

    /** GET /api/packages/{id} */
    public function show(int $id): void
    {
        $package = $this->repo->find($id);
        if (!$package) {
            $this->error('Package not found.', 404);
        }
        $this->ok(['package' => $package]);
    }

    /** POST /api/packages */
    public function store(): void
    {
        $data = $this->validateInput([
            'name' => 'required|max:160',
            'selling_price' => 'required|numeric|min:0',
            'credits' => 'required|integer|min:1',
            'validity_days' => 'required|integer|min:1',
            'description' => 'nullable',
            'status' => 'in:active,inactive',
        ]);

        $data['status'] = $data['status'] ?? 'active';
        $id = $this->repo->create($data);
        $this->logActivity('packages.create', 'Package created: ' . $data['name']);
        $this->ok(['id' => $id, 'package' => $this->repo->find($id)], 'Package created successfully.');
    }

    /** PUT/POST /api/packages/{id} */
    public function update(int $id): void
    {
        if (!$this->repo->find($id)) {
            $this->error('Package not found.', 404);
        }
        $data = $this->validateInput([
            'name' => 'required|max:160',
            'selling_price' => 'required|numeric|min:0',
            'credits' => 'required|integer|min:1',
            'validity_days' => 'required|integer|min:1',
            'description' => 'nullable',
            'status' => 'in:active,inactive',
        ]);

        $data['status'] = $data['status'] ?? 'active';
        $this->repo->update($id, $data);
        $this->logActivity('packages.update', 'Package updated: ' . $data['name']);
        $this->ok(['package' => $this->repo->find($id)], 'Package updated successfully.');
    }

    /** DELETE /api/packages/{id} */
    public function destroy(int $id): void
    {
        if (!$this->repo->find($id)) {
            $this->error('Package not found.', 404);
        }
        $this->repo->delete($id);
        $this->logActivity('packages.delete', 'Package deleted: #' . $id);
        $this->ok(null, 'Package deleted.');
    }
}
