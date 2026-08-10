<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

final class CustomerPackageRepository extends BaseRepository
{
    protected string $table = 'customer_packages';

    public function activeFor(int $customerId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM customer_packages
             WHERE customer_id = ? AND status = 'active'
               AND (expires_on IS NULL OR expires_on >= CURDATE())
             ORDER BY created_at DESC"
        );
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }

    public function forCustomer(int $customerId): array
    {
        $stmt = $this->db->prepare(
            "SELECT cp.*, p.selling_price AS template_price, e.name AS sold_by_name
             FROM customer_packages cp
             LEFT JOIN packages p ON p.id = cp.package_id
             LEFT JOIN employees e ON e.id = cp.sold_by
             WHERE cp.customer_id = ?
             ORDER BY cp.created_at DESC"
        );
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }

    public function transactions(int $customerPackageId): array
    {
        $stmt = $this->db->prepare(
            "SELECT cpt.*, i.invoice_number
             FROM customer_package_transactions cpt
             LEFT JOIN invoices i ON i.id = cpt.reference_id
             WHERE cpt.customer_package_id = ?
             ORDER BY cpt.created_at DESC"
        );
        $stmt->execute([$customerPackageId]);
        return $stmt->fetchAll();
    }

    /** Expire packages whose validity has lapsed. Returns rows touched. */
    public function expireOverdue(): int
    {
        $stmt = $this->db->prepare(
            "UPDATE customer_packages
             SET status = 'expired'
             WHERE status = 'active' AND expires_on IS NOT NULL AND expires_on < CURDATE()"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function balanceValue(int $customerPackageId): float
    {
        $stmt = $this->db->prepare(
            "SELECT remaining_credits, value_per_credit, selling_price, credits FROM customer_packages WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$customerPackageId]);
        $row = $stmt->fetch();
        if (!$row) {
            return 0.0;
        }
        $vpc = (float) $row['value_per_credit'];
        if ($vpc <= 0 && (int) $row['credits'] > 0) {
            $vpc = (float) $row['selling_price'] / (int) $row['credits'];
        }
        return round((float) $row['remaining_credits'] * $vpc, 2);
    }

    public function totalCreditsValueFor(int $customerId): float
    {
        $stmt = $this->db->prepare(
            "SELECT cp.* FROM customer_packages cp
             WHERE cp.customer_id = ? AND cp.status = 'active'
               AND (cp.expires_on IS NULL OR cp.expires_on >= CURDATE())"
        );
        $stmt->execute([$customerId]);
        $total = 0.0;
        foreach ($stmt->fetchAll() as $row) {
            $vpc = (float) $row['value_per_credit'];
            if ($vpc <= 0 && (int) $row['credits'] > 0) {
                $vpc = (float) $row['selling_price'] / (int) $row['credits'];
            }
            $total += (float) $row['remaining_credits'] * $vpc;
        }
        return round($total, 2);
    }
}
