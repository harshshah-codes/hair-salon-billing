<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Customer;
use App\Models\CustomerNote;
use App\Repositories\ActivityLogRepository;
use App\Repositories\CustomerPackageRepository;
use App\Repositories\CustomerRepository;
use App\Services\ActivityService;
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
        $page    = max(1, (int)$this->request->query('page', 1));
        $perPage = 20;

        $pagination = $this->repo->listing($search, $filter, $sort, $page, $perPage);

        $this->view('customers/index', [
            'pageTitle'  => 'Customers',
            'active'     => 'customers',
            'breadcrumbs' => ['Customers' => '/customers'],
            'pagination' => $pagination,
            'search'     => $search,
            'filter'     => $filter,
            'sort'       => $sort,
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
        if ($errors) {
            $this->session->setErrors($errors);
            $this->session->setOld($data);
            $this->flash('danger', 'Please fix the highlighted fields and try again.');
            $this->back();
        }

        $data['mobile'] = trim((string) ($data['phone'] ?? ''));
        unset($data['phone']);

        $id = $this->service->create($data);
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

        $this->view('customers/show', [
            'pageTitle'      => $customer['name'],
            'active'         => 'customers',
            'breadcrumbs'    => ['Customers' => '/customers', $customer['name'] => ''],
            'customer'       => $customer,
            'summary'        => $summary,
            'packages'       => $packages,
            'billingHistory' => $billingHistory,
            'ledger'         => $ledger,
            'recentServices' => $recentServices,
            'recentInvoices' => $recentInvoices,
            'notes'          => $notes,
            'tab'            => $tab,
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
            $this->json(['results' => []]);
        }
        $this->json(['results' => $this->repo->search($q, 10)]);
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
