<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = ['role_id', 'name', 'email', 'phone', 'avatar', 'password', 'status', 'last_login_at', 'remember_token'];
}
