<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Setting extends Model
{
    protected string $table = 'settings';
    protected bool $softDeletes = false;
    protected array $fillable = ['key', 'value'];
}
