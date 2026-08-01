<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class EmployeeAllocation extends Model
{
    protected string $table = 'employee_allocations';
    protected bool $softDeletes = false;
    protected array $fillable = ['invoice_id', 'invoice_item_id', 'employee_id', 'amount'];
}
