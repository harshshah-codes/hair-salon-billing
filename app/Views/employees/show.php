<?php
/** @var array $employee @var array $stats @var array $recent @var array $earningsSeries @var array $monthly @var array $servicesCount */
?>
<script>
    window.EMP_EARNINGS_SERIES = {
        labels: <?php echo json_encode(array_column($earningsSeries, 'date')); ?>,
        values: <?php echo json_encode(array_map(fn ($r) => (float) $r['total'], $earningsSeries)); ?>
    };
</script>
<div class="card mb-4">
    <div class="card-body p-3 p-lg-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3">
            <?php echo avatar_or_initials($employee['name'], $employee['photo'], 64); ?>
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h4 class="fw-bold mb-0"><?php echo e($employee['name']); ?></h4>
                    <span class="status-pill status-<?php echo e($employee['status']); ?>"><?php echo ucfirst($employee['status']); ?></span>
                </div>
                <div class="text-muted small mt-1 d-flex flex-wrap gap-3">
                    <span><i class="fa-solid fa-briefcase me-1"></i><?php echo e($employee['designation'] ?? 'Staff'); ?></span>
                    <span><i class="fa-solid fa-phone me-1"></i><?php echo e($employee['mobile']); ?></span>
                    <?php if ($employee['email']): ?><span><i class="fa-solid fa-envelope me-1"></i><?php echo e($employee['email']); ?></span><?php endif; ?>
                    <?php if ($employee['joined_at']): ?><span><i class="fa-solid fa-calendar-check me-1"></i>Joined <?php echo format_date($employee['joined_at']); ?></span><?php endif; ?>
                    <span><i class="fa-solid fa-percent me-1"></i>Commission <?php echo number_format((float) $employee['commission_rate'], 2); ?>%</span>
                </div>
            </div>
            <?php if (can('employees.edit')): ?>
                <button class="btn btn-soft" onclick="NHS.editEmployee(<?php echo (int) $employee['id']; ?>)"><i class="fa-solid fa-pen me-1"></i>Edit</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card stat-card"><div class="card-body">
            <div class="d-flex align-items-center gap-3">
                <span class="stat-icon bg-success-soft text-success"><i class="fa-solid fa-sack-dollar"></i></span>
                <div><div class="stat-value"><?php echo money($stats['revenue']); ?></div><div class="stat-label">Revenue Generated</div></div>
            </div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card"><div class="card-body">
            <div class="d-flex align-items-center gap-3">
                <span class="stat-icon bg-info-soft text-info"><i class="fa-solid fa-users"></i></span>
                <div><div class="stat-value"><?php echo (int) $stats['customers']; ?></div><div class="stat-label">Customers Served</div></div>
            </div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card"><div class="card-body">
            <div class="d-flex align-items-center gap-3">
                <span class="stat-icon bg-warning-soft text-warning"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
                <div><div class="stat-value"><?php echo (int) $stats['services']; ?></div><div class="stat-label">Services Completed</div></div>
            </div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card"><div class="card-body">
            <div class="d-flex align-items-center gap-3">
                <span class="stat-icon bg-primary-soft text-primary"><i class="fa-solid fa-bolt"></i></span>
                <div><div class="stat-value"><?php echo money($stats['today']); ?></div><div class="stat-label">Today's Earnings</div></div>
            </div>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-header p-0">
        <ul class="nav nav-tabs px-3 pt-2">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#eOverview">Overview</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#eServices">Services</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#eEarnings">Earnings</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#eAllocations">Billing Allocation</button></li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane fade show active" id="eOverview">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <h6 class="fw-bold mb-3">Earnings — Last 30 Days</h6>
                        <div style="height:260px"><canvas id="empEarningsChart"></canvas></div>
                    </div>
                    <div class="col-lg-6">
                        <h6 class="fw-bold mb-3">Monthly Earnings</h6>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead><tr><th>Month</th><th class="text-end">Earnings</th></tr></thead>
                                <tbody>
                                <?php if (empty($monthly)): ?>
                                    <tr><td colspan="2" class="text-center text-muted">No earnings yet</td></tr>
                                <?php else: ?>
                                    <?php foreach ($monthly as $m): ?>
                                        <tr><td><?php echo date('M Y', strtotime($m['month'] . '-01')); ?></td>
                                            <td class="text-end fw-semibold"><?php echo money($m['total']); ?></td></tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="eServices">
                <h6 class="fw-bold mb-3">Most Completed Services</h6>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Service</th><th class="text-center">Times</th><th class="text-end">Allocated</th></tr></thead>
                        <tbody>
                        <?php if (empty($servicesCount)): ?>
                            <tr><td colspan="3" class="text-center text-muted">No services completed yet</td></tr>
                        <?php else: ?>
                            <?php foreach ($servicesCount as $s): ?>
                                <tr><td><?php echo e($s['service']); ?></td>
                                    <td class="text-center"><?php echo (int) $s['count']; ?></td>
                                    <td class="text-end fw-semibold"><?php echo money($s['total']); ?></td></tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="eEarnings">
                <h6 class="fw-bold mb-3">Earnings Breakdown</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="summary-item"><div class="si-label">Total Revenue</div><div class="si-value"><?php echo money($stats['revenue']); ?></div></div>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-item"><div class="si-label">Commission @ <?php echo number_format((float) $employee['commission_rate'], 0); ?>%</div>
                            <div class="si-value"><?php echo money($stats['revenue'] * (float) $employee['commission_rate'] / 100); ?></div></div>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-item"><div class="si-label">Invoices Handled</div><div class="si-value"><?php echo (int) $stats['invoices']; ?></div></div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="eAllocations">
                <h6 class="fw-bold mb-3">Recent Billing Allocations</h6>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Invoice</th><th>Date</th><th>Customer</th><th>Service</th><th class="text-end">Allocated</th></tr></thead>
                        <tbody>
                        <?php if (empty($recent)): ?>
                            <tr><td colspan="5" class="text-center text-muted">No allocations yet</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent as $r): ?>
                                <tr>
                                    <td><a href="<?php echo url('/billing/invoice/' . (int) $r['invoice_id']); ?>" class="fw-semibold"><?php echo e($r['invoice_number']); ?></a></td>
                                    <td class="text-nowrap"><?php echo format_date($r['invoice_date']); ?></td>
                                    <td><?php echo e($r['customer_name']); ?></td>
                                    <td><?php echo e($r['service']); ?></td>
                                    <td class="text-end fw-semibold text-success"><?php echo money($r['amount']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
