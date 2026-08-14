<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Core\Database;

final class CustomerRepository extends BaseRepository
{
    protected string $table = 'customers';

    /**
     * Filtered + searchable customer list, paginated.
     *
     * @param string      $search   matches name or phone
     * @param string|null $filter   all | active-package | outstanding | inactive
     * @param string|null $sort     name | last_visit | created | outstanding
     * @param int         $page
     * @param int         $perPage
     */
    public function listing(
        string $search = '',
        ?string $filter = 'all',
        ?string $sort = 'created',
        int $page = 1,
        int $perPage = 20,
        ?int $branchId = null
    ): array {
        $where   = ['c.deleted_at IS NULL'];
        $params  = [];

        if ($branchId) {
            $where[] = 'EXISTS (SELECT 1 FROM customer_packages bpc
                                 JOIN branches bf ON bf.address = bpc.branch_address
                                 WHERE bpc.customer_id = c.id AND bf.id = ?)';
            $params[] = $branchId;
        }

        $search = trim($search);
        if ($search !== '') {
            $where[] = '(c.name LIKE ? OR c.mobile LIKE ? OR c.email LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $hasActivePackage = "(cp.customer_id IS NOT NULL)";
        $outstandingExpr  = "(SELECT COALESCE(SUM(i.balance), 0) FROM invoices i
                              WHERE i.customer_id = c.id
                                AND i.status IN ('issued','partially_paid'))";

        switch ($filter) {
            case 'active-package':
                $where[] = $hasActivePackage;
                break;
            case 'outstanding':
                $where[] = $outstandingExpr . ' > 0';
                break;
            case 'inactive':
                $where[] = "c.status = 'inactive'";
                break;
            case 'no-visit':
                $where[] = 'c.last_visit_at IS NULL OR c.last_visit_at < DATE_SUB(CURDATE(), INTERVAL 30 DAY)';
                break;
            case 'all':
            default:
                break;
        }

        $orderBy = match ($sort) {
            'name'        => 'c.name ASC',
            'last_visit'  => 'c.last_visit_at DESC',
            'outstanding' => $outstandingExpr . ' DESC',
            default       => 'c.created_at DESC',
        };

