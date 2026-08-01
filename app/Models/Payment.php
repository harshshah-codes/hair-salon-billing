<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Payment extends Model
{
    protected string $table = 'payments';
    protected bool $softDeletes = false;
    protected array $fillable = [
        'invoice_id', 'customer_id', 'amount', 'method', 'reference', 'received_by', 'paid_at', 'notes',
    ];
}
