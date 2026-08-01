<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\Customer;
use App\Models\CustomerPackage;
use App\Models\CustomerPackageTransaction;
use App\Models\EmployeeAllocation;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Repositories\ActivityLogRepository;
use App\Repositories\CustomerPackageRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\InvoiceRepository;
use App\Repositories\ServiceRepository;
use App\Services\ActivityService;
use App\Services\BillingService;
use RuntimeException;

final class BillingController extends ApiController
{
    private function service(): BillingService
    {
        return new BillingService(
            new InvoiceRepository(),
            new CustomerPackageRepository(),
            new Invoice(),
            new InvoiceItem(),
            new EmployeeAllocation(),
            new Payment(),
            new LedgerEntry(),
            new CustomerPackage(),
            new CustomerPackageTransaction(),
            new Customer(),
            new ActivityService(new ActivityLogRepository())
        );
    }

    /** GET /api/billing/options — services, employees, default GST */
    public function options(): void
    {
        $this->ok([
            'services' => (new ServiceRepository())->active(),
            'employees' => (new EmployeeRepository())->active(),
            'gst_percent' => (float) setting('gst_percent', 18),
        ]);
    }

    /** GET /api/billing/customer/{id} — balance + active packages */
    public function customerData(int $id): void
    {
        $repo = new CustomerRepository();
        $customer = $repo->find($id);
        if (!$customer) {
            $this->error('Customer not found.', 404);
        }

        $summary = $repo->summary($id);
        $outstanding = max(0, round((float) ($summary['ledger_billed'] ?? 0) - (float) ($summary['ledger_paid'] ?? 0), 2));
        $packages = $this->db->fetchAll(
            "SELECT cp.`id`, cp.`name`, cp.`remaining_credits`, cp.`credits`, cp.`selling_price`,
                cp.`value_per_credit`, cp.`expires_on`
             FROM customer_packages cp
             WHERE cp.`customer_id` = ? AND cp.`status` = 'active' AND cp.`deleted_at` IS NULL
               AND (cp.`expires_on` IS NULL OR cp.`expires_on` >= CURDATE())
             ORDER BY cp.`id` DESC",
            [$id]
        );

        $balanceValue = 0.0;
        foreach ($packages as $pkg) {
            $vpc = (float) ($pkg['value_per_credit'] ?? 0);
            if ($vpc <= 0 && (float) ($pkg['credits'] ?? 0) > 0) {
                $vpc = (float) ($pkg['selling_price'] ?? 0) / (float) $pkg['credits'];
            }
            $balanceValue += (float) ($pkg['remaining_credits'] ?? 0) * $vpc;
        }

        $this->ok([
            'customer' => [
                'id' => (int) $customer['id'],
                'name' => $customer['name'],
                'mobile' => $customer['mobile'],
                'email' => $customer['email'],
                'photo' => $customer['photo'],
                'outstanding' => $outstanding,
                'credits' => round($balanceValue, 2),
                'last_visit' => $customer['last_visit_at'],
                'packages' => $packages,
            ],
        ]);
    }

