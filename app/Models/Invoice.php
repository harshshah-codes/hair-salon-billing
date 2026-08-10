<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Invoice extends Model
{
    protected string $table = 'invoices';
    protected array $fillable = [
        'invoice_number', 'customer_id', 'subtotal', 'discount', 'gst_percent', 'gst_amount',
        'total', 'package_used', 'payable', 'paid', 'balance', 'status', 'payment_method',
        'notes', 'invoice_date', 'due_date', 'branch_id', 'created_by',
    ];
}
