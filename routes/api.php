<?php

declare(strict_types=1);

use App\Middleware\ApiAuthenticate;
use App\Middleware\ApiPermission;

/**
 * REST API route definitions.
 * Each entry: [METHOD, PATTERN, [Controller, action], [middleware...]]
 */

return [
    // ------------------------------------------------------------
    // Auth (public)
    // ------------------------------------------------------------
    ['POST', '/api/auth/login', ['App\Controllers\Api\AuthController', 'login']],
    ['POST', '/api/auth/logout', ['App\Controllers\Api\AuthController', 'logout'], [ApiAuthenticate::class]],
    ['GET', '/api/auth/me', ['App\Controllers\Api\AuthController', 'me'], [ApiAuthenticate::class]],

    // ------------------------------------------------------------
    // Dashboard
    // ------------------------------------------------------------
    ['GET', '/api/dashboard', ['App\Controllers\Api\DashboardController', 'index'], [ApiAuthenticate::class, [ApiPermission::class, 'dashboard.view']]],

    // ------------------------------------------------------------
    // Customers
    // ------------------------------------------------------------
    ['GET', '/api/customers', ['App\Controllers\Api\CustomerController', 'index'], [ApiAuthenticate::class, [ApiPermission::class, 'customers.view']]],
    ['GET', '/api/customers/search', ['App\Controllers\Api\CustomerController', 'search'], [ApiAuthenticate::class, [ApiPermission::class, 'customers.view']]],
    ['GET', '/api/customers/{id}', ['App\Controllers\Api\CustomerController', 'show'], [ApiAuthenticate::class, [ApiPermission::class, 'customers.view']]],
    ['POST', '/api/customers', ['App\Controllers\Api\CustomerController', 'store'], [ApiAuthenticate::class, [ApiPermission::class, 'customers.create']]],
    ['PUT', '/api/customers/{id}', ['App\Controllers\Api\CustomerController', 'update'], [ApiAuthenticate::class, [ApiPermission::class, 'customers.edit']]],
    ['POST', '/api/customers/{id}', ['App\Controllers\Api\CustomerController', 'update'], [ApiAuthenticate::class, [ApiPermission::class, 'customers.edit']]],
    ['DELETE', '/api/customers/{id}', ['App\Controllers\Api\CustomerController', 'destroy'], [ApiAuthenticate::class, [ApiPermission::class, 'customers.delete']]],
    ['POST', '/api/customers/{id}/notes', ['App\Controllers\Api\CustomerController', 'notes'], [ApiAuthenticate::class, [ApiPermission::class, 'customers.edit']]],
    ['POST', '/api/customers/{id}/packages', ['App\Controllers\Api\CustomerController', 'assignPackage'], [ApiAuthenticate::class, [ApiPermission::class, 'packages.create']]],

    // ------------------------------------------------------------
    // Packages
    // ------------------------------------------------------------
    ['GET', '/api/packages', ['App\Controllers\Api\PackageController', 'index'], [ApiAuthenticate::class, [ApiPermission::class, 'packages.view']]],
    ['GET', '/api/packages/{id}', ['App\Controllers\Api\PackageController', 'show'], [ApiAuthenticate::class, [ApiPermission::class, 'packages.view']]],
    ['POST', '/api/packages', ['App\Controllers\Api\PackageController', 'store'], [ApiAuthenticate::class, [ApiPermission::class, 'packages.create']]],
    ['PUT', '/api/packages/{id}', ['App\Controllers\Api\PackageController', 'update'], [ApiAuthenticate::class, [ApiPermission::class, 'packages.edit']]],
    ['POST', '/api/packages/{id}', ['App\Controllers\Api\PackageController', 'update'], [ApiAuthenticate::class, [ApiPermission::class, 'packages.edit']]],
    ['DELETE', '/api/packages/{id}', ['App\Controllers\Api\PackageController', 'destroy'], [ApiAuthenticate::class, [ApiPermission::class, 'packages.delete']]],

    // ------------------------------------------------------------
    // Services
    // ------------------------------------------------------------
    ['GET', '/api/services', ['App\Controllers\Api\ServiceController', 'index'], [ApiAuthenticate::class, [ApiPermission::class, 'services.view']]],
    ['GET', '/api/services/{id}', ['App\Controllers\Api\ServiceController', 'show'], [ApiAuthenticate::class, [ApiPermission::class, 'services.view']]],
    ['POST', '/api/services', ['App\Controllers\Api\ServiceController', 'store'], [ApiAuthenticate::class, [ApiPermission::class, 'services.create']]],
    ['PUT', '/api/services/{id}', ['App\Controllers\Api\ServiceController', 'update'], [ApiAuthenticate::class, [ApiPermission::class, 'services.edit']]],
    ['POST', '/api/services/{id}', ['App\Controllers\Api\ServiceController', 'update'], [ApiAuthenticate::class, [ApiPermission::class, 'services.edit']]],
    ['DELETE', '/api/services/{id}', ['App\Controllers\Api\ServiceController', 'destroy'], [ApiAuthenticate::class, [ApiPermission::class, 'services.delete']]],

    // ------------------------------------------------------------
    // Employees
    // ------------------------------------------------------------
    ['GET', '/api/employees', ['App\Controllers\Api\EmployeeController', 'index'], [ApiAuthenticate::class, [ApiPermission::class, 'employees.view']]],
    ['GET', '/api/employees/{id}', ['App\Controllers\Api\EmployeeController', 'show'], [ApiAuthenticate::class, [ApiPermission::class, 'employees.view']]],
    ['POST', '/api/employees', ['App\Controllers\Api\EmployeeController', 'store'], [ApiAuthenticate::class, [ApiPermission::class, 'employees.create']]],
    ['PUT', '/api/employees/{id}', ['App\Controllers\Api\EmployeeController', 'update'], [ApiAuthenticate::class, [ApiPermission::class, 'employees.edit']]],
    ['POST', '/api/employees/{id}', ['App\Controllers\Api\EmployeeController', 'update'], [ApiAuthenticate::class, [ApiPermission::class, 'employees.edit']]],
    ['DELETE', '/api/employees/{id}', ['App\Controllers\Api\EmployeeController', 'destroy'], [ApiAuthenticate::class, [ApiPermission::class, 'employees.delete']]],

    // ------------------------------------------------------------
    // Billing + Invoices
    // ------------------------------------------------------------
    ['GET', '/api/billing/options', ['App\Controllers\Api\BillingController', 'options'], [ApiAuthenticate::class, [ApiPermission::class, 'billing.view']]],
    ['GET', '/api/billing/customer/{id}', ['App\Controllers\Api\BillingController', 'customerData'], [ApiAuthenticate::class, [ApiPermission::class, 'billing.view']]],
    ['POST', '/api/billing/compute', ['App\Controllers\Api\BillingController', 'compute'], [ApiAuthenticate::class, [ApiPermission::class, 'billing.create']]],
    ['POST', '/api/billing/store', ['App\Controllers\Api\BillingController', 'store'], [ApiAuthenticate::class, [ApiPermission::class, 'billing.create']]],
    ['GET', '/api/billing/history', ['App\Controllers\Api\BillingController', 'history'], [ApiAuthenticate::class, [ApiPermission::class, 'billing.view']]],
    ['GET', '/api/billing/invoice/{id}', ['App\Controllers\Api\BillingController', 'invoice'], [ApiAuthenticate::class, [ApiPermission::class, 'billing.view']]],

    ['GET', '/api/invoices/{id}', ['App\Controllers\Api\InvoiceController', 'show'], [ApiAuthenticate::class, [ApiPermission::class, 'billing.view']]],
    ['POST', '/api/invoices/{id}/pay', ['App\Controllers\Api\InvoiceController', 'pay'], [ApiAuthenticate::class, [ApiPermission::class, 'billing.create']]],
    ['POST', '/api/invoices/{id}/cancel', ['App\Controllers\Api\InvoiceController', 'cancel'], [ApiAuthenticate::class, [ApiPermission::class, 'billing.create']]],

    // ------------------------------------------------------------
    // Reports
    // ------------------------------------------------------------
    ['GET', '/api/reports', ['App\Controllers\Api\ReportController', 'index'], [ApiAuthenticate::class, [ApiPermission::class, 'reports.view']]],

    // ------------------------------------------------------------
    // Settings
    // ------------------------------------------------------------
    ['GET', '/api/settings', ['App\Controllers\Api\SettingsController', 'index'], [ApiAuthenticate::class, [ApiPermission::class, 'settings.view']]],
    ['POST', '/api/settings', ['App\Controllers\Api\SettingsController', 'update'], [ApiAuthenticate::class, [ApiPermission::class, 'settings.edit']]],
    ['POST', '/api/settings/users', ['App\Controllers\Api\SettingsController', 'createUser'], [ApiAuthenticate::class, [ApiPermission::class, 'settings.edit']]],
    ['PUT', '/api/settings/users/{id}', ['App\Controllers\Api\SettingsController', 'updateUser'], [ApiAuthenticate::class, [ApiPermission::class, 'settings.edit']]],
    ['POST', '/api/settings/users/{id}', ['App\Controllers\Api\SettingsController', 'updateUser'], [ApiAuthenticate::class, [ApiPermission::class, 'settings.edit']]],
    ['DELETE', '/api/settings/users/{id}', ['App\Controllers\Api\SettingsController', 'deleteUser'], [ApiAuthenticate::class, [ApiPermission::class, 'settings.edit']]],
    ['POST', '/api/settings/roles', ['App\Controllers\Api\SettingsController', 'updateRolePermissions'], [ApiAuthenticate::class, [ApiPermission::class, 'settings.edit']]],

    // ------------------------------------------------------------
    // Profile
    // ------------------------------------------------------------
    ['GET', '/api/profile', ['App\Controllers\Api\ProfileController', 'index'], [ApiAuthenticate::class]],
    ['PUT', '/api/profile', ['App\Controllers\Api\ProfileController', 'update'], [ApiAuthenticate::class]],
    ['POST', '/api/profile', ['App\Controllers\Api\ProfileController', 'update'], [ApiAuthenticate::class]],
];
