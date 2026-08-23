<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Customer;
use App\Models\CustomerPackage;
use App\Models\CustomerPackageTransaction;
use App\Repositories\ActivityLogRepository;
use App\Repositories\CustomerPackageRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\PackageRepository;
use App\Services\ActivityService;
use App\Services\CustomerPackageService;

final class CustomerPackageController extends Controller
{
    private CustomerPackageService $service;
    private CustomerRepository $customers;
    private PackageRepository $packages;

    public function __construct()
    {
        parent::__construct();
        $this->customers = new CustomerRepository();
        $this->packages = new PackageRepository();
        $this->service = new CustomerPackageService(
            new CustomerPackageRepository(),
            $this->packages,
            new CustomerPackage(),
            new CustomerPackageTransaction(),
            new ActivityService(new ActivityLogRepository())
        );
    }

    public function store(string $id): void
    {
        $customer = $this->customers->find((int)$id);
        if (!$customer) {
            $this->response->abort(404, 'Customer not found.');
        }

        $type = (string) $this->request->post('source', (string) $this->request->post('package_type', 'predefined'));
        $data = $this->request->only(['package_id', 'name', 'price', 'selling_price', 'credits', 'validity_days', 'notes', 'sold_by', 'starts_on']);
        $data['branch_address'] = \App\Core\Session::branch()['address'] ?? null;
        if (isset($data['selling_price']) && $data['selling_price'] !== '' && $data['selling_price'] !== null) {
            $data['price'] = $data['selling_price'];
        }
        unset($data['selling_price']);

        $errors = $this->validate($data, [
            'package_id'    => 'nullable|integer|exists:packages,id',
            'name'          => 'nullable|max:160',
            'price'         => 'nullable|numeric|min:0',
            'credits'       => 'nullable|integer|min:1',
            'validity_days' => 'nullable|integer|min:1|max:3650',
            'notes'         => 'nullable|max:500',
            'sold_by'       => 'required|integer|exists:employees,id',
            'starts_on'     => 'nullable|date',
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

        try {
            if ($type === 'custom') {
                $this->service->assign((int)$id, null, $data);
                $message = 'Custom package assigned successfully.';
            } else {
                if (empty($data['package_id'])) {
                    if ($this->request->isAjax()) {
                        $this->json(['success' => false, 'message' => 'Please select a package.'], 422);
                    }
                    $this->flash('danger', 'Please select a package.');
                    $this->back();
                }
                $this->service->assign((int)$id, (int)$data['package_id'], $data);
                $message = 'Package assigned successfully.';
            }
        } catch (\InvalidArgumentException $e) {
            if ($this->request->isAjax()) {
                $this->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            $this->flash('danger', $e->getMessage());
            $this->back();
        }

        if ($this->request->isAjax()) {
            $this->json(['success' => true, 'message' => $message]);
        }

        $this->flash('success', $message);
        $this->redirect('/customers/' . $id . '?tab=packages');
    }

    public function cancel(string $id): void
    {
        $reason = trim((string)$this->request->post('reason', ''));
        try {
            $this->service->cancel((int)$id, $reason);
            $this->flash('success', 'Package cancelled.');
        } catch (\InvalidArgumentException $e) {
            $this->flash('danger', $e->getMessage());
        }
        $this->back();
    }
}
