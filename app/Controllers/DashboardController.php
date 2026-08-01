<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\ReportRepository;
use App\Services\ActivityService;
use App\Services\DashboardService;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $service = new DashboardService(new ReportRepository());
        $activity = new ActivityService(new \App\Repositories\ActivityLogRepository());

        $this->view('dashboard/index', [
            'pageTitle'       => 'Dashboard',
            'active'          => 'dashboard',
            'breadcrumbs'     => [],
            'cards'           => $service->cards(),
            'revenueChart'    => $service->revenueChart(),
            'employeeTop'     => $service->employeePerformance(),
            'topServices'     => $service->topServices(),
            'recentBills'     => $service->recentBills(),
            'lowBalance'      => $service->lowBalanceCustomers(),
            'inactiveCustomers' => $service->inactiveCustomers(),
            'activities'      => $activity->recent(8),
        ]);
    }
}
