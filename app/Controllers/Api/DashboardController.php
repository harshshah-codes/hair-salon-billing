<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\ReportRepository;
use App\Services\DashboardService;

final class DashboardController extends ApiController
{
    /**
     * GET /api/dashboard
     */
    public function index(): void
    {
        $service = new DashboardService(new ReportRepository());
        $this->ok([
            'cards' => $service->cards(),
            'revenue_chart' => $service->revenueChart(),
            'top_employees' => $service->employeePerformance(),
            'top_services' => $service->topServices(),
            'recent_bills' => $service->recentBills(),
            'low_balance_customers' => $service->lowBalanceCustomers(),
            'inactive_customers' => $service->inactiveCustomers(),
        ]);
    }
}
