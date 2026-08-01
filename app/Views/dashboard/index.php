<?php
/** @var array $cards */
/** @var array $revenueChart */
/** @var array $employeeTop */
/** @var array $topServices */
/** @var array $recentBills */
/** @var array $lowBalance */
/** @var array $inactiveCustomers */
/** @var array $activities */
?>
<div class="page-header">
    <div>
        <h1>Dashboard</h1>
        <p>Here's what's happening at your salon today.</p>
    </div>
    <div class="page-actions">
        <a href="<?= e(url('/billing')) ?>" class="btn btn-primary">
            <i class="fa-solid fa-bolt me-2"></i>New Bill
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card stat-card"><div class="card-body">
            <div class="d-flex align-items-center gap-3">
                <span class="stat-icon bg-success-soft text-success"><i class="fa-solid fa-indian-rupee-sign"></i></span>
                <div><div class="stat-value"><?= e(money($cards['revenue_today'] ?? 0)) ?></div><div class="stat-label">Revenue Today</div></div>
            </div>
        </div></div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card stat-card"><div class="card-body">
            <div class="d-flex align-items-center gap-3">
                <span class="stat-icon bg-info-soft text-info"><i class="fa-solid fa-chart-line"></i></span>
                <div><div class="stat-value"><?= e(money($cards['revenue_month'] ?? 0)) ?></div><div class="stat-label">Revenue This Month</div></div>
            </div>
        </div></div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card stat-card"><div class="card-body">
            <div class="d-flex align-items-center gap-3">
                <span class="stat-icon bg-warning-soft text-warning"><i class="fa-solid fa-wallet"></i></span>
                <div><div class="stat-value"><?= e(money($cards['outstanding'] ?? 0)) ?></div><div class="stat-label">Total Outstanding</div></div>
            </div>
        </div></div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card stat-card"><div class="card-body">
            <div class="d-flex align-items-center gap-3">
                <span class="stat-icon bg-primary-soft text-primary"><i class="fa-solid fa-users"></i></span>
                <div><div class="stat-value"><?= (int)($cards['customers'] ?? 0) ?></div><div class="stat-label">Active Customers</div></div>
            </div>
        </div></div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card stat-card"><div class="card-body">
            <div class="d-flex align-items-center gap-3">
                <span class="stat-icon bg-success-soft text-success"><i class="fa-solid fa-scissors"></i></span>
                <div><div class="stat-value"><?= (int)($cards['services_today'] ?? 0) ?></div><div class="stat-label">Services Today</div></div>
            </div>
        </div></div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card stat-card"><div class="card-body">
            <div class="d-flex align-items-center gap-3">
                <span class="stat-icon bg-secondary-soft text-secondary"><i class="fa-solid fa-user-tie"></i></span>
                <div><div class="stat-value"><?= (int)($cards['employees_active'] ?? 0) ?></div><div class="stat-label">Active Staff</div></div>
            </div>
        </div></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Revenue — Last 30 Days</h5>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="120"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">Activity</h5></div>
            <div class="card-body p-0" data-activity-list>
                <?php if (empty($activities)): ?>
                    <div class="px-3 py-4 text-center text-muted small">No recent activity</div>
                <?php else: ?>
                    <?php foreach ($activities as $a): ?>
                        <div class="notification-item px-3 py-2 d-flex gap-2">
                            <i class="fa-solid fa-circle-info text-success mt-1" style="font-size:.6rem"></i>
                            <div class="small">
                                <div class="fw-semibold"><?= e($a['description'] ?? '') ?></div>
                                <div class="text-muted" style="font-size:.72rem"><?= e(time_ago($a['created_at'] ?? null)) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Recent Bills</h5></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Invoice</th><th>Customer</th><th class="text-end">Payable</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php if (empty($recentBills)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No settled bills yet</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentBills as $bill): ?>
                            <tr>
                                <td><a href="<?= e(url('/billing/invoice/' . (int)$bill['id'])) ?>" class="fw-semibold text-decoration-none"><?= e($bill['invoice_number']) ?></a></td>
                                <td><?= e($bill['customer_name']) ?></td>
                                <td class="text-end fw-semibold"><?= e(money($bill['payable'])) ?></td>
                                <td><span class="status-pill status-<?= e($bill['payment_status']) ?>"><?= e(ucfirst($bill['payment_status'])) ?></span></td>
                                <td class="text-muted text-nowrap"><?= e(format_date($bill['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Top Services</h5></div>
            <div class="card-body py-2">
                <?php if (empty($topServices)): ?>
                    <div class="text-muted small text-center py-3">No data yet</div>
                <?php else: ?>
                    <?php foreach ($topServices as $i => $s): ?>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <span class="rank-badge"><?= (int)($i + 1) ?></span>
                                <span class="fw-semibold small"><?= e($s['service_name']) ?></span>
                            </div>
                            <span class="small text-success fw-semibold"><?= e(money($s['revenue'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="mb-0">Staff Performance</h5></div>
            <div class="card-body py-2">
                <?php if (empty($employeeTop)): ?>
                    <div class="text-muted small text-center py-3">No data yet</div>
                <?php else: ?>
                    <?php foreach ($employeeTop as $emp): ?>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="fw-semibold small"><?= e($emp['name']) ?></span>
                            <span class="small text-success fw-semibold"><?= e(money($emp['earnings'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Low Package Balance</h5></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Customer</th><th>Package</th><th class="text-center">Credits Left</th></tr></thead>
                    <tbody>
                    <?php if (empty($lowBalance)): ?>
                        <tr><td colspan="3" class="text-center text-muted py-4">No low balances</td></tr>
                    <?php else: ?>
                        <?php foreach ($lowBalance as $lb): ?>
                            <tr>
                                <td><a href="<?= e(url('/customers/' . (int)$lb['id'])) ?>" class="fw-semibold text-decoration-none"><?= e($lb['name']) ?></a></td>
                                <td><?= e($lb['package_name']) ?></td>
                                <td class="text-center"><span class="badge bg-warning-soft"><?= (int)$lb['remaining_credits'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Inactive Customers (30+ days)</h5></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Customer</th><th>Phone</th><th class="text-end">Outstanding</th><th>Last Visit</th></tr></thead>
                    <tbody>
                    <?php if (empty($inactiveCustomers)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">All customers active recently</td></tr>
                    <?php else: ?>
                        <?php foreach ($inactiveCustomers as $ic): ?>
                            <tr>
                                <td><a href="<?= e(url('/customers/' . (int)$ic['id'])) ?>" class="fw-semibold text-decoration-none"><?= e($ic['name']) ?></a></td>
                                <td><?= e($ic['phone']) ?></td>
                                <td class="text-end text-danger fw-semibold"><?= e(money($ic['outstanding'])) ?></td>
                                <td class="text-muted"><?= e(format_date($ic['last_visit_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    window.DASHBOARD_REVENUE = <?= json_encode($revenueChart) ?>;
    $(function () {
        const d = window.DASHBOARD_REVENUE || { labels: [], values: [] };
        const el = document.getElementById('revenueChart');
        if (el && d.labels.length) {
            new Chart(el, {
                type: 'line',
                data: {
                    labels: d.labels,
                    datasets: [{
                        label: 'Revenue (₹)',
                        data: d.values,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16,185,129,.12)',
                        fill: true,
                        tension: .4,
                        pointRadius: 3,
                        pointBackgroundColor: '#10b981'
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
</script>
