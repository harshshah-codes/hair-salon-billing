<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\PdfExport;
use App\Repositories\InvoiceRepository;
use App\Repositories\PaymentRepository;
use App\Services\InvoiceService;

final class InvoiceController extends Controller
{
    private InvoiceRepository $repo;
    private InvoiceService $service;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new InvoiceRepository();
        $this->service = new InvoiceService($this->repo, new PaymentRepository());
    }

    public function index(): void
    {
        $search = trim((string)$this->request->query('search', ''));
        $status = (string)$this->request->query('status', 'all');
        $page = max(1, (int)$this->request->query('page', 1));

        $result = $this->repo->listing($search, $status, $page, 20);

        $this->view('billing/history', [
            'pageTitle'   => 'Invoice History',
            'active'      => 'billing',
            'breadcrumbs' => ['Billing' => '/billing', 'Invoice History' => ''],
            'invoices'    => $result['items'],
            'paginator'   => $result,
            'search'      => $search,
            'status'      => $status === 'all' ? '' : $status,
        ]);
    }

    public function show(int $id): void
    {
        $invoice = $this->repo->findWithDetails($id);
        if (!$invoice) {
            $this->response->abort(404, 'Invoice not found.');
        }

        $this->view('billing/invoice', [
            'pageTitle'          => 'Invoice ' . $invoice['invoice_number'],
            'active'             => 'billing',
            'breadcrumbs'        => ['Billing' => '/billing', 'Invoice History' => '/billing/history', $invoice['invoice_number'] => ''],
            'invoice'            => $invoice,
            'items'              => $this->repo->items($id),
            'payments'           => $this->repo->payments($id),
            'packageTransactions' => $this->repo->packageTransactions($id),
            'print'              => false,
        ]);
    }

    public function printView(int $id): void
    {
        $invoice = $this->repo->findWithDetails($id);
        if (!$invoice) {
            $this->response->abort(404, 'Invoice not found.');
        }

        echo view('billing/invoice', [
            'invoice'             => $invoice,
            'items'               => $this->repo->items($id),
            'payments'            => $this->repo->payments($id),
            'packageTransactions' => $this->repo->packageTransactions($id),
            'print'               => true,
        ], 'plain');
        exit;
    }

    public function pdf(int $id): void
    {
        $invoice = $this->repo->findWithDetails($id);
        if (!$invoice) {
            $this->response->abort(404, 'Invoice not found.');
        }

        $pdf = new PdfExport('P', 'A4', 'Invoice ' . $invoice['invoice_number']);
        $pdf->addPage();

        $pdf->setFont('helvetica', 12, 'B');
        $pdf->cell(0, 8, (string)setting('business_name', 'Nirav Hair Storm'), 0, 1, 'L');
        $pdf->setFont('helvetica', 10, '');
        $pdf->cell(0, 6, (string)setting('business_address', ''), 0, 1, 'L');
        $pdf->cell(0, 6, (string)setting('business_phone', ''), 0, 1, 'L');
        $pdf->ln(4);

        $pdf->setFont('helvetica', 11, 'B');
        $pdf->cell(0, 7, 'INVOICE ' . $invoice['invoice_number'], 0, 1, 'R');
        $pdf->setFont('helvetica', 10, '');
        $pdf->cell(0, 6, 'Date: ' . format_date($invoice['invoice_date']), 0, 1, 'R');
        $pdf->ln(4);

        $pdf->cell(0, 6, 'Bill To:', 0, 1);
        $pdf->setFont('helvetica', 10, 'B');
        $pdf->cell(0, 6, $invoice['customer_name'], 0, 1);
        $pdf->setFont('helvetica', 10, '');
        $pdf->cell(0, 6, 'Mobile: ' . $invoice['customer_phone'], 0, 1);
        if ($invoice['customer_address']) {
            $pdf->cell(0, 6, 'Address: ' . $invoice['customer_address'], 0, 1);
        }
        $pdf->ln(4);

        $rows = [];
        foreach ($this->repo->items($id) as $item) {
            $rows[] = [
                $item['description'],
                (string)$item['qty'],
                number_format((float)$item['price'], 2),
                number_format((float)$item['amount'], 2),
            ];
        }
        $pdf->table(['Description', 'Qty', 'Price', 'Amount'], $rows, [95, 20, 35, 35]);
        $pdf->ln(4);

        $pdf->cell(0, 6, 'Subtotal: ' . money($invoice['subtotal']), 0, 1, 'R');
        if ((float)$invoice['discount'] > 0) {
            $pdf->cell(0, 6, 'Discount: -' . money($invoice['discount']), 0, 1, 'R');
        }
        if ((float)$invoice['gst_amount'] > 0) {
            $pdf->cell(0, 6, 'GST (' . (float)$invoice['gst_percent'] . '%): ' . money($invoice['gst_amount']), 0, 1, 'R');
        }
        if ((float)$invoice['package_used'] > 0) {
            $pdf->cell(0, 6, 'Package Credits: -' . money($invoice['package_used']), 0, 1, 'R');
        }
        $pdf->setFont('helvetica', 11, 'B');
        $pdf->cell(0, 7, 'Total Payable: ' . money($invoice['payable']), 0, 1, 'R');
        $pdf->setFont('helvetica', 10, '');
        $pdf->cell(0, 6, 'Paid: ' . money($invoice['paid']), 0, 1, 'R');
        $pdf->cell(0, 6, 'Balance Due: ' . money($invoice['balance']), 0, 1, 'R');

        $pdf->download('invoice-' . $invoice['invoice_number'] . '.pdf');
    }

    public function recordPayment(int $id): void
    {
        $input = $this->request->only(['amount', 'method', 'reference']);
        $result = $this->service->recordPayment((int)$id, $input);

        if (!$result['success']) {
            $this->flash('danger', $result['message']);
        } else {
            $this->flash('success', $result['message']);
        }
        $this->back();
    }

    public function cancel(int $id): void
    {
        $result = $this->service->cancel((int)$id);
        if (!$result['success']) {
            $this->flash('danger', $result['message']);
        } else {
            $this->flash('success', $result['message']);
        }
        $this->back();
    }
}
