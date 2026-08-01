<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\EmployeeRepository;

class EmployeeController extends Controller
{
    public function index(): void
    {
        $repo = new EmployeeRepository();
        $search = trim((string) $this->request->query('search', ''));
        $page = max(1, (int) $this->request->query('page', 1));
        $status = (string) $this->request->query('status', '');

        $result = $repo->listing($search, $status, $page, 15);

        $revenue = [];
        foreach ($result['items'] as $emp) {
            $revenue[$emp['id']] = [
                'revenue' => (float) ($emp['revenue'] ?? 0),
                'services' => (int) ($emp['services_completed'] ?? 0),
            ];
        }

        $this->render('employees.index', [
            'title' => 'Employees',
            'active' => 'employees',
            'employees' => $result['items'],
            'revenue' => $revenue,
            'paginator' => $result,
            'search' => $search,
            'status' => $status,
            'breadcrumbs' => ['Employees' => '/employees'],
            'scripts' => ['js/pages/employees.js'],
        ]);
    }

    public function show(int $id): void
    {
        $repo = new EmployeeRepository();
        $employee = $repo->find($id);
        if (!$employee) {
            $this->response->abort(404, 'Employee not found');
        }

        $raw = $repo->stats($id);
        $stats = [
            'revenue' => (float) ($raw['revenue_generated'] ?? 0),
            'customers' => (int) ($raw['customers_served'] ?? 0),
            'services' => (int) ($raw['services_completed'] ?? 0),
            'today' => (float) ($raw['today_earnings'] ?? 0),
            'invoices' => (int) ($raw['total_bills'] ?? 0),
        ];

        $recent = $this->db->fetchAll(
            "SELECT ea.`amount`, i.`id` AS invoice_id, i.`invoice_number`, i.`invoice_date`, ii.`description` AS service,
                c.`name` AS customer_name
             FROM employee_allocations ea
             JOIN invoices i ON i.id = ea.invoice_id AND i.deleted_at IS NULL AND i.status != 'cancelled'
             JOIN invoice_items ii ON ii.id = ea.invoice_item_id
             JOIN customers c ON c.id = i.customer_id
             WHERE ea.employee_id = ? ORDER BY ea.id DESC LIMIT 20",
            [$id]
        );

        $earningsSeries = array_map(
            static fn ($r) => ['date' => $r['day'], 'total' => (float) ($r['earnings'] ?? 0)],
            $repo->earningsSeries($id, date('Y-m-d', strtotime('-29 days')), date('Y-m-d'))
        );

        $monthly = $this->db->fetchAll(
            "SELECT DATE_FORMAT(i.`invoice_date`, '%Y-%m') AS month, COALESCE(SUM(ea.`amount`), 0) AS total
             FROM employee_allocations ea
             JOIN invoices i ON i.id = ea.invoice_id AND i.deleted_at IS NULL AND i.status != 'cancelled'
             WHERE ea.employee_id = ?
             GROUP BY month ORDER BY month DESC LIMIT 12",
            [$id]
        );

        $servicesCount = $this->db->fetchAll(
            "SELECT ii.`description` AS service, COUNT(*) AS count, COALESCE(SUM(ea.`amount`), 0) AS total
             FROM employee_allocations ea
             JOIN invoice_items ii ON ii.id = ea.invoice_item_id
             JOIN invoices i ON i.id = ea.invoice_id AND i.deleted_at IS NULL AND i.status != 'cancelled'
             WHERE ea.employee_id = ? GROUP BY ii.description ORDER BY count DESC LIMIT 10",
            [$id]
        );

        $recent = $repo->recentServices($id);

        $this->render('employees.show', [
            'title' => $employee['name'],
            'active' => 'employees',
            'employee' => $employee,
            'stats' => $stats,
            'recent' => $recent,
            'earningsSeries' => $earningsSeries,
            'monthly' => $monthly,
            'servicesCount' => $servicesCount,
            'breadcrumbs' => ['Employees' => '/employees', $employee['name'] => '/employees/' . $id],
            'scripts' => ['js/pages/employee-show.js'],
        ]);
    }

    public function create(): void
    {
        $this->view->render('employees.partials._form', ['employee' => null], 'plain');
    }

    public function edit(int $id): void
    {
        $employee = (new EmployeeRepository())->find($id);
        if (!$employee) {
            $this->response->abort(404, 'Employee not found');
        }
        $this->view->render('employees.partials._form', ['employee' => $employee], 'plain');
    }

    public function store(): void
    {
        $id = (int) $this->request->input('id', 0);
        $repo = new EmployeeRepository();

        $data = [
            'name' => trim((string) $this->request->input('name')),
            'mobile' => trim((string) $this->request->input('mobile')),
            'email' => trim((string) $this->request->input('email')) ?: null,
            'designation' => trim((string) $this->request->input('designation')) ?: null,
            'commission_rate' => round((float) $this->request->input('commission_rate', 0), 2),
            'status' => $this->request->input('status', 'active') === 'inactive' ? 'inactive' : 'active',
            'joined_at' => $this->request->input('joined_at') ?: null,
        ];

        $errors = $this->validate($data, [
            'name' => 'required|max:160',
            'mobile' => 'required|max:20|unique:employees,mobile,' . ($id ?: ''),
            'email' => 'nullable|email|max:160',
            'designation' => 'nullable|max:120',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'joined_at' => 'nullable|date',
        ]);
        if ($errors) {
            $this->json(['success' => false, 'errors' => $errors], 422);
        }

        $file = $this->request->file('photo');
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $photo = $this->savePhoto($file, $id ? $repo->find($id)['photo'] ?? null : null);
            if ($photo) {
                $data['photo'] = $photo;
            }
        }

        if ($id) {
            $repo->update($id, $data);
            $message = 'Employee updated successfully.';
        } else {
            $id = $repo->create($data);
            $message = 'Employee added successfully.';
        }
        $this->logActivity('employees.save', "Saved employee: {$data['name']}");
        $this->json(['success' => true, 'message' => $message, 'id' => $id]);
    }

    public function update(int $id): void
    {
        $this->request->setPostValue('id', $id);
        $this->store();
    }

    public function destroy(int $id): void
    {
        $repo = new EmployeeRepository();
        $employee = $repo->find($id);
        if (!$employee) {
            $this->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }
        $repo->delete($id);
        $this->logActivity('employees.delete', "Deleted employee: {$employee['name']}");
        $this->json(['success' => true, 'message' => 'Employee deleted.']);
    }

    private function savePhoto(array $file, ?string $existing = null): ?string
    {
        $allowed = App\Core\App::config('uploads.allowed');
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true) || $file['size'] > App\Core\App::config('uploads.max_size')) {
            return null;
        }
        $dir = App\Core\App::config('uploads.dir') . '/employees';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = 'emp_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
            return null;
        }
        if ($existing) {
            $old = $dir . '/' . basename($existing);
            if (is_file($old)) {
                @unlink($old);
            }
        }
        return 'employees/' . $name;
    }
}
