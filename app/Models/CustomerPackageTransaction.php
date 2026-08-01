<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class CustomerPackageTransaction extends Model
{
    protected string $table = 'customer_package_transactions';
    protected bool $softDeletes = false;
    protected array $fillable = [
        'customer_package_id', 'customer_id', 'type', 'credits', 'amount', 'description', 'reference_id',
    ];
}
