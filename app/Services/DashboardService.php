<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ReportRepository;

final class DashboardService
{
    public function __construct(private ReportRepository $reports)
    {
    }

    public function cards(): array
    {
        return $this->reports->dashboardCards();
    }

    public function revenueChart(): array
    {
        $rows = $this->reports->revenueLastDays(30);

        $labels = [];
        $values = [];
        foreach ($rows as $row) {
            $labels[] = date('d M', strtotime((string)$row['day']));
            $values[] = (float)$row['revenue'];
        }

        return ['labels' => $labels, 'values' => $values];
    }

    public function employeePerformance(): array
    {
        return $this->reports->employeePerformance(6);
    }

    public function topServices(): array
    {
        return $this->reports->topServices(6);
    }

    public function recentBills(): array
    {
        return $this->reports->recentBills(8);
    }

    public function lowBalanceCustomers(): array
    {
        return $this->reports->lowBalanceCustomers(8);
    }

    public function inactiveCustomers(): array
    {
        return $this->reports->inactiveCustomers(8);
    }
}
