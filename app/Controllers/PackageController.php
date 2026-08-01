<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\PackageRepository;

class PackageController extends Controller
{
    public function index(): void
    {
        $repo = new PackageRepository();
        $search = trim((string) $this->request->query('search', ''));
        $page = max(1, (int) $this->request->query('page', 1));
        $status = (string) $this->request->query('status', '');

        $result = $repo->listing($search, $status, $page, 15);

        $usage = [];
        foreach ($result['items'] as $package) {
            $usage[$package['id']] = (int) ($package['customers_using'] ?? 0);
        }

        $this->render('packages.index', [
            'title' => 'Packages',
            'active' => 'packages',
            'packages' => $result['items'],
            'usage' => $usage,
            'paginator' => $result,
            'search' => $search,
            'status' => $status,
            'breadcrumbs' => ['Packages' => '/packages'],
            'scripts' => ['js/pages/packages.js'],
        ]);
    }

    public function create(): void
    {
        $this->view->render('packages.partials._form', ['package' => null], 'plain');
    }

    public function store(): void
    {
        $data = [
            'name' => trim((string) $this->request->input('name')),
            'selling_price' => round((float) $this->request->input('selling_price', 0), 2),
            'credits' => (int) $this->request->input('credits', 0),
            'validity_days' => (int) $this->request->input('validity_days', 30),
            'description' => trim((string) $this->request->input('description')) ?: null,
            'status' => $this->request->input('status', 'active') === 'inactive' ? 'inactive' : 'active',
        ];

        $errors = $this->validate($data, [
            'name' => 'required|max:160',
            'selling_price' => 'required|numeric|min:0',
            'credits' => 'required|integer|min:1',
            'validity_days' => 'required|integer|min:1',
        ]);
        if ($errors) {
            $this->json(['success' => false, 'errors' => $errors], 422);
        }

        $repo = new PackageRepository();
        $id = $repo->create($data);
        $this->logActivity('packages.create', "Created package: {$data['name']}");
        $this->json(['success' => true, 'message' => 'Package created successfully.', 'id' => $id]);
    }

    public function edit(int $id): void
    {
        $package = (new PackageRepository())->find($id);
        if (!$package) {
            $this->response->abort(404, 'Package not found');
        }
        $this->view->render('packages.partials._form', ['package' => $package], 'plain');
    }

    public function update(int $id): void
    {
        $data = [
            'name' => trim((string) $this->request->input('name')),
            'selling_price' => round((float) $this->request->input('selling_price', 0), 2),
            'credits' => (int) $this->request->input('credits', 0),
            'validity_days' => (int) $this->request->input('validity_days', 30),
            'description' => trim((string) $this->request->input('description')) ?: null,
            'status' => $this->request->input('status', 'active') === 'inactive' ? 'inactive' : 'active',
        ];

        $errors = $this->validate($data, [
            'name' => 'required|max:160',
            'selling_price' => 'required|numeric|min:0',
            'credits' => 'required|integer|min:1',
            'validity_days' => 'required|integer|min:1',
        ]);
        if ($errors) {
            $this->json(['success' => false, 'errors' => $errors], 422);
        }

        $repo = new PackageRepository();
        $repo->update($id, $data);
        $this->logActivity('packages.update', "Updated package: {$data['name']}");
        $this->json(['success' => true, 'message' => 'Package updated successfully.']);
    }

    public function destroy(int $id): void
    {
        $repo = new PackageRepository();
        $package = $repo->find($id);
        if (!$package) {
            $this->json(['success' => false, 'message' => 'Package not found.'], 404);
        }
        $repo->delete($id);
        $this->logActivity('packages.delete', "Deleted package: {$package['name']}");
        $this->json(['success' => true, 'message' => 'Package deleted.']);
    }
}
