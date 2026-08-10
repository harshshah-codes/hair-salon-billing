<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Employee extends Model
{
    protected string $table = 'employees';
    protected array $fillable = [
        'branch_id', 'name', 'mobile', 'email', 'designation', 'photo', 'commission_rate', 'status', 'joined_at',
    ];
}
