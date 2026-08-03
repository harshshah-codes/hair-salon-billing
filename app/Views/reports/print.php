<?php
/**
 * Printable / printable-friendly report output (plain, no layout).
 * @var string $type
 * @var string $from
 * @var string $to
 * @var array  $series
 * @var array  $totals
 * @var array  $detail
 * @var array  $rows
 */
$typeLabels = [
    'revenue'           => 'Revenue Report',
    'package_sales'     => 'Package Sales Report',
    'employee_earnings' => 'Employee Earnings Report',
    'service_revenue'   => 'Service Revenue Report',
    'outstanding'       => 'Outstanding Balances Report',
    'statements'        => 'Customer Statement',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= e($typeLabels[$type] ?? 'Report') ?></title>
<style>
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; color: #111; margin: 0; padding: 32px; font-size: 13px; }
    h1 { margin: 0 0 4px; font-size: 22px; }
    .meta { color: #555; font-size: 12px; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th { background: #f2f2f2; text-align: left; padding: 8px 10px; text-transform: uppercase; font-size: 11px; }
    td { padding: 7px 10px; border-bottom: 1px solid #e5e5e5; }
    td.num, th.num { text-align: right; }
    td.center, th.center { text-align: center; }
    .totals { width: 340px; margin-left: auto; margin-top: 16px; border-top: 2px solid #111; }
    .totals .row { display: flex; justify-content: space-between; padding: 4px 0; }
    .totals .row strong { font-weight: bold; }
    @media print { body { padding: 0; } }
</style>
</head>
<body>
<h1><?= e(setting('business_name', 'Nirav Hair Storm')) ?></h1>
<div class="meta">
    <?= e($typeLabels[$type] ?? 'Report') ?> · Period: <?= e(format_date($from)) ?> — <?= e(format_date($to)) ?>
    <br>Generated: <?= e(format_datetime(date('Y-m-d H:i:s'))) ?>
</div>

<?php if ($type === 'revenue'): ?>
    <table>
        <thead><tr><th>Date</th><th class="num">Revenue</th><th class="num">Package Used</th><th class="num">Bills</th></tr></thead>
        <tbody>
        <?php foreach ($series as $row): ?>
            <tr>
                <td><?= e(format_date($row['day'])) ?></td>
                <td class="num"><?= e(money($row['revenue'])) ?></td>
                <td class="num"><?= e(money($row['package_used'])) ?></td>
                <td class="num"><?= (int)$row['bills'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="totals">
        <div class="row"><span>Total Revenue</span><strong><?= e(money($totals['revenue'] ?? 0)) ?></strong></div>
        <div class="row"><span>Package Deduction</span><strong><?= e(money($totals['package_deduction'] ?? 0)) ?></strong></div>
        <div class="row"><span>GST Collected</span><strong><?= e(money($totals['gst'] ?? 0)) ?></strong></div>
        <div class="row"><span>Bills</span><strong><?= (int)($totals['bill_count'] ?? 0) ?></strong></div>
    </div>
<?php elseif ($type === 'package_sales'): ?>
    <table>
        <thead><tr><th>Package</th><th class="center">Units</th><th class="num">Total Value</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e($row['package_name']) ?></td>
                <td class="center"><?= (int)$row['units'] ?></td>
                <td class="num"><?= e(money($row['total_sold'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php elseif ($type === 'employee_earnings'): ?>
    <table>
        <thead><tr><th>Employee</th><th>Role</th><th class="center">Services</th><th class="center">Customers</th><th class="num">Earnings</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e($row['name']) ?></td>
                <td><?= e($row['role'] ?: '—') ?></td>
                <td class="center"><?= (int)$row['services'] ?></td>
                <td class="center"><?= (int)$row['customers'] ?></td>
                <td class="num"><?= e(money($row['earnings'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php elseif ($type === 'service_revenue'): ?>
    <table>
        <thead><tr><th>Service</th><th class="center">Qty</th><th class="center">Customers</th><th class="num">Revenue</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e($row['service_name']) ?></td>
                <td class="center"><?= (int)$row['qty'] ?></td>
                <td class="center"><?= (int)$row['customers'] ?></td>
                <td class="num"><?= e(money($row['revenue'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php elseif ($type === 'outstanding'): ?>
    <table>
        <thead><tr><th>Customer</th><th>Phone</th><th>Package</th><th class="num">Outstanding</th><th>Last Visit</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e($row['name']) ?></td>
                <td><?= e($row['phone']) ?></td>
                <td><?= e($row['current_package'] ?: '—') ?></td>
                <td class="num"><?= e(money($row['outstanding'])) ?></td>
                <td><?= e(format_date($row['last_visit_at'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <table>
        <thead><tr><th>Date</th><th>Type</th><th>Description</th><th class="num">Amount</th><th class="num">Balance</th></tr></thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="5" style="text-align:center;color:#888">Select a customer to view their statement</td></tr>
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
                    <td><?= e(format_datetime($row['created_at'])) ?></td>
                    <td><?= e($row['type']) ?></td>
                    <td><?= e($desc) ?></td>
                    <td class="num"><?= $isCredit ? '+' : '−' ?><?= e(money(abs($amount))) ?></td>
                    <td class="num"><?= $bal < 0 ? '−' : ($bal > 0 ? '+' : '') ?><?= e(money(abs($bal))) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
<?php endif; ?>

<script>window.print();</script>
</body>
</html>
