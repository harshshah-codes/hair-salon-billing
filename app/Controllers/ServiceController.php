<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\ServiceRepository;

class ServiceController extends Controller
{
    public function index(): void
    {
        $repo = new ServiceRepository();
        $search = trim((string) $this->request->query('search', ''));
        $page = max(1, (int) $this->request->query('page', 1));
        $category = (string) $this->request->query('category', '');
        $status = (string) $this->request->query('status', '');

        $result = $repo->listing($search, $status, $category, $page, 15);

        $this->render('services.index', [
            'title' => 'Services',
            'active' => 'services',
            'services' => $result['items'],
            'paginator' => $result,
            'categories' => $repo->categories(),
            'search' => $search,
            'category' => $category,
            'status' => $status,
            'breadcrumbs' => ['Services' => '/services'],
            'scripts' => ['js/pages/services.js'],
        ]);
    }

    public function create(): void
    {
        $this->view->render('services.partials._form', [
            'service' => null,
            'categories' => (new ServiceRepository())->categories(),
        ], 'plain');
    }

    public function store(): void
    {
        $data = [
            'name' => trim((string) $this->request->input('name')),
            'category' => trim((string) $this->request->input('category')) ?: null,
            'duration_minutes' => (int) $this->request->input('duration_minutes', 30),
            'price' => round((float) $this->request->input('price', 0), 2),
            'description' => trim((string) $this->request->input('description')) ?: null,
            'status' => $this->request->input('status', 'active') === 'inactive' ? 'inactive' : 'active',
        ];

        $errors = $this->validate($data, [
            'name' => 'required|max:160',
            'category' => 'nullable|max:100',
            'duration_minutes' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);
        if ($errors) {
            $this->json(['success' => false, 'errors' => $errors], 422);
        }

        $repo = new ServiceRepository();
        $id = $repo->create($data);
        $this->logActivity('services.create', "Created service: {$data['name']}");
        $this->json(['success' => true, 'message' => 'Service created successfully.', 'id' => $id]);
    }

    public function edit(int $id): void
    {
        $service = (new ServiceRepository())->find($id);
        if (!$service) {
            $this->response->abort(404, 'Service not found');
        }
        $this->view->render('services.partials._form', [
            'service' => $service,
            'categories' => (new ServiceRepository())->categories(),
        ], 'plain');
    }

    public function update(int $id): void
    {
        $data = [
            'name' => trim((string) $this->request->input('name')),
            'category' => trim((string) $this->request->input('category')) ?: null,
            'duration_minutes' => (int) $this->request->input('duration_minutes', 30),
            'price' => round((float) $this->request->input('price', 0), 2),
            'description' => trim((string) $this->request->input('description')) ?: null,
            'status' => $this->request->input('status', 'active') === 'inactive' ? 'inactive' : 'active',
        ];

        $errors = $this->validate($data, [
            'name' => 'required|max:160',
            'category' => 'nullable|max:100',
            'duration_minutes' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);
        if ($errors) {
            $this->json(['success' => false, 'errors' => $errors], 422);
        }

        (new ServiceRepository())->update($id, $data);
        $this->logActivity('services.update', "Updated service: {$data['name']}");
        $this->json(['success' => true, 'message' => 'Service updated successfully.']);
    }

    public function destroy(int $id): void
    {
        $repo = new ServiceRepository();
        $service = $repo->find($id);
        if (!$service) {
            $this->json(['success' => false, 'message' => 'Service not found.'], 404);
        }
        $repo->delete($id);
        $this->logActivity('services.delete', "Deleted service: {$service['name']}");
        $this->json(['success' => true, 'message' => 'Service deleted.']);
    }
}
