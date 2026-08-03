<?php
/** @var array  $invoices */
/** @var array  $paginator */
/** @var string $search */
/** @var string $status */
?>
<div class="page-header">
    <div>
        <h1>Transactions</h1>
        <p>All transactions created against customers.</p>
    </div>
    <div class="page-actions">
        <a href="<?= e(url('/billing')) ?>" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>New Transaction</a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body py-3">
        <form method="get" action="<?= e(url('/billing/history')) ?>" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label small">Search</label>
                <input type="text" name="search" class="form-control" value="<?= e($search) ?>" placeholder="Invoice #, customer name or phone…">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select">
                    <option value="all" <?= $status === '' ? 'selected' : '' ?>>All</option>
                    <?php foreach (['draft', 'issued', 'partially_paid', 'paid', 'cancelled'] as $s): ?>
                        <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $s))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-filter me-1"></i>Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
            <tr>
                <th>Invoice</th>
                <th>Customer</th>
                <th>Date</th>
                <th class="text-end">Payable</th>
                <th class="text-end">Balance</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($invoices)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No invoices found</td></tr>
            <?php else: ?>
                <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td><a href="<?= e(url('/billing/invoice/' . (int)$inv['id'])) ?>" class="fw-semibold text-decoration-none"><?= e($inv['invoice_number']) ?></a></td>
                        <td><?= e($inv['customer_name']) ?><div class="small text-muted"><?= e($inv['customer_phone']) ?></div></td>
                        <td class="text-muted text-nowrap"><?= e(format_date($inv['invoice_date'])) ?></td>
                        <td class="text-end fw-semibold"><?= e(money($inv['payable'])) ?></td>
                        <td class="text-end <?= (float)$inv['balance'] > 0 ? 'text-danger fw-semibold' : 'text-muted' ?>"><?= e(money($inv['balance'])) ?></td>
                        <td><span class="status-pill status-<?= e($inv['status']) ?>"><?= e(ucfirst(str_replace('_', ' ', $inv['status']))) ?></span></td>
                        <td class="text-end text-nowrap">
                            <a href="<?= e(url('/billing/invoice/' . (int)$inv['id'] . '/print')) ?>" class="btn btn-sm btn-light" title="Print"><i class="fa-solid fa-print"></i></a>
                            <a href="<?= e(url('/billing/invoice/' . (int)$inv['id'] . '/pdf')) ?>" class="btn btn-sm btn-light" title="PDF"><i class="fa-solid fa-file-pdf"></i></a>
                            <?php if (in_array($inv['status'], ['issued', 'partially_paid'], true)): ?>
                                <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#payModal"
                                        data-id="<?= (int)$inv['id'] ?>"
                                        data-number="<?= e($inv['invoice_number']) ?>"
                                        data-balance="<?= e($inv['balance']) ?>">Pay</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-body">
        <?= partial('partials/pagination', ['paginator' => $paginator]) ?>
    </div>
</div>

<div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= e(url('/invoices/0/pay')) ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Record Payment — <span data-invoice-number></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="invoice_id" value="">
                    <p class="small text-muted mb-3">Balance due: <strong data-balance-due></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
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

<script>
    $(function () {
        $('#payModal').on('show.bs.modal', function (e) {
            const b = $(e.relatedTarget);
            const m = $(this);
            m.find('form').attr('action', '<?= e(url('/invoices')) ?>/' + b.data('id') + '/pay');
            m.find('input[name="invoice_id"]').val(b.data('id'));
            m.find('[data-invoice-number]').text(b.data('number'));
            m.find('[data-balance-due]').text(b.data('balance'));
            m.find('input[name="amount"]').attr('max', b.data('balance')).val(b.data('balance'));
        });
    });
</script>
