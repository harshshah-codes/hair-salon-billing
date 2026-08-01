<?php

declare(strict_types=1);

namespace App\Repositories;

class PaymentRepository extends BaseRepository
{
    protected string $table = 'payments';
    protected bool $softDeletes = false;

    public function forInvoice(int $invoiceId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM payments WHERE invoice_id = ? ORDER BY id'
        );
        $stmt->execute([$invoiceId]);
        return $stmt->fetchAll();
    }

    public function totalForInvoice(int $invoiceId): float
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(amount), 0) FROM payments WHERE invoice_id = ?'
        );
        $stmt->execute([$invoiceId]);
        return (float) $stmt->fetchColumn();
    }
}