        $join = 'LEFT JOIN (
                    SELECT cp.customer_id, cp.name, cp.remaining_credits,
                           ROW_NUMBER() OVER (PARTITION BY cp.customer_id ORDER BY cp.created_at DESC) AS rn
                    FROM customer_packages cp
                    WHERE cp.status = "active"
                      AND (cp.expires_on IS NULL OR cp.expires_on >= CURDATE())
                ) cp ON cp.customer_id = c.id AND cp.rn = 1';

        $whereSql = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) FROM customers c {$join} WHERE {$whereSql}";
        $selectSql = "SELECT c.*, cp.name AS current_package_name, cp.remaining_credits AS package_balance,
                             {$outstandingExpr} AS outstanding
                      FROM customers c {$join}
                      WHERE {$whereSql} ORDER BY {$orderBy}";

        return $this->paginateQuery($countSql, $selectSql, $params, $page, $perPage);
    }

    /** Aggregate summary used on the customer detail page. */
    public function summary(int $customerId): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                c.*,
                (SELECT COUNT(*) FROM invoices i WHERE i.customer_id = c.id AND i.status IN ('issued','paid','partially_paid')) AS total_visits,
                (SELECT COALESCE(SUM(cp.remaining_credits), 0)
                   FROM customer_packages cp
                  WHERE cp.customer_id = c.id AND cp.status = 'active'
                    AND (cp.expires_on IS NULL OR cp.expires_on >= CURDATE())) AS current_credits,
                (SELECT cp.name FROM customer_packages cp
                  WHERE cp.customer_id = c.id AND cp.status = 'active'
                    AND (cp.expires_on IS NULL OR cp.expires_on >= CURDATE())
                  ORDER BY cp.created_at DESC LIMIT 1) AS current_package,
                (SELECT COALESCE(SUM(l.amount), 0) FROM ledger_entries l
                  WHERE l.customer_id = c.id AND l.type = 'bill') AS ledger_billed,
                (SELECT COALESCE(SUM(l.amount), 0) FROM ledger_entries l
                  WHERE l.customer_id = c.id AND l.type IN ('payment','package','adjustment')) AS ledger_paid
             FROM customers c WHERE c.id = ? AND c.deleted_at IS NULL LIMIT 1"
        );
        $stmt->execute([$customerId]);
        return $stmt->fetch() ?: [];
    }

    /** Recent settled invoices for a customer. */
    public function recentInvoices(int $customerId, int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            "SELECT i.*, COALESCE(SUM(p.amount),0) AS paid
             FROM invoices i
             LEFT JOIN payments p ON p.invoice_id = i.id
             WHERE i.customer_id = ? AND i.status IN ('issued','paid','partially_paid')
             GROUP BY i.id ORDER BY i.created_at DESC LIMIT {$limit}"
        );
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }

    public function billingHistory(int $customerId, int $page = 1, int $perPage = 10): array
    {
        $params = [$customerId];
        $where  = 'i.customer_id = ?';
        $countSql = "SELECT COUNT(*) FROM invoices i WHERE {$where}";
        $selectSql = "SELECT i.*, COALESCE(SUM(p.amount),0) AS paid
                      FROM invoices i
                      LEFT JOIN payments p ON p.invoice_id = i.id
                      WHERE {$where} GROUP BY i.id ORDER BY i.created_at DESC";
        return $this->paginateQuery($countSql, $selectSql, $params, $page, $perPage);
    }

    /** Most recent services (item descriptions) received by a customer. */
    public function recentServices(int $customerId, int $limit = 8): array
    {
        $stmt = $this->db->prepare(
            "SELECT ii.description AS service_name, ii.price, ii.qty,
                    (ii.price * ii.qty) AS amount, i.invoice_number, i.created_at
             FROM invoice_items ii
             JOIN invoices i ON i.id = ii.invoice_id
             WHERE i.customer_id = ? AND i.status IN ('issued','paid','partially_paid')
             ORDER BY i.created_at DESC LIMIT {$limit}"
        );
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }

    /** Paginated ledger entries for a customer. */
    public function ledgerEntries(int $customerId, int $page = 1, int $perPage = 15): array
    {
        $params = [$customerId];
        $countSql = "SELECT COUNT(*) FROM ledger_entries WHERE customer_id = ?";
        $selectSql = "SELECT l.*, i.invoice_number, p.method AS payment_method
                      FROM ledger_entries l
                      LEFT JOIN invoices i ON i.id = l.reference_id
                      LEFT JOIN payments p ON p.id = l.reference_id
                      WHERE l.customer_id = ?
                      ORDER BY l.created_at DESC";
        return $this->paginateQuery($countSql, $selectSql, $params, $page, $perPage);
    }

    public function search(string $q, int $limit = 10): array
    {
$outstandingExpr = "(SELECT COALESCE(SUM(i.balance), 0) FROM invoices i
                              WHERE i.customer_id = c.id AND i.status IN ('issued','partially_paid'))";
        $stmt = $this->db->prepare(
            "SELECT c.id, c.name, c.email, c.mobile, {$outstandingExpr} AS outstanding,
                    (SELECT COALESCE(SUM(cp.remaining_credits), 0)
                     FROM customer_packages cp
                     WHERE cp.customer_id = c.id AND cp.status='active'
                       AND (cp.expires_on IS NULL OR cp.expires_on >= CURDATE())) AS available_balance,
                    (SELECT cp.name FROM customer_packages cp
                      WHERE cp.customer_id = c.id AND cp.status='active'
                        AND (cp.expires_on IS NULL OR cp.expires_on >= CURDATE())
                      ORDER BY cp.created_at DESC LIMIT 1) AS current_package,
                    c.last_visit_at
             FROM customers c
             WHERE c.deleted_at IS NULL AND (c.name LIKE ? OR c.mobile LIKE ? OR c.email LIKE ?)
             ORDER BY c.name LIMIT {$limit}"
        );
        $like = '%' . $q . '%';
        $stmt->execute([$like, $like, $like]);
        return $stmt->fetchAll();
    }
}
