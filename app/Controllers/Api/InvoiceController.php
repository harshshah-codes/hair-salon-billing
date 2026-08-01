<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\InvoiceRepository;
use App\Repositories\PaymentRepository;
use App\Services\BillingService;
use App\Services\InvoiceService;
use RuntimeException;

final class InvoiceController extends ApiController
{
    private InvoiceRepository $repo;
    private InvoiceService $service;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new InvoiceRepository();
        $this->service = new InvoiceService($this->repo, new PaymentRepository());
    }

    /** GET /api/invoices/{id} */
    public function show(int $id): void
    {
        $invoice = $this->repo->findWithDetails($id);
        if (!$invoice) {
            $this->error('Invoice not found.', 404);
        }
        $this->ok([
            'invoice' => $invoice,
            'items' => $this->repo->items($id),
            'payments' => $this->repo->payments($id),
            'package_transactions' => $this->repo->packageTransactions($id),
        ]);
    }

    /** POST /api/invoices/{id}/pay  { amount, method, reference } */
    public function pay(int $id): void
    {
        $data = $this->validateInput([
            'amount' => 'required|numeric|min:0.01',
            'method' => 'in:cash,card,upi,bank,other',
            'reference' => 'nullable|max:100',
        ]);

        $result = $this->service->recordPayment($id, [
            'amount' => (float) ($data['amount'] ?? 0),
            'method' => (string) ($data['method'] ?? 'cash'),
            'reference' => (string) ($data['reference'] ?? ''),
        ]);

        if (!$result['success']) {
            $this->error($result['message'], 422);
        }

        $this->ok(['invoice' => $this->repo->findWithDetails($id)], $result['message']);
    }

    /** POST /api/invoices/{id}/cancel */
    public function cancel(int $id): void
    {
        $invoice = $this->repo->findWithDetails($id);
        if (!$invoice) {
            $this->error('Invoice not found.', 404);
        }
        if ($invoice['status'] === 'cancelled') {
            $this->error('This invoice is already cancelled.', 422);
        }

        $billing = new BillingService(
            $this->repo,
            new \App\Repositories\CustomerPackageRepository(),
            new \App\Models\Invoice(),
            new \App\Models\InvoiceItem(),
            new \App\Models\EmployeeAllocation(),
            new \App\Models\Payment(),
            new \App\Models\LedgerEntry(),
            new \App\Models\CustomerPackage(),
            new \App\Models\CustomerPackageTransaction(),
            new \App\Models\Customer(),
            new \App\Services\ActivityService(new \App\Repositories\ActivityLogRepository())
        );

        try {
            $billing->void($id);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage(), 422);
        }

        $this->ok(['invoice' => $this->repo->findWithDetails($id)], 'Invoice cancelled.');
    }
}
