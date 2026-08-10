<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Branch extends Model
{
    protected string $table = 'branches';
    protected bool $softDeletes = false;
    protected array $fillable = [
        'name', 'address', 'phone', 'status',
    ];
}
