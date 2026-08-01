<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class LedgerEntry extends Model
{
    protected string $table = 'ledger_entries';
    protected bool $softDeletes = false;
    protected array $fillable = [
        'customer_id', 'type', 'amount', 'balance', 'reference_id', 'description',
    ];
}
