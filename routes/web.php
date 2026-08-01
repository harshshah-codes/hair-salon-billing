<?php

declare(strict_types=1);

use App\Middleware\Authenticate;
use App\Middleware\Guest;
use App\Middleware\Permission;

/**
 * Route definitions.
 * Each entry: [METHOD, PATTERN, [Controller, action], [middleware...]]
 */

return [
    // ------------------------------------------------------------
    // Auth
    // ------------------------------------------------------------
    ['GET', '/', ['App\Controllers\DashboardController', 'index'], [Authenticate::class]],
    ['GET', '/auth/login', ['App\Controllers\AuthController', 'login'], [Guest::class]],
    ['POST', '/auth/login', ['App\Controllers\AuthController', 'authenticate'], [Guest::class]],
    ['POST', '/auth/logout', ['App\Controllers\AuthController', 'logout'], [Authenticate::class]],

    // ------------------------------------------------------------
    // Dashboard
    // ------------------------------------------------------------
    ['GET', '/dashboard', ['App\Controllers\DashboardController', 'index'], [Authenticate::class]],

    // ------------------------------------------------------------
    // Customers
    // ------------------------------------------------------------
    ['GET', '/customers', ['App\Controllers\CustomerController', 'index'], [Authenticate::class, [Permission::class, 'customers.view']]],
    ['GET', '/customers/create', ['App\Controllers\CustomerController', 'create'], [Authenticate::class, [Permission::class, 'customers.create']]],
    ['POST', '/customers', ['App\Controllers\CustomerController', 'store'], [Authenticate::class, [Permission::class, 'customers.create']]],
    ['GET', '/customers/{id}', ['App\Controllers\CustomerController', 'show'], [Authenticate::class, [Permission::class, 'customers.view']]],
    ['GET', '/customers/{id}/edit', ['App\Controllers\CustomerController', 'edit'], [Authenticate::class, [Permission::class, 'customers.edit']]],
    ['POST', '/customers/{id}/update', ['App\Controllers\CustomerController', 'update'], [Authenticate::class, [Permission::class, 'customers.edit']]],
    ['POST', '/customers/{id}/delete', ['App\Controllers\CustomerController', 'destroy'], [Authenticate::class, [Permission::class, 'customers.delete']]],
    ['POST', '/customers/{id}/notes', ['App\Controllers\CustomerController', 'notes'], [Authenticate::class]],
    ['GET', '/customers/ajax/search', ['App\Controllers\CustomerController', 'search'], [Authenticate::class]],

    // ------------------------------------------------------------
    // Packages (Package Manager)
    // ------------------------------------------------------------
    ['GET', '/packages', ['App\Controllers\PackageController', 'index'], [Authenticate::class, [Permission::class, 'packages.view']]],
    ['GET', '/packages/create', ['App\Controllers\PackageController', 'create'], [Authenticate::class, [Permission::class, 'packages.create']]],
    ['POST', '/packages', ['App\Controllers\PackageController', 'store'], [Authenticate::class, [Permission::class, 'packages.create']]],
    ['GET', '/packages/{id}/edit', ['App\Controllers\PackageController', 'edit'], [Authenticate::class, [Permission::class, 'packages.edit']]],
    ['POST', '/packages/{id}/update', ['App\Controllers\PackageController', 'update'], [Authenticate::class, [Permission::class, 'packages.edit']]],
    ['POST', '/packages/{id}/delete', ['App\Controllers\PackageController', 'destroy'], [Authenticate::class, [Permission::class, 'packages.delete']]],

    // Customer package assignment
    ['POST', '/customers/{id}/packages', ['App\Controllers\CustomerPackageController', 'store'], [Authenticate::class, [Permission::class, 'packages.create']]],
    ['POST', '/customers/{id}/packages/cancel', ['App\Controllers\CustomerPackageController', 'cancel'], [Authenticate::class, [Permission::class, 'packages.create']]],

    // ------------------------------------------------------------
    // Services
    // ------------------------------------------------------------
    ['GET', '/services', ['App\Controllers\ServiceController', 'index'], [Authenticate::class, [Permission::class, 'services.view']]],
    ['GET', '/services/create', ['App\Controllers\ServiceController', 'create'], [Authenticate::class, [Permission::class, 'services.create']]],
    ['POST', '/services', ['App\Controllers\ServiceController', 'store'], [Authenticate::class, [Permission::class, 'services.create']]],
    ['GET', '/services/{id}/edit', ['App\Controllers\ServiceController', 'edit'], [Authenticate::class, [Permission::class, 'services.edit']]],
    ['POST', '/services/{id}/update', ['App\Controllers\ServiceController', 'update'], [Authenticate::class, [Permission::class, 'services.edit']]],
    ['POST', '/services/{id}/delete', ['App\Controllers\ServiceController', 'destroy'], [Authenticate::class, [Permission::class, 'services.delete']]],

    // ------------------------------------------------------------
    // Employees
    // ------------------------------------------------------------
    ['GET', '/employees', ['App\Controllers\EmployeeController', 'index'], [Authenticate::class, [Permission::class, 'employees.view']]],
    ['GET', '/employees/create', ['App\Controllers\EmployeeController', 'create'], [Authenticate::class, [Permission::class, 'employees.create']]],
    ['POST', '/employees', ['App\Controllers\EmployeeController', 'store'], [Authenticate::class, [Permission::class, 'employees.create']]],
    ['GET', '/employees/{id}', ['App\Controllers\EmployeeController', 'show'], [Authenticate::class, [Permission::class, 'employees.view']]],
    ['GET', '/employees/{id}/edit', ['App\Controllers\EmployeeController', 'edit'], [Authenticate::class, [Permission::class, 'employees.edit']]],
    ['POST', '/employees/{id}/update', ['App\Controllers\EmployeeController', 'update'], [Authenticate::class, [Permission::class, 'employees.edit']]],
    ['POST', '/employees/{id}/delete', ['App\Controllers\EmployeeController', 'destroy'], [Authenticate::class, [Permission::class, 'employees.delete']]],

    // ------------------------------------------------------------
    // Billing (POS)
    // ------------------------------------------------------------
    ['GET', '/billing', ['App\Controllers\BillingController', 'index'], [Authenticate::class, [Permission::class, 'billing.view']]],
    ['POST', '/billing/store', ['App\Controllers\BillingController', 'store'], [Authenticate::class, [Permission::class, 'billing.create']]],
    ['GET', '/billing/customer/{id}', ['App\Controllers\BillingController', 'customerData'], [Authenticate::class]],
    ['GET', '/billing/history', ['App\Controllers\InvoiceController', 'index'], [Authenticate::class, [Permission::class, 'billing.view']]],
    ['GET', '/billing/invoice/{id}', ['App\Controllers\InvoiceController', 'show'], [Authenticate::class, [Permission::class, 'billing.view']]],
    ['GET', '/billing/invoice/{id}/print', ['App\Controllers\InvoiceController', 'printView'], [Authenticate::class, [Permission::class, 'billing.view']]],
    ['GET', '/billing/invoice/{id}/pdf', ['App\Controllers\InvoiceController', 'pdf'], [Authenticate::class, [Permission::class, 'billing.view']]],
    ['POST', '/invoices/{id}/pay', ['App\Controllers\InvoiceController', 'recordPayment'], [Authenticate::class, [Permission::class, 'billing.create']]],
    ['POST', '/invoices/{id}/cancel', ['App\Controllers\InvoiceController', 'cancel'], [Authenticate::class, [Permission::class, 'billing.create']]],

    // ------------------------------------------------------------
    // Reports
    // ------------------------------------------------------------
    ['GET', '/reports', ['App\Controllers\ReportController', 'index'], [Authenticate::class, [Permission::class, 'reports.view']]],
    ['POST', '/reports/export', ['App\Controllers\ReportController', 'export'], [Authenticate::class, [Permission::class, 'reports.view']]],

    // ------------------------------------------------------------
    // Settings
    // ------------------------------------------------------------
    ['GET', '/settings', ['App\Controllers\SettingsController', 'index'], [Authenticate::class, [Permission::class, 'settings.view']]],
    ['POST', '/settings', ['App\Controllers\SettingsController', 'update'], [Authenticate::class, [Permission::class, 'settings.edit']]],
    ['POST', '/settings/theme', ['App\Controllers\SettingsController', 'theme'], [Authenticate::class]],
    ['GET', '/settings/backup', ['App\Controllers\SettingsController', 'backup'], [Authenticate::class, [Permission::class, 'settings.edit']]],
    ['GET', '/settings/activity', ['App\Controllers\SettingsController', 'activity'], [Authenticate::class]],

    // ------------------------------------------------------------
    // Profile
    // ------------------------------------------------------------
    ['GET', '/profile', ['App\Controllers\ProfileController', 'index'], [Authenticate::class]],
    ['POST', '/profile', ['App\Controllers\ProfileController', 'update'], [Authenticate::class]],
];
