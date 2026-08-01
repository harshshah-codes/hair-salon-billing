<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CustomerRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\PackageRepository;
use App\Repositories\ReportRepository;
use App\Repositories\ServiceRepository;

final class ReportService
{
    public function __construct(
        private ReportRepository $reports,
        private EmployeeRepository $employees,
        private CustomerRepository $customers,
        private ServiceRepository $services,
        private PackageRepository $packages
    ) {
    }

    /** Build the complete dataset for a given report type + filters. */
    public function dataset(string $type, array $filters): array
    {
        $from = (string)($filters['from'] ?? date('Y-m-01'));
        $to   = (string)($filters['to'] ?? date('Y-m-d'));

        $employeeId = isset($filters['employee_id']) ? (int)$filters['employee_id'] : null;
        $customerId = isset($filters['customer_id']) ? (int)$filters['customer_id'] : null;
        $serviceId  = isset($filters['service_id'])  ? (int)$filters['service_id']  : null;

        $data = ['type' => $type, 'from' => $from, 'to' => $to, 'filters' => $filters];

        switch ($type) {
            case 'revenue':
                $series = $this->reports->revenueSeries($from, $to);
                $data['series'] = $series;
                $data['totals'] = $this->reports->revenueTotals($from, $to);
                $data['detail'] = $this->reports->filteredRevenue($from, $to, $employeeId, $serviceId, $customerId);
                break;

            case 'package_sales':
                $data['rows'] = $this->reports->packageSales($from, $to);
                break;

            case 'employee_earnings':
                $data['rows'] = $this->reports->employeeEarnings($from, $to, $employeeId);
                break;

            case 'service_revenue':
                $data['rows'] = $this->reports->serviceRevenue($from, $to, $serviceId);
                break;

            case 'outstanding':
                $data['rows'] = $this->reports->outstanding();
                break;

            case 'statements':
                $data['rows'] = $customerId ? $this->reports->customerStatement($customerId, $from, $to) : [];
                break;
        }

        return $data;
    }

    public function filterOptions(): array
    {
        return [
            'employees' => $this->employees->active(),
            'customers' => $this->customers->search('', 500),
            'services'  => $this->services->search('', 500),
            'packages'  => $this->packages->active(),
        ];
    }
}
