<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Customer extends Model
{
    protected string $table = 'customers';
    protected array $fillable = [
        'name', 'mobile', 'email', 'gender', 'dob', 'address', 'city', 'photo', 'notes', 'status',
        'last_visit_at',
    ];
}
