<?php
/** @var array $customer @var array $stats @var array $activePackages @var array $allPackages
 *  @var array $invoices @var array $ledger @var array $recentServices @var array $notes @var array $templates
 */
?>
<div class="card mb-4">
    <div class="card-body p-3 p-lg-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3">
            <?php echo avatar_or_initials($customer['name'], $customer['photo'], 64); ?>
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h4 class="fw-bold mb-0"><?php echo e($customer['name']); ?></h4>
                    <span class="status-pill status-<?php echo e($customer['status']); ?>"><?php echo ucfirst($customer['status']); ?></span>
                </div>
                <div class="text-muted small mt-1 d-flex flex-wrap gap-3">
                    <span><i class="fa-solid fa-phone me-1"></i><?php echo e($customer['mobile']); ?></span>
                    <?php if ($customer['email']): ?><span><i class="fa-solid fa-envelope me-1"></i><?php echo e($customer['email']); ?></span><?php endif; ?>
                    <?php if ($customer['city']): ?><span><i class="fa-solid fa-location-dot me-1"></i><?php echo e($customer['city']); ?></span><?php endif; ?>
                    <span><i class="fa-solid fa-calendar-check me-1"></i>Member since <?php echo format_date($customer['created_at']); ?></span>
                </div>
            </div>
            <div class="d-flex gap-2">
                <?php if (can('customers.edit')): ?>
                    <a href="<?php echo url('/customers/' . $customer['id'] . '/edit'); ?>" class="btn btn-soft" onclick="event.preventDefault();NHS.editCustomer(<?php echo (int) $customer['id']; ?>)">
                        <i class="fa-solid fa-pen me-1"></i>Edit
                    </a>
                <?php endif; ?>
                <a href="<?php echo url('/billing?customer_id=' . $customer['id']); ?>" class="btn btn-primary">
                    <i class="fa-solid fa-bolt me-1"></i>Bill this customer
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl">
        <div class="summary-item"><div class="si-label">Lifetime Spend</div><div class="si-value"><?php echo money($stats['lifetime_spend']); ?></div></div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="summary-item"><div class="si-label">Total Visits</div><div class="si-value"><?php echo (int) $stats['visits']; ?></div></div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="summary-item"><div class="si-label">Available Balance</div><div class="si-value text-success"><?php echo money($stats['credits']); ?></div></div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="summary-item"><div class="si-label">Outstanding</div><div class="si-value <?php echo $stats['outstanding'] > 0 ? 'text-danger' : ''; ?>"><?php echo money($stats['outstanding']); ?></div></div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="summary-item"><div class="si-label">Current Package</div>
            <div class="si-value" style="font-size:.95rem">
                <?php if ($activePackages): echo e($activePackages[0]['name']); else: echo '<span class="text-muted">—</span>'; endif; ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="summary-item"><div class="si-label">Last Visit</div><div class="si-value" style="font-size:.95rem"><?php echo $customer['last_visit_at'] ? e(time_ago($customer['last_visit_at'])) : '—'; ?></div></div>
    </div>
</div>

