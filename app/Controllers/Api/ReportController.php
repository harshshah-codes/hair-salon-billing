<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\CustomerRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\PackageRepository;
use App\Repositories\ReportRepository;
use App\Repositories\ServiceRepository;
use App\Services\ReportService;

final class ReportController extends ApiController
{
    /** GET /api/reports?type=&from=&to=&employee_id=&customer_id=&service_id= */
    public function index(): void
    {
        $type = (string) $this->request->query('type', 'revenue');
        $valid = ['revenue', 'package_sales', 'employee_earnings', 'service_revenue', 'outstanding', 'statements'];
        if (!in_array($type, $valid, true)) {
            $this->error('Invalid report type.', 422);
        }

        $filters = [
            'from' => (string) $this->request->query('from', date('Y-m-01')),
            'to' => (string) $this->request->query('to', date('Y-m-d')),
            'employee_id' => $this->request->query('employee_id') !== null ? (int) $this->request->query('employee_id') : null,
            'customer_id' => $this->request->query('customer_id') !== null ? (int) $this->request->query('customer_id') : null,
            'service_id' => $this->request->query('service_id') !== null ? (int) $this->request->query('service_id') : null,
        ];

        if ($type === 'statements' && $filters['customer_id'] === null) {
            $this->error('customer_id is required for statements reports.', 422);
        }

        $service = new ReportService(
            new ReportRepository(),
            new EmployeeRepository(),
            new CustomerRepository(),
            new ServiceRepository(),
            new PackageRepository()
        );

        $this->ok($service->dataset($type, $filters));
    }
}
