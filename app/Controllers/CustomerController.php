<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Customer;
use App\Models\CustomerNote;
use App\Repositories\ActivityLogRepository;
use App\Repositories\CustomerPackageRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\PackageRepository;
use App\Services\ActivityService;
use App\Services\CustomerPackageService;
use App\Services\CustomerService;

final class CustomerController extends Controller
{
    private CustomerRepository $repo;
    private CustomerService $service;
    private CustomerPackageRepository $customerPackages;

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
        $this->customerPackages = new CustomerPackageRepository();
    }

    public function index(): void
    {
        $search  = trim((string)$this->request->query('search', ''));
        $filter  = (string)$this->request->query('filter', 'all');
        $sort    = (string)$this->request->query('sort', 'created');
        $branch  = max(0, (int)$this->request->query('branch', 0));
        $page    = max(1, (int)$this->request->query('page', 1));
        $perPage = 20;

        $pagination = $this->repo->listing($search, $filter, $sort, $page, $perPage, $branch ?: null);

        $this->view('customers/index', [
            'pageTitle'  => 'Customers',
            'active'     => 'customers',
            'breadcrumbs' => ['Customers' => '/customers'],
            'pagination' => $pagination,
            'search'     => $search,
            'filter'     => $filter,
            'sort'       => $sort,
            'branch'     => $branch,
            'branches'   => (new \App\Repositories\BranchRepository())->active(),
        ]);
    }

    public function create(): void
    {
        $this->view('customers/form', [
            'pageTitle'   => 'Add Customer',
            'active'      => 'customers',
            'breadcrumbs' => ['Customers' => '/customers', 'Add Customer' => ''],
            'customer'    => null,
        ]);
    }

    public function store(): void
    {
        $data = $this->request->only(['name', 'email', 'phone', 'address', 'dob', 'gender', 'notes', 'status']);
        $data['status'] = $data['status'] ?? 'active';

        $errors = $this->validate($data, [
            'name'    => 'required|max:120',
            'email'   => 'nullable|email|max:190|unique:customers,email',
            'phone'   => 'required|max:20',
            'address' => 'nullable|max:255',
            'dob'     => 'nullable|date',
            'gender'  => 'nullable|in:male,female,other',
            'notes'   => 'nullable',
            'status'  => 'in:active,inactive',
        ]);

        // Optional package purchase (sold_by required whenever a package is sold)
        $packageName = trim((string) $this->request->input('package_name', ''));
        $packagePrice = (float) $this->request->input('package_price', 0);
        $packageCredits = (int) $this->request->input('package_credits', 0);
        $packageSoldBy = (int) $this->request->input('package_sold_by', 0);
        $wantsPackage = $packageName !== '' || $packagePrice > 0 || $packageCredits > 0;
        if ($wantsPackage && $packageSoldBy <= 0) {
            $errors['package_sold_by'] = 'Select the staff member who sold this package.';
        }

        if ($errors) {
            if ($this->request->isAjax()) {
                $this->json(['success' => false, 'errors' => $errors], 422);
            }
            $this->session->setErrors($errors);
            $this->session->setOld($data);
            $this->flash('danger', 'Please fix the highlighted fields and try again.');
            $this->back();
        }

        $data['mobile'] = trim((string) ($data['phone'] ?? ''));
        unset($data['phone']);

        $id = $this->service->create($data);

        if ($wantsPackage) {
            $branch = \App\Core\Session::branch();
            $packageData = [
                'name'            => $packageName,
                'price'           => $packagePrice,
                'credits'         => $packageCredits,
                'validity_days'   => (string) $this->request->input('package_validity_days', '') !== ''
                    ? (int) $this->request->input('package_validity_days')
                    : null,
                'notes'           => (string) $this->request->input('package_notes', ''),
                'sold_by'         => $packageSoldBy,
                'starts_on'       => (string) $this->request->input('package_purchase_date', ''),
                'branch_address'  => $branch['address'] ?? null,
            ];
            (new CustomerPackageService(
                new CustomerPackageRepository(),
                new PackageRepository(),
                new \App\Models\CustomerPackage(),
                new \App\Models\CustomerPackageTransaction(),
                new ActivityService(new ActivityLogRepository())
            ))->assign((int)$id, null, $packageData);
        }

        if ($this->request->isAjax()) {
            $customer = $this->repo->find((int)$id);
            $this->json([
                'success' => true,
                'message' => 'Customer created successfully.',
                'customer' => $customer ? [
                    'id' => (int)$customer['id'],
                    'name' => $customer['name'],
                    'mobile' => $customer['mobile'],
                    'email' => $customer['email'],
                    'photo' => $customer['photo'],
                    'outstanding' => 0,
                    'credits' => 0.0,
                    'last_visit' => $customer['last_visit_at'],
                ] : null,
            ]);
        }

        $this->flash('success', 'Customer created successfully.');
        $this->redirect('/customers/' . $id);
    }

    public function show(string $id): void
    {
        $customer = $this->repo->find((int)$id);
        if (!$customer) {
            $this->response->abort(404, 'Customer not found.');
        }

        $tab = (string)$this->request->query('tab', 'overview');
        $page = max(1, (int)$this->request->query('page', 1));

        $summary = $this->repo->summary((int)$id);
        $packages = $this->customerPackages->forCustomer((int)$id);
        $billingHistory = $this->repo->billingHistory((int)$id, $page, 10);
        $ledger = $this->repo->ledgerEntries((int)$id, $page, 15);
        $recentServices = $this->repo->recentServices((int)$id, 8);
        $recentInvoices = $this->repo->recentInvoices((int)$id, 5);
        $notes = $this->service->notes((int)$id);

        foreach ($packages as &$pkg) {
            $pkg['balance_value'] = round((float) ($pkg['remaining_credits'] ?? 0) * (float) ($pkg['value_per_credit'] ?? 0), 2);
            $pkg['days_left'] = $pkg['expires_on']
                ? (int) floor((strtotime($pkg['expires_on']) - time()) / 86400)
                : 999;
        }
        unset($pkg);

        $stats = [
            'lifetime_spend' => round((float) ($summary['ledger_billed'] ?? 0), 2),
            'visits'         => (int) ($summary['total_visits'] ?? 0),
            'credits'        => round((float) ($summary['current_credits'] ?? 0), 2),
            'outstanding'    => max(0, round((float) ($summary['ledger_billed'] ?? 0) - (float) ($summary['ledger_paid'] ?? 0), 2)),
        ];

        $this->view('customers/show', [
            'pageTitle'      => $customer['name'],
            'active'         => 'customers',
            'breadcrumbs'    => ['Customers' => '/customers', $customer['name'] => ''],
            'customer'       => $customer,
            'summary'        => $summary,
            'stats'          => $stats,
            'allPackages'    => $packages,
            'activePackages' => array_values(array_filter($packages, static fn ($p) => ($p['status'] ?? '') === 'active')),
            'invoices'       => $billingHistory['items'] ?? [],
            'templates'      => (new PackageRepository())->active(),
            'employees'      => (new \App\Repositories\EmployeeRepository())->active(),
            'billingHistory' => $billingHistory,
            'ledger'         => $ledger['items'] ?? [],
            'recentServices' => $recentServices,
            'recentInvoices' => $recentInvoices,
            'notes'          => $notes,
            'tab'            => $tab,
            'scripts'        => ['js/pages/customer-show.js'],
        ]);
    }

    public function edit(string $id): void
    {
        $customer = $this->repo->find((int)$id);
        if (!$customer) {
            $this->response->abort(404, 'Customer not found.');
        }
        $this->view('customers/form', [
            'pageTitle'   => 'Edit Customer',
            'active'      => 'customers',
            'breadcrumbs' => ['Customers' => '/customers', $customer['name'] => '/customers/' . $id, 'Edit' => ''],
            'customer'    => $customer,
        ]);
    }

    public function update(string $id): void
    {
        $customer = $this->repo->find((int)$id);
        if (!$customer) {
            $this->response->abort(404, 'Customer not found.');
        }

        $data = $this->request->only(['name', 'email', 'phone', 'address', 'dob', 'gender', 'notes', 'status']);

        $errors = $this->validate($data, [
            'name'    => 'required|max:120',
            'email'   => 'nullable|email|max:190|unique:customers,email,' . $id,
            'phone'   => 'required|max:20',
            'address' => 'nullable|max:255',
            'dob'     => 'nullable|date',
            'gender'  => 'nullable|in:male,female,other',
            'notes'   => 'nullable',
            'status'  => 'in:active,inactive',
        ]);
        if ($errors) {
            if ($this->request->isAjax()) {
                $this->json(['success' => false, 'errors' => $errors], 422);
            }
            $this->session->setErrors($errors);
            $this->session->setOld($data);
            $this->flash('danger', 'Please fix the highlighted fields and try again.');
            $this->back();
        }

        $data['mobile'] = trim((string) ($data['phone'] ?? ''));
        unset($data['phone']);
        $data['status'] = $data['status'] ?? 'active';

        $this->service->update((int)$id, $data);

        if ($this->request->isAjax()) {
            $this->json(['success' => true, 'message' => 'Customer updated successfully.']);
        }

        $this->flash('success', 'Customer updated successfully.');
        $this->redirect('/customers/' . $id);
    }

    public function destroy(string $id): void
    {
        $customer = $this->repo->find((int)$id);
        if (!$customer) {
            $this->response->abort(404, 'Customer not found.');
        }
        $this->service->delete((int)$id);
        $this->flash('success', 'Customer deleted.');
        $this->redirect('/customers');
    }

    public function notes(string $id): void
    {
        $customer = $this->repo->find((int)$id);
        if (!$customer) {
            $this->response->abort(404, 'Customer not found.');
        }

        $note = trim((string)$this->request->post('note', ''));
        if ($note === '') {
            $this->json(['success' => false, 'message' => 'Note cannot be empty.'], 422);
        }

        $this->service->addNote((int)$id, $note);
        $this->json(['success' => true, 'message' => 'Note added.']);
    }

    /* ---------------------------------------------------------
     * JSON endpoints
     * ------------------------------------------------------- */

    public function search(): void
    {
        $q = trim((string)$this->request->query('q', ''));
        if ($q === '') {
            $this->json(['success' => true, 'customers' => []]);
        }
        $this->json(['success' => true, 'customers' => $this->repo->search($q, 10)]);
    }

    public function showJson(string $id): void
    {
        $customer = $this->repo->summary((int)$id);
        if (!$customer) {
            $this->json(['message' => 'Customer not found.'], 404);
        }
        $customer['packages'] = $this->customerPackages->activeFor((int)$id);
        $this->json($customer);
    }
}