<div class="card">
    <div class="card-header p-0">
        <ul class="nav nav-tabs px-3 pt-2" id="customerTabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabOverview">Overview</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabPackages">Packages <span class="badge bg-secondary-soft"><?php echo count($allPackages); ?></span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabBills">Statement</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabLedger">Wallet Ledger</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabNotes">Notes <span class="badge bg-secondary-soft"><?php echo count($notes); ?></span></button></li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">

            <!-- Overview -->
            <div class="tab-pane fade show active" id="tabOverview">
                <div class="row g-4">
                    <div class="col-lg-7">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-file-invoice me-2 text-success"></i>Recent Transactions</h6>
                        <?php if (empty($invoices)): ?>
                            <?php include APP_PATH . '/Views/partials/empty_state.php'; ?>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead><tr><th>Transaction</th><th>Date</th><th class="text-end">Total</th><th class="text-end">Balance</th><th>Status</th></tr></thead>
                                    <tbody>
                                    <?php foreach (array_slice($invoices, 0, 6) as $inv): ?>
                                        <tr>
                                            <td><a href="<?php echo url('/billing/invoice/' . $inv['id']); ?>" class="fw-semibold"><?php echo e($inv['invoice_number']); ?></a></td>
                                            <td class="text-nowrap"><?php echo format_date($inv['invoice_date']); ?></td>
                                            <td class="text-end"><?php echo money($inv['total']); ?></td>
                                            <td class="text-end <?php echo $inv['balance'] > 0 ? 'text-danger fw-semibold' : ''; ?>"><?php echo money($inv['balance']); ?></td>
                                            <td><span class="status-pill status-<?php echo e($inv['status']); ?>"><?php echo ucwords(str_replace('_', ' ', $inv['status'])); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-lg-5">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-crown me-2 text-warning"></i>Package Card</h6>
                        <?php if ($activePackages): ?>
                            <?php foreach ($activePackages as $pkg): ?>
                                <?php
                                $pct = $pkg['credits'] > 0 ? ($pkg['remaining_credits'] / $pkg['credits']) * 100 : 0;
                                ?>
                                <div class="package-card active-pkg mb-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="fw-bold"><?php echo e($pkg['name']); ?></div>
                                            <small class="text-muted">Purchased <?php echo format_date($pkg['starts_on']); ?> · ₹<?php echo number_format((float) $pkg['selling_price'], 2); ?></small>
                                        </div>
                                        <span class="status-pill status-active">Active</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-3 mb-1">
                                        <small class="text-muted"><?php echo (float) $pkg['remaining_credits']; ?> / <?php echo (int) $pkg['credits']; ?> credits left</small>
                                        <small class="fw-semibold text-success"><?php echo money((float) $pkg['balance_value']); ?></small>
                                    </div>
                                    <div class="package-credit-bar"><div style="width:<?php echo max(0, min(100, $pct)); ?>%"></div></div>
                                    <div class="d-flex justify-content-between mt-2">
                                        <small class="text-muted">Expires <?php echo $pkg['expires_on'] ? format_date($pkg['expires_on']) : '—'; ?></small>
                                        <small class="<?php echo ($pkg['days_left'] ?? 999) < 7 ? 'text-danger fw-semibold' : 'text-muted'; ?>">
                                            <?php echo ($pkg['days_left'] ?? 999) >= 0 ? ($pkg['days_left'] . ' days left') : 'Expired'; ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-4 border rounded-3">
                                <i class="fa-solid fa-box-open fa-2x mb-2 d-block text-muted-2"></i>
                                No active package. <a href="#" onclick="NHS.assignPackage()" class="fw-semibold">Assign one</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <h6 class="fw-bold mt-4 mb-3"><i class="fa-solid fa-wand-magic-sparkles me-2 text-primary"></i>Recent Services</h6>
                <?php if (empty($recentServices)): ?>
                    <p class="text-muted small">No services yet.</p>
                <?php else: ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($recentServices as $rs): ?>
                            <span class="badge bg-secondary-soft px-3 py-2">
                                <i class="fa-solid fa-check text-success me-1"></i><?php echo e($rs['service_name'] ?? $rs['description']); ?>
                                <span class="ms-2"><?php echo money($rs['amount']); ?></span>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Packages -->
            <div class="tab-pane fade" id="tabPackages">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">All Packages</h6>
                    <?php if (can('packages.create')): ?>
                        <button class="btn btn-primary btn-sm" onclick="NHS.assignPackage()"><i class="fa-solid fa-plus me-1"></i>Assign Package</button>
                    <?php endif; ?>
                </div>
                <?php if (empty($allPackages)): ?>
                    <?php include APP_PATH . '/Views/partials/empty_state.php'; ?>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($allPackages as $pkg): ?>
                            <div class="col-md-6 col-xl-4">
                                <div class="package-card <?php echo $pkg['status'] === 'active' ? 'active-pkg' : ''; ?> h-100">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <div class="fw-bold"><?php echo e($pkg['name']); ?></div>
                                            <small class="text-muted"><?php echo money((float) $pkg['selling_price']); ?></small>
                                        </div>
                                        <span class="status-pill status-<?php echo e($pkg['status']); ?>"><?php echo ucfirst($pkg['status']); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span class="text-muted">Credits</span><span class="fw-semibold"><?php echo (float) $pkg['remaining_credits']; ?> / <?php echo (int) $pkg['credits']; ?></span>
                                    </div>
                                    <div class="package-credit-bar">
                                        <div style="width:<?php echo $pkg['credits'] > 0 ? max(0, min(100, ($pkg['remaining_credits'] / $pkg['credits']) * 100)) : 0; ?>%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between mt-2 small text-muted">
                                        <span>Start: <?php echo format_date($pkg['starts_on']); ?></span>
                                        <span>Expiry: <?php echo $pkg['expires_on'] ? format_date($pkg['expires_on']) : '—'; ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Statement -->
            <div class="tab-pane fade" id="tabBills">
                <?php if (empty($invoices)): ?>
                    <?php include APP_PATH . '/Views/partials/empty_state.php'; ?>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Transaction</th><th>Date</th><th class="text-end">Subtotal</th><th class="text-end">Total</th><th class="text-end">Wallet Charged</th><th class="text-end">Paid</th><th class="text-end">Balance</th><th>Status</th><th>By</th></tr></thead>
                            <tbody>
                            <?php foreach ($invoices as $inv): ?>
                                <tr>
                                    <td><a href="<?php echo url('/billing/invoice/' . $inv['id']); ?>" class="fw-semibold"><?php echo e($inv['invoice_number']); ?></a></td>
                                    <td class="text-nowrap"><?php echo format_date($inv['invoice_date']); ?></td>
                                    <td class="text-end"><?php echo money($inv['subtotal']); ?></td>
                                    <td class="text-end fw-semibold"><?php echo money($inv['total']); ?></td>
                                    <td class="text-end"><?php echo money($inv['package_used']); ?></td>
                                    <td class="text-end"><?php echo money($inv['paid']); ?></td>
                                    <td class="text-end <?php echo $inv['balance'] > 0 ? 'text-danger fw-semibold' : ''; ?>"><?php echo money($inv['balance']); ?></td>
                                    <td><span class="status-pill status-<?php echo e($inv['status']); ?>"><?php echo ucwords(str_replace('_', ' ', $inv['status'])); ?></span></td>
                                    <td class="small text-muted"><?php echo e($inv['created_by_name'] ?? '—'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Ledger -->
            <div class="tab-pane fade" id="tabLedger">
                <?php if (empty($ledger)): ?>
                    <?php include APP_PATH . '/Views/partials/empty_state.php'; ?>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Date</th><th>Description</th><th>Type</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Balance</th></tr></thead>
                            <tbody>
                            <?php foreach (array_reverse($ledger) as $entry): ?>
                                <tr>
                                    <td class="text-nowrap"><?php echo format_date($entry['created_at']); ?></td>
                                    <td><?php echo e($entry['description'] ?? '—'); ?></td>
                                    <td><span class="badge bg-info-soft"><?php echo ucfirst($entry['type']); ?></span></td>
                                    <td class="text-end"><?php echo (float) $entry['amount'] < 0 ? money(abs((float) $entry['amount'])) : '—'; ?></td>
                                    <td class="text-end text-success"><?php echo (float) $entry['amount'] >= 0 ? money($entry['amount']) : '—'; ?></td>
                                    <td class="text-end fw-semibold"><?php echo money($entry['balance']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Notes -->
            <div class="tab-pane fade" id="tabNotes">
                <form id="noteForm" class="mb-4">
                    <?php echo csrf_field(); ?>
                    <div class="input-group">
                        <textarea class="form-control" id="noteText" rows="2" placeholder="Add a note about this customer…" required></textarea>
                        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-plus me-1"></i>Add Note</button>
                    </div>
                </form>
                <?php if (empty($notes)): ?>
                    <?php include APP_PATH . '/Views/partials/empty_state.php'; ?>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($notes as $note): ?>
                            <div class="d-flex gap-3 align-items-start border rounded-3 p-3">
                                <?php echo avatar_or_initials($note['created_by_name'] ?? 'Staff', '', 32); ?>
                                <div class="flex-grow-1">
                                    <p class="mb-1"><?php echo nl2br(e($note['note'])); ?></p>
                                    <small class="text-muted"><?php echo e($note['created_by_name'] ?? 'Staff'); ?> · <?php echo format_datetime($note['created_at']); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- Assign Package Modal -->
<div class="modal fade" id="assignPackageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="assignPackageForm">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Package</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Package Source</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="source" id="sourcePredefined" value="predefined" checked>
                                <label class="form-check-label" for="sourcePredefined">Predefined Package</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="source" id="sourceCustom" value="custom">
                                <label class="form-check-label" for="sourceCustom">Custom Package</label>
                            </div>
                        </div>
                    </div>

                    <div id="predefinedFields">
                        <label class="form-label">Choose Package <span class="text-danger">*</span></label>
                        <select class="form-select" name="package_id" id="packageSelect">
                            <option value="">Select a package…</option>
                            <?php foreach ($templates as $tpl): ?>
                                <option value="<?php echo (int) $tpl['id']; ?>" data-price="<?php echo (float) $tpl['selling_price']; ?>" data-credits="<?php echo (int) $tpl['credits']; ?>" data-validity="<?php echo (int) $tpl['validity_days']; ?>">
                                    <?php echo e($tpl['name']); ?> — ₹<?php echo number_format((float) $tpl['selling_price'], 2); ?> (<?php echo (int) $tpl['credits']; ?> credits, <?php echo (int) $tpl['validity_days']; ?> days)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="row g-3 mt-1" id="packagePreview"></div>
                    </div>

                    <div id="customFields" style="display:none">
                        <div class="row g-3">
                            <div class="col-md-7"><label class="form-label">Package Name <span class="text-danger">*</span></label>
                                <input class="form-control" name="name" placeholder="e.g. Bridal Special"></div>
                            <div class="col-md-5"><label class="form-label">Selling Price (₹)</label>
                                <input class="form-control" name="selling_price" type="number" step="0.01" min="0"></div>
                            <div class="col-md-6"><label class="form-label">Credits</label>
                                <input class="form-control" name="credits" type="number" min="1"></div>
                            <div class="col-md-6"><label class="form-label">Validity (days)</label>
                                <input class="form-control" name="validity_days" type="number" min="1" value="30"></div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>Assign Package</button>
                </div>
            </form>
        </div>
    </div>
</div>
