<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

final class InvoiceRepository extends BaseRepository
{
    protected string $table = 'invoices';

    public function findWithDetails(int $invoiceId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT i.*, c.name AS customer_name, c.email AS customer_email, c.mobile AS customer_phone,
                    c.address AS customer_address
             FROM invoices i
             JOIN customers c ON c.id = i.customer_id
             WHERE i.id = ? LIMIT 1"
        );
        $stmt->execute([$invoiceId]);
        return $stmt->fetch() ?: null;
    }

    public function items(int $invoiceId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ii.*, s.category AS service_category
             FROM invoice_items ii
             LEFT JOIN services s ON s.id = ii.service_id
             WHERE ii.invoice_id = ?"
        );
        $stmt->execute([$invoiceId]);
        $items = $stmt->fetchAll();

        if ($items !== []) {
            $ids = array_column($items, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $allocStmt = $this->db->prepare(
                "SELECT a.invoice_item_id, a.employee_id, e.name AS employee_name, a.amount
                 FROM employee_allocations a
                 LEFT JOIN employees e ON e.id = a.employee_id
                 WHERE a.invoice_item_id IN ({$placeholders})
                 ORDER BY a.id ASC"
            );
            $allocStmt->execute($ids);
            $allocations = [];
            foreach ($allocStmt->fetchAll() as $a) {
                $allocations[$a['invoice_item_id']][] = [
                    'employee_id'   => (int) $a['employee_id'],
                    'employee_name' => $a['employee_name'],
                    'amount'        => (float) $a['amount'],
                ];
            }
            foreach ($items as &$item) {
                $item['allocations'] = $allocations[$item['id']] ?? [];
            }
            unset($item);
        }

        return $items;
    }

    public function payments(int $invoiceId): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, u.name AS user_name FROM payments p
             LEFT JOIN users u ON u.id = p.received_by
             WHERE p.invoice_id = ? ORDER BY p.id ASC"
        );
        $stmt->execute([$invoiceId]);
        return $stmt->fetchAll();
    }

    public function packageTransactions(int $invoiceId): array
    {
        $stmt = $this->db->prepare(
            "SELECT cpt.*, cp.name AS package_name, e.name AS sold_by_name
             FROM customer_package_transactions cpt
             JOIN customer_packages cp ON cp.id = cpt.customer_package_id
             LEFT JOIN employees e ON e.id = cp.sold_by
             WHERE cpt.reference_id = ?"
        );
        $stmt->execute([$invoiceId]);
        return $stmt->fetchAll();
    }

    public function listing(string $search = '', string $status = 'all', int $page = 1, int $perPage = 20): array
    {
        $where  = ['i.deleted_at IS NULL'];
        $params = [];

        if (in_array($status, ['draft', 'issued', 'paid', 'partially_paid', 'cancelled'], true)) {
            $where[] = 'i.status = ?';
            $params[] = $status;
        }

        if (trim($search) !== '') {
            $where[] = '(i.invoice_number LIKE ? OR c.name LIKE ? OR c.mobile LIKE ?)';
            $params[] = '%' . trim($search) . '%';
            $params[] = '%' . trim($search) . '%';
            $params[] = '%' . trim($search) . '%';
        }

        $whereSql = implode(' AND ', $where);
        $countSql = "SELECT COUNT(*) FROM invoices i JOIN customers c ON c.id = i.customer_id WHERE {$whereSql}";
        $selectSql = "SELECT i.*, c.name AS customer_name, c.mobile AS customer_phone
                      FROM invoices i JOIN customers c ON c.id = i.customer_id
                      WHERE {$whereSql} ORDER BY i.created_at DESC";

        return $this->paginateQuery($countSql, $selectSql, $params, $page, $perPage);
    }

    public function findByNumber(string $number): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM invoices WHERE invoice_number = ? LIMIT 1");
        $stmt->execute([$number]);
        return $stmt->fetch() ?: null;
    }

    public function nextInvoiceNumber(): string
    {
        $prefix = (string)setting('invoice_prefix', 'INV-');
        $stmt = $this->db->query("SELECT invoice_number FROM invoices ORDER BY id DESC LIMIT 1");
        $last = $stmt->fetchColumn();
        $n = 1;
        if ($last) {
            $parts = explode('-', (string)$last);
            $lastNum = (int)end($parts);
            $n = $lastNum + 1;
        }
        return $prefix . str_pad((string)$n, 3, '0', STR_PAD_LEFT);
    }
}
