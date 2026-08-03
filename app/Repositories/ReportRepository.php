<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Core\Database;

final class ReportRepository extends BaseRepository
{
    protected string $table = 'invoices';

    private function dateWhere(string $from, string $to): string
    {
        if ($from && $to) {
            return " AND DATE(created_at) BETWEEN '{$from}' AND '{$to}'";
        }
        return '';
    }

    /** Settled (non-draft, non-cancelled) invoice statuses. */
    private function settled(): string
    {
        return "i.status IN ('issued','paid','partially_paid')";
    }

    /** Daily revenue in a range (settled invoices, payable amount). */
    public function revenueSeries(string $from, string $to): array
    {
        $sql = "SELECT DATE(created_at) AS day,
                       COALESCE(SUM(payable),0) AS revenue,
                       COALESCE(SUM(package_used),0) AS package_used,
                       COUNT(*) AS bills
                FROM invoices
                WHERE status IN ('issued','paid','partially_paid'){$this->dateWhere($from, $to)}
                GROUP BY DATE(created_at) ORDER BY day ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function revenueTotals(string $from, string $to): array
    {
        $sql = "SELECT COALESCE(SUM(payable),0) AS revenue,
                       COALESCE(SUM(package_used),0) AS package_deduction,
                       COALESCE(SUM(gst_amount),0) AS gst,
                       COUNT(*) AS bill_count
                FROM invoices WHERE status IN ('issued','paid','partially_paid'){$this->dateWhere($from, $to)}";
        return $this->db->query($sql)->fetch();
    }

    /** Revenue with optional employee / service / customer filters. */
    public function filteredRevenue(
        string $from,
        string $to,
        ?int $employeeId = null,
        ?int $serviceId = null,
        ?int $customerId = null
    ): array {
        $where = ['i.status IN (?, ?, ?)'];
        $params = ['issued', 'paid', 'partially_paid'];

        if ($from && $to) {
            $where[] = 'DATE(i.created_at) BETWEEN ? AND ?';
            $params[] = $from;
            $params[] = $to;
        }
        if ($customerId) {
            $where[] = 'i.customer_id = ?';
            $params[] = $customerId;
        }
        if ($employeeId) {
            $where[] = 'EXISTS (SELECT 1 FROM employee_allocations a WHERE a.invoice_id = i.id AND a.employee_id = ?)';
            $params[] = $employeeId;
        }
        if ($serviceId) {
            $where[] = 'EXISTS (SELECT 1 FROM invoice_items ii WHERE ii.invoice_id = i.id AND ii.service_id = ?)';
            $params[] = $serviceId;
        }

        $whereSql = implode(' AND ', $where);

        $stmt = $this->db->prepare(
            "SELECT i.id, i.invoice_number, i.payable AS amount_payable, i.package_used AS package_deduction, i.created_at,
                    c.name AS customer_name
             FROM invoices i JOIN customers c ON c.id = i.customer_id
             WHERE {$whereSql} ORDER BY i.created_at DESC"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $totals = ['revenue' => 0.0, 'package_deduction' => 0.0, 'count' => count($rows)];
        foreach ($rows as $row) {
            $totals['revenue'] += (float)$row['amount_payable'];
            $totals['package_deduction'] += (float)$row['package_deduction'];
        }

        return ['rows' => $rows, 'totals' => $totals];
    }

    /** Package sales (customer_packages purchases) within a range. */
    public function packageSales(string $from, string $to): array
    {
        $sql = "SELECT cp.name AS package_name,
                       COUNT(*) AS units,
                       COALESCE(SUM(cp.selling_price),0) AS total_sold
                FROM customer_packages cp
                WHERE DATE(cp.created_at) BETWEEN '{$from}' AND '{$to}'
                GROUP BY cp.name ORDER BY total_sold DESC";
        return $this->db->query($sql)->fetchAll();
    }

    /** Employee earnings (allocations on settled invoices) within a range. */
    public function employeeEarnings(string $from, string $to, ?int $employeeId = null): array
    {
        $where = "i.status IN ('issued','paid','partially_paid') AND DATE(i.created_at) BETWEEN '{$from}' AND '{$to}'";
        $params = [];
        $bind = '';
        if ($employeeId) {
            $bind = ' AND a.employee_id = ?';
            $params[] = $employeeId;
        }

        $stmt = $this->db->prepare(
            "SELECT e.id, e.name, e.designation AS role,
                    COALESCE(SUM(a.amount),0) AS earnings,
                    COUNT(DISTINCT a.invoice_item_id) AS services,
                    COUNT(DISTINCT i.customer_id) AS customers
             FROM employee_allocations a
             JOIN employees e ON e.id = a.employee_id
             JOIN invoices i ON i.id = a.invoice_id
             WHERE {$where}{$bind}
             GROUP BY e.id, e.name, e.designation
             ORDER BY earnings DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Service revenue (invoice items) within a range. */
    public function serviceRevenue(string $from, string $to, ?int $serviceId = null): array
    {
        $where = "i.status IN ('issued','paid','partially_paid') AND DATE(i.created_at) BETWEEN '{$from}' AND '{$to}'";
        $params = [];
        if ($serviceId) {
            $where .= ' AND ii.service_id = ?';
            $params[] = $serviceId;
        }

        $stmt = $this->db->prepare(
            "SELECT ii.description AS service_name,
                    ii.service_id,
                    COALESCE(SUM(ii.amount),0) AS revenue,
                    SUM(ii.qty) AS qty,
                    COUNT(DISTINCT i.customer_id) AS customers
             FROM invoice_items ii
             JOIN invoices i ON i.id = ii.invoice_id
             WHERE {$where}
             GROUP BY ii.description, ii.service_id
             ORDER BY revenue DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Customers with outstanding balances. */
    public function outstanding(int $limit = 500): array
    {
        $outstandingExpr = "(SELECT COALESCE(SUM(i.balance), 0) FROM invoices i
                              WHERE i.customer_id = c.id AND i.status IN ('issued','partially_paid'))";
        return $this->db->query(
            "SELECT c.id, c.name, c.mobile AS phone, {$outstandingExpr} AS outstanding,
                    (SELECT cp.name FROM customer_packages cp
                      WHERE cp.customer_id = c.id AND cp.status='active'
                        AND (cp.expires_on IS NULL OR cp.expires_on >= CURDATE())
                      ORDER BY cp.created_at DESC LIMIT 1) AS current_package,
                    c.last_visit_at
             FROM customers c
             WHERE c.deleted_at IS NULL AND {$outstandingExpr} > 0
             ORDER BY {$outstandingExpr} DESC LIMIT {$limit}"
        )->fetchAll();
    }

    /** Ledger statement for one customer within a range. */
    public function customerStatement(int $customerId, string $from, string $to): array
    {
        $stmt = $this->db->prepare(
            "SELECT cpt.id, cpt.created_at, cpt.type, cpt.amount, cpt.reference_id,
                    cp.name AS package_name, i.invoice_number,
                    (SELECT GROUP_CONCAT(CONCAT(ii.description, IF(ii.qty > 1, CONCAT(' x', ii.qty), '')) ORDER BY ii.id SEPARATOR ', ')
                     FROM invoice_items ii
                     WHERE ii.invoice_id = cpt.reference_id) AS services,
                    (SELECT GROUP_CONCAT(DISTINCT e.name ORDER BY e.name SEPARATOR ', ')
                     FROM employee_allocations ea
                     JOIN employees e ON e.id = ea.employee_id
                     WHERE ea.invoice_id = cpt.reference_id) AS employees
             FROM customer_package_transactions cpt
             LEFT JOIN customer_packages cp ON cp.id = cpt.customer_package_id
             LEFT JOIN invoices i ON i.id = cpt.reference_id
             WHERE cpt.customer_id = ? AND DATE(cpt.created_at) BETWEEN ? AND ?
             ORDER BY cpt.id ASC"
        );
        $stmt->execute([$customerId, $from, $to]);

        $rows = $stmt->fetchAll();
        $running = 0.0;
        foreach ($rows as &$row) {
            // Credit-ish types add money to the wallet; debits take it out.
            $isCredit = in_array($row['type'], ['purchase', 'credit'], true);
            $amount = round((float) $row['amount'], 2);
            $row['is_credit'] = $isCredit;
            $running = round($isCredit ? $running + $amount : $running - $amount, 2);
            $row['wallet_balance'] = $running;
        }
        unset($row);

        return $rows;
    }

    /* ---------------------------------------------------------
     * Dashboard aggregates
     * ------------------------------------------------------- */

    public function dashboardCards(): array
    {
        $settled = "status IN ('issued','paid','partially_paid')";
        return [
            'revenue_today'      => (float)$this->db->query(
                "SELECT COALESCE(SUM(payable),0) FROM invoices WHERE {$settled} AND DATE(created_at)=CURDATE()"
            )->fetchColumn(),
            'revenue_month'      => (float)$this->db->query(
                "SELECT COALESCE(SUM(payable),0) FROM invoices WHERE {$settled} AND YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE())"
            )->fetchColumn(),
            'customers'          => (int)$this->db->query(
                "SELECT COUNT(*) FROM customers WHERE deleted_at IS NULL AND status='active'"
            )->fetchColumn(),
            'packages_active'    => (int)$this->db->query(
                "SELECT COUNT(*) FROM customer_packages WHERE status='active' AND (expires_on IS NULL OR expires_on >= CURDATE())"
            )->fetchColumn(),
            'outstanding'        => (float)$this->db->query(
                "SELECT COALESCE(SUM(balance),0) FROM invoices WHERE status IN ('issued','partially_paid')"
            )->fetchColumn(),
            'services_today'     => (int)$this->db->query(
                "SELECT COUNT(*) FROM invoice_items ii JOIN invoices i ON i.id=ii.invoice_id WHERE {$settled} AND DATE(i.created_at)=CURDATE()"
            )->fetchColumn(),
            'invoices_month'     => (int)$this->db->query(
                "SELECT COUNT(*) FROM invoices WHERE {$settled} AND YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE())"
            )->fetchColumn(),
            'employees_active'   => (int)$this->db->query(
                "SELECT COUNT(*) FROM employees WHERE status='active' AND deleted_at IS NULL"
            )->fetchColumn(),
        ];
    }

    public function revenueLastDays(int $days = 30): array
    {
        $stmt = $this->db->prepare(
            "SELECT DATE(created_at) AS day, COALESCE(SUM(payable),0) AS revenue
             FROM invoices WHERE status IN ('issued','paid','partially_paid')
               AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(created_at) ORDER BY day ASC"
        );
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }

    public function topServices(int $limit = 6): array
    {
        $stmt = $this->db->query(
            "SELECT ii.description AS service_name, COALESCE(SUM(ii.amount),0) AS revenue, SUM(ii.qty) AS qty
             FROM invoice_items ii JOIN invoices i ON i.id = ii.invoice_id
             WHERE i.status IN ('issued','paid','partially_paid')
             GROUP BY ii.description ORDER BY revenue DESC LIMIT {$limit}"
        );
        return $stmt->fetchAll();
    }

    public function employeePerformance(int $limit = 6): array
    {
        $stmt = $this->db->query(
            "SELECT e.name, COALESCE(SUM(a.amount),0) AS earnings
             FROM employee_allocations a
             JOIN employees e ON e.id = a.employee_id
             JOIN invoices i ON i.id = a.invoice_id AND i.status IN ('issued','paid','partially_paid')
             GROUP BY e.id, e.name ORDER BY earnings DESC LIMIT {$limit}"
        );
        return $stmt->fetchAll();
    }

    public function recentBills(int $limit = 8): array
    {
        $stmt = $this->db->prepare(
            "SELECT i.id, i.invoice_number, i.payable, i.status AS payment_status, i.created_at, c.name AS customer_name
             FROM invoices i JOIN customers c ON c.id = i.customer_id
             WHERE i.status IN ('issued','paid','partially_paid')
             ORDER BY i.created_at DESC LIMIT {$limit}"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function lowBalanceCustomers(int $limit = 8): array
    {
        $stmt = $this->db->query(
            "SELECT cp.customer_id AS id, c.name, c.mobile AS phone,
                    cp.name AS package_name, cp.remaining_credits
             FROM customer_packages cp
             JOIN customers c ON c.id = cp.customer_id
             WHERE cp.status='active' AND (cp.expires_on IS NULL OR cp.expires_on >= CURDATE())
               AND cp.remaining_credits <= 2
             ORDER BY cp.remaining_credits ASC LIMIT {$limit}"
        );
        return $stmt->fetchAll();
    }

    public function inactiveCustomers(int $limit = 8): array
    {
        $outstandingExpr = "(SELECT COALESCE(SUM(i.balance), 0) FROM invoices i
                              WHERE i.customer_id = customers.id AND i.status IN ('issued','partially_paid'))";
        $stmt = $this->db->prepare(
            "SELECT id, name, mobile AS phone, last_visit_at, {$outstandingExpr} AS outstanding
             FROM customers
             WHERE deleted_at IS NULL AND status='active'
               AND (last_visit_at IS NULL OR last_visit_at < DATE_SUB(CURDATE(), INTERVAL 30 DAY))
             ORDER BY last_visit_at IS NULL DESC, last_visit_at ASC LIMIT {$limit}"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
