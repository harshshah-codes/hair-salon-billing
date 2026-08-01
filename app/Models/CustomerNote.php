<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class CustomerNote extends Model
{
    protected string $table = 'customer_notes';
    protected bool $softDeletes = false;
    protected array $fillable = ['customer_id', 'note', 'created_by'];
}
