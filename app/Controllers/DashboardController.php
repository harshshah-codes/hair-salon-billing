<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\EmployeeRepository;
use App\Repositories\PackageRepository;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $this->view('dashboard/index', [
            'pageTitle'   => 'Dashboard',
            'active'      => 'dashboard',
            'breadcrumbs' => [],
            'employees'   => (new EmployeeRepository())->active(),
            'templates'   => (new PackageRepository())->active(),
            'scripts'     => ['js/create-customer.js', 'js/pages/dashboard.js'],
        ]);
    }
}
