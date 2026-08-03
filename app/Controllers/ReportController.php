<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\ReportRepository;
use App\Services\ReportService;

final class ReportController extends Controller
{
    private ReportService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ReportService(
            new ReportRepository(),
            new \App\Repositories\EmployeeRepository(),
            new \App\Repositories\CustomerRepository(),
            new \App\Repositories\ServiceRepository(),
            new \App\Repositories\PackageRepository()
        );
    }

    public function index(): void
    {
        $type = (string)$this->request->query('type', 'revenue');
        $from = (string)$this->request->query('from', date('Y-m-01'));
        $to   = (string)$this->request->query('to', date('Y-m-d'));

        $filters = [
            'from' => $from,
            'to'   => $to,
            'employee_id' => $this->request->query('employee_id'),
            'customer_id' => $this->request->query('customer_id'),
            'service_id'  => $this->request->query('service_id'),
        ];

        $data = $this->service->dataset($type, $filters);

        $this->view('reports/index', array_merge($data, [
            'pageTitle'    => 'Reports',
            'active'       => 'reports',
            'breadcrumbs'  => ['Reports' => '/reports'],
            'options'      => $this->service->filterOptions(),
            'query'        => $filters,
        ]));
    }

    public function export(): void
    {
        $format = (string)$this->request->post('format', 'csv');
        $type   = (string)$this->request->post('type', 'revenue');

        $filters = [
            'from'        => (string)$this->request->post('from', date('Y-m-01')),
            'to'          => (string)$this->request->post('to', date('Y-m-d')),
            'employee_id' => $this->request->post('employee_id'),
            'customer_id' => $this->request->post('customer_id'),
            'service_id'  => $this->request->post('service_id'),
        ];

        $data = $this->service->dataset($type, $filters);

        if ($format === 'print') {
            echo view('reports/print', array_merge($data, ['pageTitle' => 'Report']), 'plain');
            exit;
        }

        $this->streamCsv($type, $data);
    }

    private function streamCsv(string $type, array $data): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="report-' . $type . '-' . date('Ymd-His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

        $rows = [];

        switch ($type) {
            case 'revenue':
                $rows = [['Date', 'Revenue', 'Package Used', 'Bills']];
                foreach ($data['series'] as $row) {
                    $rows[] = [$row['day'], $row['revenue'], $row['package_used'], $row['bills']];
                }
                break;
            case 'package_sales':
                $rows = [['Package', 'Units Sold', 'Total Value']];
                foreach ($data['rows'] as $row) {
                    $rows[] = [$row['package_name'], $row['units'], $row['total_sold']];
                }
                break;
            case 'employee_earnings':
                $rows = [['Employee', 'Role', 'Earnings', 'Services', 'Customers']];
                foreach ($data['rows'] as $row) {
                    $rows[] = [$row['name'], $row['role'], $row['earnings'], $row['services'], $row['customers']];
                }
                break;
            case 'service_revenue':
                $rows = [['Service', 'Revenue', 'Quantity', 'Customers']];
                foreach ($data['rows'] as $row) {
                    $rows[] = [$row['service_name'], $row['revenue'], $row['qty'], $row['customers']];
                }
                break;
            case 'outstanding':
                $rows = [['Customer', 'Phone', 'Package', 'Outstanding', 'Last Visit']];
                foreach ($data['rows'] as $row) {
                    $rows[] = [$row['name'], $row['phone'], $row['current_package'], $row['outstanding'], $row['last_visit_at']];
                }
                break;
            case 'statements':
                $rows = [['Date', 'Type', 'Description', 'Services', 'By', 'Amount', 'Balance']];
                foreach ($data['rows'] as $row) {
                    $balance = (float) ($row['wallet_balance'] ?? 0);
                    $amount  = (float) $row['amount'];
                    $isCredit = !empty($row['is_credit']);
                    $desc = $row['package_name'] ?? ($row['invoice_number'] ?? '');
                    if ($row['type'] === 'debit') {
                        $desc = ($row['services'] ?: 'Transaction ' . ($row['invoice_number'] ?? ''));
                    } elseif (!empty($row['services'])) {
                        $desc = trim($row['services']) . ' — ' . $desc;
                    }
                    $rows[] = [
                        $row['created_at'],
                        $row['type'],
                        $desc,
                        $row['services'] ?? '',
                        $row['employees'] ?? '',
                        ($isCredit ? '+' : '-') . number_format(abs($amount), 2),
                        ($balance > 0 ? '+' : ($balance < 0 ? '-' : '')) . number_format(abs($balance), 2),
                    ];
                }
                break;
        }

        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }
}
