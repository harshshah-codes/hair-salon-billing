<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

final class EmployeeRepository extends BaseRepository
{
    protected string $table = 'employees';

    public function listing(string $search = '', string $status = 'all', int $page = 1, int $perPage = 20): array
    {
        $where  = ['e.deleted_at IS NULL'];
        $params = [];

        if ($status === 'active' || $status === 'inactive') {
            $where[] = 'e.status = ?';
            $params[] = $status;
        }

        if (trim($search) !== '') {
            $where[] = '(e.name LIKE ? OR e.mobile LIKE ? OR e.designation LIKE ?)';
            $params[] = '%' . trim($search) . '%';
            $params[] = '%' . trim($search) . '%';
            $params[] = '%' . trim($search) . '%';
        }

        $settled = "i.status IN ('issued','paid','partially_paid')";
        $whereSql = implode(' AND ', $where);
        $countSql = "SELECT COUNT(*) FROM employees e WHERE {$whereSql}";
        $selectSql = "SELECT e.*,
                        COALESCE(SUM(CASE WHEN {$settled} THEN a.amount END), 0) AS revenue,
                        COUNT(DISTINCT CASE WHEN {$settled} THEN a.invoice_item_id END) AS services_completed
                      FROM employees e
                      LEFT JOIN employee_allocations a ON a.employee_id = e.id
                      LEFT JOIN invoices i ON i.id = a.invoice_id
                      WHERE {$whereSql}
                      GROUP BY e.id ORDER BY e.created_at DESC";

        return $this->paginateQuery($countSql, $selectSql, $params, $page, $perPage);
    }

    public function active(): array
    {
        return $this->db->query(
            "SELECT * FROM employees WHERE status='active' AND deleted_at IS NULL ORDER BY name ASC"
        )->fetchAll();
    }

    /** Statistics for the employee profile page. */
    public function stats(int $employeeId): array
    {
        $settled = "i.status IN ('issued','paid','partially_paid')";
        $stmt = $this->db->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN {$settled} THEN a.amount END), 0) AS revenue_generated,
                COUNT(DISTINCT CASE WHEN {$settled} THEN i.customer_id END) AS customers_served,
                COUNT(DISTINCT CASE WHEN {$settled} THEN a.invoice_item_id END) AS services_completed,
                COALESCE(SUM(CASE WHEN {$settled} AND DATE(i.created_at) = CURDATE() THEN a.amount END), 0) AS today_earnings,
                COALESCE(SUM(CASE WHEN {$settled} AND DATE(i.created_at) = CURDATE() THEN 1 END), 0) AS today_services,
                (SELECT COUNT(DISTINCT a3.invoice_id) FROM employee_allocations a3
                  JOIN invoices i3 ON i3.id = a3.invoice_id
                  WHERE a3.employee_id = ? AND i3.status IN ('issued','paid','partially_paid')) AS total_bills
             FROM employee_allocations a
             LEFT JOIN invoices i ON i.id = a.invoice_id
             WHERE a.employee_id = ?"
        );
        $stmt->execute([$employeeId, $employeeId]);
        return $stmt->fetch() ?: [];
    }

    /** Monthly earnings series for the earnings chart. */
    public function earningsSeries(int $employeeId, string $from, string $to): array
    {
        $settled = "i.status IN ('issued','paid','partially_paid')";
        $stmt = $this->db->prepare(
            "SELECT DATE(i.created_at) AS day, COALESCE(SUM(a.amount),0) AS earnings
             FROM employee_allocations a
             JOIN invoices i ON i.id = a.invoice_id AND {$settled}
             WHERE a.employee_id = ? AND DATE(i.created_at) BETWEEN ? AND ?
             GROUP BY DATE(i.created_at) ORDER BY day ASC"
        );
        $stmt->execute([$employeeId, $from, $to]);
        return $stmt->fetchAll();
    }

    public function recentServices(int $employeeId, int $limit = 15): array
    {
        $settled = "i.status IN ('issued','paid','partially_paid')";
        $stmt = $this->db->prepare(
            "SELECT a.amount, i.id AS invoice_id, i.invoice_number, i.invoice_date,
                    ii.description AS service, c.name AS customer_name
             FROM employee_allocations a
             JOIN invoice_items ii ON ii.id = a.invoice_item_id
             JOIN invoices i ON i.id = a.invoice_id AND {$settled}
             JOIN customers c ON c.id = i.customer_id
             WHERE a.employee_id = ?
             ORDER BY a.created_at DESC LIMIT {$limit}"
        );
        $stmt->execute([$employeeId]);
        return $stmt->fetchAll();
    }

    public function allocations(int $employeeId, int $page = 1, int $perPage = 10): array
    {
        $settled = "i.status IN ('issued','paid','partially_paid')";
        $params = [$employeeId];
        $countSql = "SELECT COUNT(*) FROM employee_allocations a JOIN invoices i ON i.id = a.invoice_id AND {$settled} WHERE a.employee_id = ?";
        $selectSql = "SELECT a.*, i.invoice_number, i.created_at AS invoice_date, ii.description AS service_name, c.name AS customer_name
                      FROM employee_allocations a
                      JOIN invoices i ON i.id = a.invoice_id AND {$settled}
                      JOIN invoice_items ii ON ii.id = a.invoice_item_id
                      JOIN customers c ON c.id = i.customer_id
                      WHERE a.employee_id = ?
                      ORDER BY a.created_at DESC";
        return $this->paginateQuery($countSql, $selectSql, $params, $page, $perPage);
    }
}
