<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
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
use App\Services\ActivityService;
use App\Services\BillingService;
use RuntimeException;

class BillingController extends Controller
{
    public function index(): void
    {
        $this->render('billing.index', [
            'title' => 'New Transaction',
            'active' => 'billing',
            'employees' => (new EmployeeRepository())->active(),
            'templates' => (new \App\Repositories\PackageRepository())->active(),
            'preselectCustomerId' => (int) $this->request->query('customer_id', 0),
            'breadcrumbs' => ['Billing' => '/billing'],
            'scripts' => ['js/create-customer.js', 'js/pages/billing.js'],
        ]);
    }

    public function customerData(int $id): void
    {
        $repo = new CustomerRepository();
        $customer = $repo->find($id);
        if (!$customer) {
            $this->json(['success' => false, 'message' => 'Customer not found.'], 404);
        }

        $summary = $repo->summary($id);
        $outstanding = max(0, round((float)($summary['ledger_billed'] ?? 0) - (float)($summary['ledger_paid'] ?? 0), 2));
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
            $balanceValue += (float) ($pkg['remaining_credits'] ?? 0);
        }

        $this->json([
            'success' => true,
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

    public function store(): void
    {
        $draft = (bool) $this->request->input('draft', false);

        // Normalize nested items arrays
        $items = [];
        $names = $this->request->input('items_name', []);
        $prices = $this->request->input('items_price', []);
        $qtys = $this->request->input('items_qty', []);
        $dates = $this->request->input('items_date', []);
        $allocEmp = $this->request->input('alloc_employee', []);
        $allocAmount = $this->request->input('alloc_amount', []);

        $count = count($names);
        for ($i = 0; $i < $count; $i++) {
            if (empty($names[$i])) {
                continue;
            }
            $item = [
                'service_id' => null,
                'name' => (string) ($names[$i] ?? ''),
                'price' => (float) ($prices[$i] ?? 0),
                'qty' => max(1, (int) ($qtys[$i] ?? 1)),
                'date' => !empty($dates[$i]) ? (string) $dates[$i] : date('Y-m-d'),
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

        $payload = [
            'customer_id' => (int) $this->request->input('customer_id'),
            'items' => $items,
            'package_used' => (float) $this->request->input('package_used', 0),
            'payments' => [],
            'notes' => (string) $this->request->input('notes'),
        ];

        $service = new BillingService(
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

        try {
            $invoiceId = $service->createInvoice($payload, $draft ? 'draft' : 'final');

            $response = [
                'success' => true,
                'message' => $draft ? 'Draft saved.' : 'Transaction created successfully.',
                'invoice_id' => $invoiceId,
                'customer_id' => (int) $payload['customer_id'],
            ];

            $this->json($response);
        } catch (RuntimeException $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
