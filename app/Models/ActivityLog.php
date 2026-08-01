<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class ActivityLog extends Model
{
    protected string $table = 'activity_logs';
    protected bool $softDeletes = false;
    protected array $fillable = ['user_id', 'type', 'description', 'data', 'ip', 'user_agent'];
}