    /**
     * POST /api/billing/compute — preview a bill without saving.
     * Accepts the structured payload used by BillingService::compute.
     */
    public function compute(): void
    {
        $payload = $this->normalizePayload();
        try {
            $result = $this->service()->compute($payload);
            $this->ok($result);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage(), 422);
        }
    }

    /**
     * POST /api/billing/store — create a bill.
     * Structured JSON: {customer_id, items:[{service_id,name,price,qty,allocations:[{employee_id,amount}]}],
     *  discount, gst_percent, package_used, payments:[{method,amount,reference}], notes, draft}
     * Also accepts the flat form arrays (items_name[0], items_price[0], ...) used by the web POS.
     */
    public function store(): void
    {
        $draft = (bool) $this->request->input('draft', false);
        $payload = $this->normalizePayload();

        try {
            $invoiceId = $this->service()->createInvoice($payload, $draft ? 'draft' : 'final');
            $this->ok([
                'invoice_id' => $invoiceId,
                'invoice_number' => (new InvoiceRepository())->find($invoiceId)['invoice_number'] ?? null,
            ], $draft ? 'Draft saved.' : 'Invoice generated successfully.');
        } catch (RuntimeException $e) {
            $this->error($e->getMessage(), 422);
        }
    }

    /** GET /api/billing/history ?search=&status=&page= */
    public function history(): void
    {
        $search = trim((string) $this->request->query('search', ''));
        $status = (string) $this->request->query('status', 'all');
        $page = max(1, (int) $this->request->query('page', 1));
        $perPage = min(100, max(1, (int) $this->request->query('per_page', 20)));

        $repo = new InvoiceRepository();
        $result = $repo->listing($search, $status, $page, $perPage);
        $this->ok($result);
    }

    /** GET /api/billing/invoice/{id} */
    public function invoice(int $id): void
    {
        $repo = new InvoiceRepository();
        $invoice = $repo->findWithDetails($id);
        if (!$invoice) {
            $this->error('Invoice not found.', 404);
        }
        $this->ok([
            'invoice' => $invoice,
            'items' => $repo->items($id),
            'payments' => $repo->payments($id),
            'package_transactions' => $repo->packageTransactions($id),
        ]);
    }

    /**
     * Normalize request input into the payload shape expected by BillingService.
     * Handles both structured JSON (items/payments arrays) and flat form arrays.
     */
    private function normalizePayload(): array
    {
        $json = $this->request->isJson() ? $this->request->json() : [];

        if (isset($json['items']) && is_array($json['items'])) {
            // Structured payload — pass through (compute() also reads discount_type/discount_value,
            // but POS sends discount/gst_percent; bridge them here).
            $payload = $json;
            if (!isset($payload['discount_type']) && isset($payload['discount'])) {
                $payload['discount_type'] = 'flat';
                $payload['discount_value'] = $payload['discount'];
            }
            if (!isset($payload['gst_rate']) && isset($payload['gst_percent'])) {
                $payload['gst_rate'] = $payload['gst_percent'];
            }
            if (!isset($payload['package_usage']) && isset($payload['package_used'])) {
                $payload['package_usage'] = ['amount' => $payload['package_used']];
            }
            return $payload;
        }

        // Flat form-style arrays
        $items = [];
        $names = $this->request->input('items_name', []);
        $prices = $this->request->input('items_price', []);
        $qtys = $this->request->input('items_qty', []);
        $services = $this->request->input('items_service', []);
        $allocEmp = $this->request->input('alloc_employee', []);
        $allocAmount = $this->request->input('alloc_amount', []);

        $count = max(count($names), count($services));
        for ($i = 0; $i < $count; $i++) {
            if (empty($services[$i]) && empty($names[$i])) {
                continue;
            }
            $item = [
                'service_id' => (int) ($services[$i] ?? 0),
                'name' => (string) ($names[$i] ?? ''),
                'price' => (float) ($prices[$i] ?? 0),
                'qty' => max(1, (int) ($qtys[$i] ?? 1)),
                'allocations' => [],
            ];
            if (isset($allocEmp[$i]) && is_array($allocEmp[$i])) {
                foreach ($allocEmp[$i] as $j => $empId) {
                    $item['allocations'][] = [
                        'employee_id' => (int) $empId,
                        'amount' => (float) ($allocAmount[$i][$j] ?? 0),
                    ];
                }
            }
            $items[] = $item;
        }

        $payments = [];
        $payMethods = $this->request->input('pay_method', []);
        $payAmounts = $this->request->input('pay_amount', []);
        $payRefs = $this->request->input('pay_reference', []);
        $pCount = max(count($payMethods), count($payAmounts));
        for ($i = 0; $i < $pCount; $i++) {
            $payments[] = [
                'method' => (string) ($payMethods[$i] ?? 'cash'),
                'amount' => (float) ($payAmounts[$i] ?? 0),
                'reference' => (string) ($payRefs[$i] ?? ''),
            ];
        }

        return [
            'customer_id' => (int) $this->request->input('customer_id'),
            'items' => $items,
            'discount_type' => 'flat',
            'discount_value' => (float) $this->request->input('discount', 0),
            'gst_rate' => (float) $this->request->input('gst_percent', setting('gst_percent', 18)),
            'package_used' => (float) $this->request->input('package_used', 0),
            'payments' => $payments,
            'notes' => (string) $this->request->input('notes'),
        ];
    }
}
