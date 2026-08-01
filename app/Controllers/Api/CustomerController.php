<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Validator;
use App\Models\Customer;
use App\Models\CustomerNote;
use App\Repositories\ActivityLogRepository;
use App\Repositories\CustomerPackageRepository;
use App\Repositories\CustomerRepository;
use App\Services\ActivityService;
use App\Services\CustomerService;

final class CustomerController extends ApiController
{
    private CustomerRepository $repo;
    private CustomerService $service;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new CustomerRepository();
        $this->service = new CustomerService(
            $this->repo,
            new ActivityService(new ActivityLogRepository()),
            new Customer(),
            new CustomerNote()
        );
    }

    /** GET /api/customers  ?search=&filter=&sort=&page=&per_page= */
    public function index(): void
    {
        $search = trim((string) $this->request->query('search', ''));
        $filter = (string) $this->request->query('filter', 'all');
        $sort = (string) $this->request->query('sort', 'created');
        $page = max(1, (int) $this->request->query('page', 1));
        $perPage = min(100, max(1, (int) $this->request->query('per_page', 20)));

        $this->ok($this->repo->listing($search, $filter, $sort, $page, $perPage));
    }

    /** GET /api/customers/{id} */
    public function show(int $id): void
    {
        $customer = $this->repo->find($id);
        if (!$customer) {
            $this->error('Customer not found.', 404);
        }

        $summary = $this->repo->summary($id);
        $customerPackages = new CustomerPackageRepository();

        $this->ok([
            'customer' => $customer,
            'summary' => $summary,
            'packages' => $customerPackages->forCustomer($id),
            'active_packages' => $customerPackages->activeFor($id),
            'invoices' => $this->repo->billingHistory($id, 1, 10),
            'ledger' => $this->repo->ledgerEntries($id, 1, 15),
            'recent_services' => $this->repo->recentServices($id, 8),
            'recent_invoices' => $this->repo->recentInvoices($id, 5),
            'notes' => $this->service->notes($id),
        ]);
    }

    /** GET /api/customers/search?q= */
    public function search(): void
    {
        $q = trim((string) $this->request->query('q', ''));
        if ($q === '') {
            $this->ok(['customers' => []]);
        }
        $this->ok(['customers' => $this->repo->search($q, 15)]);
    }

    /** POST /api/customers */
    public function store(): void
    {
        $data = $this->validateInput([
            'name' => 'required|max:120',
            'email' => 'nullable|email|max:190|unique:customers,email',
            'phone' => 'required|max:20',
            'address' => 'nullable|max:255',
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'notes' => 'nullable',
            'status' => 'in:active,inactive',
        ]);

        $data['mobile'] = $data['phone'];
        unset($data['phone']);
        $data['status'] = $data['status'] ?? 'active';

        $id = $this->service->create($data);
        $this->ok(['customer' => $this->repo->find($id)], 'Customer created successfully.');
    }

    /** PUT/POST /api/customers/{id} */
    public function update(int $id): void
    {
        if (!$this->repo->find($id)) {
            $this->error('Customer not found.', 404);
        }

        $data = $this->validateInput([
            'name' => 'required|max:120',
            'email' => 'nullable|email|max:190|unique:customers,email,' . $id,
            'phone' => 'required|max:20',
            'address' => 'nullable|max:255',
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'notes' => 'nullable',
            'status' => 'in:active,inactive',
        ]);

        $data['mobile'] = $data['phone'];
        unset($data['phone']);
        $data['status'] = $data['status'] ?? 'active';

        $this->service->update($id, $data);
        $this->ok(['customer' => $this->repo->find($id)], 'Customer updated successfully.');
    }

    /** DELETE /api/customers/{id} */
    public function destroy(int $id): void
    {
        if (!$this->repo->find($id)) {
            $this->error('Customer not found.', 404);
        }
        $this->service->delete($id);
        $this->ok(null, 'Customer deleted.');
    }

    /** POST /api/customers/{id}/notes */
    public function notes(int $id): void
    {
        if (!$this->repo->find($id)) {
            $this->error('Customer not found.', 404);
        }
        $note = trim((string) $this->request->input('note'));
        if ($note === '') {
            $this->error('Note cannot be empty.', 422);
        }
        $this->service->addNote($id, $note);
        $this->ok(['notes' => $this->service->notes($id)], 'Note added.');
    }

    /** POST /api/customers/{id}/packages — assign a package */
    public function assignPackage(int $id): void
    {
        if (!$this->repo->find($id)) {
            $this->error('Customer not found.', 404);
        }

        $data = $this->validateInput([
            'package_id' => 'nullable|integer|exists:packages,id',
            'name' => 'nullable|max:160',
            'price' => 'nullable|numeric|min:0',
            'credits' => 'nullable|integer|min:1',
            'validity_days' => 'nullable|integer|min:1|max:3650',
            'notes' => 'nullable|max:500',
        ]);

        $source = $this->request->input('source', $this->request->input('package_type', 'predefined'));
        $packageId = $source === 'custom' ? null : (int) ($data['package_id'] ?? 0);

        $custom = [
            'name' => $data['name'] ?? '',
            'price' => $data['price'] ?? 0,
            'credits' => $data['credits'] ?? 0,
            'validity_days' => $data['validity_days'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];

        try {
            $service = new \App\Services\CustomerPackageService(
                new CustomerPackageRepository(),
                new \App\Repositories\PackageRepository(),
                new \App\Models\CustomerPackage(),
                new \App\Models\CustomerPackageTransaction(),
                new ActivityService(new ActivityLogRepository())
            );
            $cpId = $service->assign($id, $packageId, $custom);
            $this->ok(['customer_package_id' => $cpId], 'Package assigned.');
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage(), 422);
        }
    }
}
