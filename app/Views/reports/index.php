<?php
/** @var string $type */
/** @var string $from */
/** @var string $to */
/** @var array  $options */
/** @var array  $query */
?>
<?php
$types = [
    'revenue'          => 'Revenue',
    'package_sales'    => 'Package Sales',
    'employee_earnings' => 'Employee Earnings',
    'service_revenue'  => 'Service Revenue',
    'outstanding'      => 'Outstanding',
    'statements'       => 'Customer Statements',
];
$current = isset($types[$type]) ? $type : 'revenue';
$isRevenue = $current === 'revenue';
?>
<div class="page-header">
    <div>
        <h1>Reports</h1>
        <p>Analyze revenue, staff performance and outstanding balances.</p>
    </div>
    <div class="page-actions">
        <form method="post" action="<?= e(url('/reports/export')) ?>" class="d-inline-flex gap-2">
            <?= csrf_field() ?>
            <input type="hidden" name="type" value="<?= e($current) ?>">
            <input type="hidden" name="from" value="<?= e($from) ?>">
            <input type="hidden" name="to" value="<?= e($to) ?>">
            <input type="hidden" name="employee_id" value="<?= e($query['employee_id'] ?? '') ?>">
            <input type="hidden" name="customer_id" value="<?= e($query['customer_id'] ?? '') ?>">
            <input type="hidden" name="service_id" value="<?= e($query['service_id'] ?? '') ?>">
            <button type="submit" name="format" value="print" class="btn btn-light"><i class="fa-solid fa-print me-1"></i>Print</button>
            <button type="submit" name="format" value="csv" class="btn btn-primary"><i class="fa-solid fa-file-csv me-1"></i>Export CSV</button>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body py-2">
        <ul class="nav nav-pills report-tabs flex-wrap">
            <?php foreach ($types as $key => $label): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $current === $key ? 'active' : '' ?>"
                       href="<?= e(url('/reports?type=' . $key . '&from=' . urlencode($from) . '&to=' . urlencode($to))) ?>"><?= e($label) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="<?= e(url('/reports')) ?>" class="row g-3 align-items-end">
            <input type="hidden" name="type" value="<?= e($current) ?>">
            <div class="col-md-2">
                <label class="form-label small">From</label>
                <input type="date" class="form-control" name="from" value="<?= e($from) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small">To</label>
                <input type="date" class="form-control" name="to" value="<?= e($to) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Employee</label>
                <select class="form-select" name="employee_id">
                    <option value="">All</option>
                    <?php foreach ($options['employees'] as $emp): ?>
                        <option value="<?= (int)$emp['id'] ?>" <?= (string)($query['employee_id'] ?? '') === (string)$emp['id'] ? 'selected' : '' ?>><?= e($emp['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Customer</label>
                <select class="form-select" name="customer_id">
                    <option value="">All</option>
                    <?php foreach ($options['customers'] as $cust): ?>
                        <option value="<?= (int)$cust['id'] ?>" <?= (string)($query['customer_id'] ?? '') === (string)$cust['id'] ? 'selected' : '' ?>><?= e($cust['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Service</label>
                <select class="form-select" name="service_id">
                    <option value="">All</option>
                    <?php foreach ($options['services'] as $svc): ?>
                        <option value="<?= (int)$svc['id'] ?>" <?= (string)($query['service_id'] ?? '') === (string)$svc['id'] ? 'selected' : '' ?>><?= e($svc['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-filter me-1"></i>Apply</button>
            </div>
        </form>
    </div>
</div>

<?php if ($current === 'revenue'): ?>
    <?php $totals = $totals ?? []; ?>
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card stat-card"><div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <span class="stat-icon bg-success-soft text-success"><i class="fa-solid fa-indian-rupee-sign"></i></span>
                    <div><div class="stat-value"><?= e(money($totals['revenue'] ?? 0)) ?></div><div class="stat-label">Revenue</div></div>
                </div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card"><div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <span class="stat-icon bg-primary-soft text-primary"><i class="fa-solid fa-box-open"></i></span>
                    <div><div class="stat-value"><?= e(money($totals['package_deduction'] ?? 0)) ?></div><div class="stat-label">Package Deduction</div></div>
                </div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card"><div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <span class="stat-icon bg-info-soft text-info"><i class="fa-solid fa-receipt"></i></span>
                    <div><div class="stat-value"><?= (int)($totals['bill_count'] ?? 0) ?></div><div class="stat-label">Bills</div></div>
                </div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card"><div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <span class="stat-icon bg-secondary-soft text-secondary"><i class="fa-solid fa-percent"></i></span>
                    <div><div class="stat-value"><?= e(money($totals['gst'] ?? 0)) ?></div><div class="stat-label">GST Collected</div></div>
                </div>
            </div></div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Daily Revenue</h5></div>
        <div class="card-body">
            <canvas id="revenueChart" height="100"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Invoices</h5></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Invoice</th><th>Customer</th><th>Date</th><th class="text-end">Package</th><th class="text-end">Payable</th></tr></thead>
                <tbody>
                <?php $detail = $detail ?? []; ?>
                <?php if (empty($detail['rows'])): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No invoices in range</td></tr>
                <?php else: ?>
                    <?php foreach ($detail['rows'] as $row): ?>
                        <tr>
                            <td><a href="<?= e(url('/billing/invoice/' . (int)$row['id'])) ?>" class="fw-semibold text-decoration-none"><?= e($row['invoice_number']) ?></a></td>
                            <td><?= e($row['customer_name']) ?></td>
                            <td class="text-muted"><?= e(format_date($row['created_at'])) ?></td>
                            <td class="text-end"><?= e(money($row['package_deduction'])) ?></td>
                            <td class="text-end fw-semibold"><?= e(money($row['amount_payable'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php elseif ($current === 'package_sales'): ?>
    <div class="card">
        <div class="card-header"><h5 class="mb-0">Package Sales</h5></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Package</th><th class="text-center">Units</th><th class="text-end">Total Value</th></tr></thead>
                <tbody>
                <?php $rows = $rows ?? []; ?>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="3" class="text-center text-muted py-4">No package sales in range</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($row['package_name']) ?></td>
                            <td class="text-center"><?= (int)$row['units'] ?></td>
                            <td class="text-end fw-semibold text-success"><?= e(money($row['total_sold'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php elseif ($current === 'employee_earnings'): ?>
    <div class="card">
        <div class="card-header"><h5 class="mb-0">Employee Earnings</h5></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Employee</th><th>Role</th><th class="text-center">Services</th><th class="text-center">Customers</th><th class="text-end">Earnings</th></tr></thead>
                <tbody>
                <?php $rows = $rows ?? []; ?>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No data in range</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($row['name']) ?></td>
                            <td><?= e($row['role'] ?: '—') ?></td>
                            <td class="text-center"><?= (int)$row['services'] ?></td>
                            <td class="text-center"><?= (int)$row['customers'] ?></td>
                            <td class="text-end fw-semibold text-success"><?= e(money($row['earnings'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php elseif ($current === 'service_revenue'): ?>
    <div class="card">
        <div class="card-header"><h5 class="mb-0">Service Revenue</h5></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Service</th><th class="text-center">Qty</th><th class="text-center">Customers</th><th class="text-end">Revenue</th></tr></thead>
                <tbody>
                <?php $rows = $rows ?? []; ?>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">No data in range</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($row['service_name']) ?></td>
                            <td class="text-center"><?= (int)$row['qty'] ?></td>
                            <td class="text-center"><?= (int)$row['customers'] ?></td>
                            <td class="text-end fw-semibold text-success"><?= e(money($row['revenue'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php elseif ($current === 'outstanding'): ?>
    <div class="card">
        <div class="card-header"><h5 class="mb-0">Outstanding Balances</h5></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Customer</th><th>Phone</th><th>Package</th><th class="text-end">Outstanding</th><th>Last Visit</th></tr></thead>
                <tbody>
                <?php $rows = $rows ?? []; ?>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No outstanding balances</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><a href="<?= e(url('/customers/' . (int)$row['id'])) ?>" class="fw-semibold text-decoration-none"><?= e($row['name']) ?></a></td>
                            <td><?= e($row['phone']) ?></td>
                            <td><?= e($row['current_package'] ?: '—') ?></td>
                            <td class="text-end fw-semibold text-danger"><?= e(money($row['outstanding'])) ?></td>
                            <td class="text-muted"><?= e(format_date($row['last_visit_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <?php $rows = $rows ?? []; ?>
    <div class="card">
        <div class="card-header"><h5 class="mb-0">Customer Statement</h5></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Date</th><th>Type</th><th>Description</th><th class="text-end">Amount</th><th class="text-end">Balance</th></tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Select a customer to view their statement</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <?php
                            $amount = (float) $row['amount'];
                            $bal = (float) $row['wallet_balance'];
                            $isCredit = !empty($row['is_credit']);
                            $desc = $row['package_name'] ?? ($row['invoice_number'] ?? '');
                            if ($row['type'] === 'debit') {
                                $desc = ($row['services'] ?: 'Transaction ' . ($row['invoice_number'] ?? ''));
                                if (!empty($row['employees'])) {
                                    $desc .= ' — by ' . $row['employees'];
                                }
                            } elseif (!empty($row['services'])) {
                                $desc = trim($row['services']) . ' — ' . $desc;
                            }
                        ?>
                        <tr>
                            <td class="text-muted text-nowrap"><?= e(format_datetime($row['created_at'])) ?></td>
                            <td><span class="badge bg-secondary-soft"><?= e($row['type']) ?></span></td>
                            <td><?= e($desc) ?></td>
                            <td class="text-end <?= $isCredit ? 'text-success' : 'text-danger' ?> fw-semibold">
                                <?= $isCredit ? '+' : '−' ?><?= e(money(abs($amount))) ?>
                            </td>
                            <td class="text-end <?= $bal < 0 ? 'text-danger' : ($bal > 0 ? 'text-success' : 'text-muted') ?> fw-semibold">
                                <?= $bal < 0 ? '−' : ($bal > 0 ? '+' : '') ?><?= e(money(abs($bal))) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<script>
    <?php if ($current === 'revenue'): ?>
    window.REPORT_SERIES = <?= json_encode($series ?? []) ?>;
    $(function () {
        const s = window.REPORT_SERIES || [];
        const el = document.getElementById('revenueChart');
        if (el && s.length) {
            new Chart(el, {
                type: 'bar',
                data: {
                    labels: s.map(r => r.day),
                    datasets: [{
                        label: 'Revenue (₹)',
                        data: s.map(r => Number(r.revenue)),
                        backgroundColor: 'rgba(16,185,129,.65)',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(100,116,139,.12)' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    });
    <?php endif; ?>
</script>
