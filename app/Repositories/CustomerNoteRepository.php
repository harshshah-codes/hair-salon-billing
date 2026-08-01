<?php

declare(strict_types=1);

namespace App\Repositories;

class CustomerNoteRepository extends BaseRepository
{
    protected string $table = 'customer_notes';
    protected bool $softDeletes = false;

    public function forCustomer(int $customerId, int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            "SELECT cn.*, u.`name` AS created_by_name
             FROM customer_notes cn LEFT JOIN users u ON u.id = cn.created_by
             WHERE cn.customer_id = ? ORDER BY cn.id DESC LIMIT $limit"
        );
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }
}
