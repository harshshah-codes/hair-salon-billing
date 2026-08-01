<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ApiToken extends Model
{
    protected string $table = 'api_tokens';
    protected array $fillable = ['user_id', 'token', 'name', 'expires_at', 'last_used_at'];
    protected bool $softDeletes = false;
}
