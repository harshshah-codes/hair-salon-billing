<?php
/**
 * Invoice detail + printable invoice.
 * @var array  $invoice
 * @var array  $items
 * @var array  $payments
 * @var array  $packageTransactions
 * @var bool   $print
 */
$print = (bool)($print ?? false);
$statusLabels = [
    'draft'          => 'Draft',
    'issued'         => 'Issued',
    'partially_paid' => 'Partially Paid',
    'paid'           => 'Paid',
    'cancelled'      => 'Cancelled',
];
$status = $invoice['status'] ?? 'issued';
?>

<?php if ($print): ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($invoice['invoice_number']) ?></title>
<style>
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; color: #111; margin: 0; padding: 32px; font-size: 13px; }
    .inv-head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #111; padding-bottom: 16px; margin-bottom: 24px; }
    .inv-head h1 { font-size: 26px; margin: 0 0 4px; }
    .inv-head .muted { color: #666; font-size: 12px; line-height: 1.6; }
    .inv-meta { text-align: right; }
    .inv-meta h2 { margin: 0 0 8px; font-size: 18px; letter-spacing: 1px; }
    .bill-to { margin-bottom: 24px; }
    .bill-to h3 { margin: 0 0 6px; font-size: 13px; text-transform: uppercase; color: #555; }
    .bill-to p { margin: 2px 0; }
    table.items { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    table.items th { background: #f2f2f2; text-align: left; padding: 8px 10px; font-size: 12px; text-transform: uppercase; }
    table.items td { padding: 8px 10px; border-bottom: 1px solid #e5e5e5; }
    table.items td.num, table.items th.num { text-align: right; }
    .totals { width: 320px; margin-left: auto; }
    .totals .row { display: flex; justify-content: space-between; padding: 4px 0; }
    .totals .row.total { font-size: 15px; font-weight: bold; border-top: 2px solid #111; margin-top: 4px; padding-top: 8px; }
    .badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
    .badge.paid { background: #e6f7ef; color: #0a7d45; }
    .badge.partially_paid { background: #fff4e5; color: #b37000; }
    .badge.issued { background: #e8f0fe; color: #1a56c9; }
    .badge.draft { background: #eee; color: #555; }
    .badge.cancelled { background: #fde8e8; color: #b91c1c; }
    .foot { margin-top: 32px; padding-top: 12px; border-top: 1px solid #ddd; font-size: 11px; color: #666; }
    .alloc { color: #666; font-size: 11px; }
    @media print { body { padding: 0; } }
</style>
</head>
<body>
<div class="inv-head">
    <div>
        <h1><?= e(setting('business_name', 'Nirav Hair Storm')) ?></h1>
        <div class="muted"><?= nl2br(e(setting('business_address', ''))) ?></div>
        <div class="muted"><?= e(setting('business_phone', '')) ?> <?= setting('business_email', '') ? '· ' . e(setting('business_email', '')) : '' ?></div>
    </div>
    <div class="inv-meta">
        <h2>INVOICE</h2>
        <div><?= e($invoice['invoice_number']) ?></div>
        <div>Date: <?= e(format_date($invoice['invoice_date'])) ?></div>
        <div class="mt-1"><span class="badge <?= e($status) ?>"><?= e($statusLabels[$status] ?? $status) ?></span></div>
    </div>
</div>

<div class="bill-to">
    <h3>Bill To</h3>
    <p><strong><?= e($invoice['customer_name']) ?></strong></p>
    <?php if (!empty($invoice['customer_phone'])): ?><p>Mobile: <?= e($invoice['customer_phone']) ?></p><?php endif; ?>
    <?php if (!empty($invoice['customer_email'])): ?><p>Email: <?= e($invoice['customer_email']) ?></p><?php endif; ?>
    <?php if (!empty($invoice['customer_address'])): ?><p>Address: <?= e($invoice['customer_address']) ?></p><?php endif; ?>
</div>

<table class="items">
    <thead>
    <tr><th>Description</th><th class="num">Qty</th><th class="num">Price</th><th class="num">Amount</th></tr>
    </thead>
    <tbody>
    <?php foreach ($items as $item): ?>
        <tr>
            <td>
                <?= e($item['description']) ?>
                <?php if (!empty($item['allocations'])): ?>
                    <div class="alloc"><?= e(implode(', ', array_map(static fn ($a) => $a['employee_name'] . ' (₹' . number_format((float)$a['amount'], 2) . ')', $item['allocations']))) ?></div>
                <?php endif; ?>
            </td>
            <td class="num"><?= (int)$item['qty'] ?></td>
            <td class="num"><?= e(number_format((float)$item['price'], 2)) ?></td>
            <td class="num"><?= e(number_format((float)$item['amount'], 2)) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class="totals">
    <div class="row"><span>Subtotal</span><span><?= e(money($invoice['subtotal'])) ?></span></div>
    <?php if ((float)$invoice['discount'] > 0): ?>
        <div class="row"><span>Discount</span><span>-<?= e(money($invoice['discount'])) ?></span></div>
    <?php endif; ?>
    <?php if ((float)$invoice['gst_amount'] > 0): ?>
        <div class="row"><span>GST (<?= e(rtrim(rtrim((string)$invoice['gst_percent'], '0'), '.')) ?>%)</span><span><?= e(money($invoice['gst_amount'])) ?></span></div>
    <?php endif; ?>
    <?php if ((float)$invoice['package_used'] > 0): ?>
        <div class="row"><span>Package Credits</span><span>-<?= e(money($invoice['package_used'])) ?></span></div>
    <?php endif; ?>
    <div class="row total"><span>Total Payable</span><span><?= e(money($invoice['payable'])) ?></span></div>
    <div class="row"><span>Paid</span><span><?= e(money($invoice['paid'])) ?></span></div>
    <div class="row"><span>Balance Due</span><span><?= e(money($invoice['balance'])) ?></span></div>
</div>

<?php if (!empty($invoice['notes'])): ?>
    <p class="mt-3"><strong>Notes:</strong> <?= nl2br(e($invoice['notes'])) ?></p>
<?php endif; ?>

<?php if (!empty(setting('invoice_footer', ''))): ?>
    <div class="foot"><?= nl2br(e(setting('invoice_footer'))) ?></div>
<?php endif; ?>

<script>window.print();</script>
</body>
</html>
<?php else: ?>
<div class="page-header">
    <div>
        <h1>Invoice <?= e($invoice['invoice_number']) ?></h1>
        <p><?= e(format_date($invoice['invoice_date'])) ?> · <span class="status-pill status-<?= e($status) ?>"><?= e($statusLabels[$status] ?? $status) ?></span></p>
    </div>
    <div class="page-actions">
        <a href="<?= e(url('/billing/invoice/' . (int)$invoice['id'] . '/print')) ?>" class="btn btn-light" target="_blank"><i class="fa-solid fa-print me-1"></i>Print</a>
        <a href="<?= e(url('/billing/invoice/' . (int)$invoice['id'] . '/pdf')) ?>" class="btn btn-light"><i class="fa-solid fa-file-pdf me-1"></i>PDF</a>
        <?php if (in_array($status, ['issued', 'partially_paid'], true)): ?>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#payModal"><i class="fa-solid fa-hand-holding-dollar me-1"></i>Record Payment</button>
            <form method="post" action="<?= e(url('/invoices/' . (int)$invoice['id'] . '/cancel')) ?>" class="d-inline" data-confirm data-confirm-message="Cancel this invoice? Package credits used will be refunded.">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-danger"><i class="fa-solid fa-ban me-1"></i>Cancel Invoice</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-muted small text-uppercase mb-2">Bill To</h6>
                <h5 class="mb-1"><?= e($invoice['customer_name']) ?></h5>
                <?php if (!empty($invoice['customer_phone'])): ?><div><?= e($invoice['customer_phone']) ?></div><?php endif; ?>
                <?php if (!empty($invoice['customer_email'])): ?><div><?= e($invoice['customer_email']) ?></div><?php endif; ?>
                <?php if (!empty($invoice['customer_address'])): ?><div class="text-muted"><?= e($invoice['customer_address']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6 text-md-end">
                <h6 class="text-muted small text-uppercase mb-2">Invoice Summary</h6>
                <div class="mb-1">Subtotal: <strong><?= e(money($invoice['subtotal'])) ?></strong></div>
                <?php if ((float)$invoice['discount'] > 0): ?><div class="mb-1">Discount: <strong>-<?= e(money($invoice['discount'])) ?></strong></div><?php endif; ?>
                <?php if ((float)$invoice['gst_amount'] > 0): ?><div class="mb-1">GST: <strong><?= e(money($invoice['gst_amount'])) ?></strong></div><?php endif; ?>
                <?php if ((float)$invoice['package_used'] > 0): ?><div class="mb-1">Package Credits: <strong>-<?= e(money($invoice['package_used'])) ?></strong></div><?php endif; ?>
                <hr>
                <div class="fs-5 fw-bold">Payable: <?= e(money($invoice['payable'])) ?></div>
                <div>Paid: <span class="text-success"><?= e(money($invoice['paid'])) ?></span></div>
                <div>Balance: <span class="<?= (float)$invoice['balance'] > 0 ? 'text-danger fw-semibold' : '' ?>"><?= e(money($invoice['balance'])) ?></span></div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Items</h5></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Description</th><th class="text-center">Qty</th><th class="text-end">Price</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <?= e($item['description']) ?>
                        <?php if (!empty($item['allocations'])): ?>
                            <div class="small text-muted">
                                <?php foreach ($item['allocations'] as $a): ?>
                                    <span class="badge bg-secondary-soft me-1"><?= e($a['employee_name']) ?> · <?= e(money($a['amount'])) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?= (int)$item['qty'] ?></td>
                    <td class="text-end"><?= e(money($item['price'])) ?></td>
                    <td class="text-end fw-semibold"><?= e(money($item['amount'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($packageTransactions)): ?>
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Package Transactions</h5></div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead><tr><th>Date</th><th>Package</th><th>Type</th><th class="text-end">Credits</th><th class="text-end">Amount</th><th>Description</th></tr></thead>
            <tbody>
            <?php foreach ($packageTransactions as $t): ?>
                <tr>
                    <td class="text-muted text-nowrap"><?= e(format_datetime($t['created_at'])) ?></td>
                    <td><?= e($t['package_name']) ?></td>
                    <td><span class="badge bg-secondary-soft"><?= e(ucfirst($t['type'])) ?></span></td>
                    <td class="text-end"><?= e(number_format((float)$t['credits'], 2)) ?></td>
                    <td class="text-end"><?= e(money($t['amount'])) ?></td>
                    <td class="text-muted"><?= e($t['description'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Payments</h5>
        <?php if (in_array($status, ['issued', 'partially_paid'], true)): ?>
            <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#payModal"><i class="fa-solid fa-plus me-1"></i>Add</button>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead><tr><th>Date</th><th>Method</th><th>Reference</th><th>By</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
            <?php if (empty($payments)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">No payments recorded</td></tr>
            <?php else: ?>
                <?php foreach ($payments as $p): ?>
                    <tr>
                        <td class="text-muted text-nowrap"><?= e(format_datetime($p['paid_at'])) ?></td>
                        <td><span class="badge bg-secondary-soft"><?= e(ucfirst($p['method'])) ?></span></td>
                        <td><?= e($p['reference'] ?: '—') ?></td>
                        <td><?= e($p['user_name'] ?: '—') ?></td>
                        <td class="text-end fw-semibold text-success"><?= e(money($p['amount'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($invoice['notes'])): ?>
    <div class="alert alert-light border"><strong>Notes:</strong> <?= nl2br(e($invoice['notes'])) ?></div>
<?php endif; ?>

<div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= e(url('/invoices/' . (int)$invoice['id'] . '/pay')) ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Record Payment — <?= e($invoice['invoice_number']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">Balance due: <strong><?= e(money($invoice['balance'])) ?></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01" max="<?= e($invoice['balance']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Method</label>
                        <select name="method" class="form-select">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="upi">UPI</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reference <span class="text-muted small">(optional)</span></label>
                        <input type="text" name="reference" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
