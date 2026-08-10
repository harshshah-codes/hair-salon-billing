<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class CustomerPackage extends Model
{
    protected string $table = 'customer_packages';
    protected array $fillable = [
        'customer_id', 'package_id', 'sold_by', 'name', 'selling_price', 'credits', 'remaining_credits',
        'value_per_credit', 'validity_days', 'starts_on', 'expires_on', 'status', 'notes', 'branch_address',
    ];
}
