<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

class LedgerRepository extends BaseRepository
{
    protected string $table = 'ledger_entries';
    protected bool $softDeletes = false;

    public function forCustomer(int $customerId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM ledger_entries WHERE customer_id = ? ORDER BY id ASC'
        );
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }

    public function openingBalance(int $customerId): float
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(amount, 0) FROM ledger_entries
             WHERE customer_id = ? AND type = ? ORDER BY id ASC LIMIT 1'
        );
        $stmt->execute([$customerId, 'opening']);
        return (float) $stmt->fetchColumn();
    }
}
