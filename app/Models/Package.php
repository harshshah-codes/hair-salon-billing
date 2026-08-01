<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Package extends Model
{
    protected string $table = 'packages';
    protected array $fillable = [
        'name', 'selling_price', 'credits', 'validity_days', 'description', 'status',
    ];
}
