<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class InvoiceItem extends Model
{
    protected string $table = 'invoice_items';
    protected bool $softDeletes = false;
    protected array $fillable = ['invoice_id', 'service_id', 'description', 'price', 'qty', 'amount'];
}
