<?php
/** @var array $employees @var array $services @var float $gstPercent @var int $preselectCustomerId */
?>
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">New Bill</h4>
        <p class="text-muted mb-0 small">Add services, allocate to employees and collect payment — all in one place.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo url('/billing/history'); ?>" class="btn btn-light"><i class="fa-solid fa-receipt me-1"></i>Invoice History</a>
    </div>
</div>

<div class="d-flex flex-wrap gap-3 align-items-start pos-layout">
    <!-- ============ LEFT 70% ============ -->
    <div class="pos-left">
        <!-- Customer lookup -->
        <div class="card mb-3">
            <div class="card-header bg-transparent py-2">
                <span class="fw-bold"><i class="fa-solid fa-magnifying-glass me-2 text-success"></i>Customer Lookup</span>
            </div>
            <div class="card-body">
                <div class="customer-search-wrap position-relative">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                        <input type="text" class="form-control" id="customerSearchInput"
                               placeholder="Search customer by name or mobile…" autocomplete="off">
                        <button class="btn btn-outline-secondary" type="button" id="customerSearchClear" title="Clear">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="customer-results card position-absolute w-100 shadow-lg d-none" id="customerResults"></div>
                </div>

                <!-- Selected customer card -->
                <div class="d-none" id="customerCard"></div>
            </div>
        </div>

        <!-- Services -->
        <div class="card">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-2">
                <span class="fw-bold"><i class="fa-solid fa-wand-magic-sparkles me-2 text-success"></i>Services</span>
                <button type="button" class="btn btn-soft btn-sm" id="btnAddServiceRow">
                    <i class="fa-solid fa-plus me-1"></i>Add Service
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table billing-items-table align-middle mb-0" id="billingItemsTable">
                        <thead>
                        <tr>
                            <th style="min-width:240px">Service</th>
                            <th style="width:110px" class="text-end">Price (₹)</th>
                            <th style="width:70px" class="text-center">Qty</th>
                            <th style="min-width:180px">Employees</th>
                            <th style="width:40px"></th>
                        </tr>
                        </thead>
                        <tbody id="billingItemsBody"><tr class="text-center text-muted"><td colspan="5" style="padding:34px">
                            <i class="fa-solid fa-plus fa-2x d-block mb-2 text-muted-2"></i>
                            Click <span class="fw-semibold">"Add Service"</span> to start billing.
                        </td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ RIGHT 30% ============ -->
    <div class="pos-right">
        <div class="pos-summary-card">
            <div class="summary-head"><i class="fa-solid fa-credit-card"></i> Bill Summary</div>
            <div class="summary-body">
                <!-- Package balance -->
                <div class="mb-3">
                    <div class="fw-bold small text-uppercase text-muted mb-2">Package Balance</div>
                    <div class="package-strip">
                        <div>
                            <div class="text-muted small">Available Balance</div>
                            <div class="fs-5 fw-bold text-success" id="pkgAvailable">₹0.00</div>
                        </div>
                        <div class="text-end">
                            <div class="text-muted small">Current Package</div>
                            <div class="fw-semibold small" id="pkgName">—</div>
                        </div>
                    </div>
                </div>

                <div class="payment-totals mb-3">
                    <div class="total-row"><span>Subtotal</span><span class="fw-semibold" id="tSubtotal">₹0.00</span></div>
                    <div class="total-row align-items-center">
                        <span>Discount</span>
                        <div class="d-flex align-items-center gap-1" style="width:130px">
                            <span class="small">₹</span>
                            <input type="number" class="form-control form-control-sm text-end" id="tDiscount" min="0" step="0.01" value="0">
                        </div>
                    </div>
                    <div class="total-row align-items-center">
                        <span>GST</span>
                        <div class="d-flex align-items-center gap-1" style="width:130px">
                            <input type="number" class="form-control form-control-sm text-end" id="tGstPercent" min="0" max="100" step="0.01" value="<?php echo (float) $gstPercent; ?>">
                            <span class="small">%</span>
                        </div>
                    </div>
                    <div class="total-row"><span class="text-muted">GST Amount</span><span id="tGstAmount">₹0.00</span></div>
                    <div class="total-row"><span class="text-muted">Total</span><span class="fw-semibold" id="tTotal">₹0.00</span></div>

                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" id="tUsePackage">
                        <label class="form-check-label small" for="tUsePackage">Pay from package credits</label>
                    </div>
                    <div class="total-row align-items-center" id="pkgRow" style="display:none">
                        <span>Package Deduction</span>
                        <div class="d-flex align-items-center gap-1" style="width:130px">
                            <span class="small">₹</span>
                            <input type="number" class="form-control form-control-sm text-end" id="tPackageUsed" min="0" step="0.01" value="0">
                        </div>
                    </div>
                    <div class="total-row"><span class="text-muted">Balance After Billing</span><span class="fw-semibold text-success" id="tBalanceAfter">₹0.00</span></div>
                    <div class="total-row"><span class="text-muted">Outstanding</span><span class="fw-semibold text-danger" id="tOutstanding">₹0.00</span></div>
                    <div class="total-row grand"><span>Amount Payable</span><span class="amount" id="tPayable">₹0.00</span></div>
                </div>

                <!-- Payments -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-bold small text-uppercase text-muted">Payment</div>
                        <button type="button" class="btn btn-sm btn-soft" id="btnAddPayment"><i class="fa-solid fa-plus me-1"></i>Split</button>
                    </div>
                    <div id="paymentRows"></div>
                    <div class="d-flex justify-content-between small mt-2 pt-2 border-top">
                        <span class="text-muted">Total Received</span>
                        <span class="fw-bold" id="tReceived">₹0.00</span>
                    </div>
                    <div class="d-flex justify-content-between small text-muted">
                        <span>Balance Due</span>
                        <span class="fw-semibold" id="tDue">₹0.00</span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" id="billNotes" rows="2" placeholder="Optional note on this bill"></textarea>
                </div>

                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary btn-lg" id="btnGenerateInvoice">
                        <i class="fa-solid fa-file-invoice me-2"></i>Generate Invoice
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light flex-fill" id="btnSaveDraft">Save Draft</button>
                        <button type="button" class="btn btn-light flex-fill" id="btnCancelBill">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Allocation modal -->
<div class="modal fade" id="allocModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Employee Allocation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3 d-flex justify-content-between align-items-center bg-light p-3 rounded-3">
                    <span class="text-muted">Service Total</span>
                    <span class="fs-5 fw-bold" id="allocServiceTotal">₹0.00</span>
                </div>
                <div id="allocRows"></div>
                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                    <span class="fw-semibold">Total Allocation</span>
                    <span class="fs-5 fw-bold" id="allocSum">₹0.00</span>
                </div>
                <div id="allocError" class="text-danger small mt-2 d-none">Allocation total must equal the service price.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="allocSave">Save Allocation</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.BILLING = {
        employees: <?php echo json_encode(array_map(fn ($e) => ['id' => (int) $e['id'], 'name' => $e['name']], $employees)); ?>,
        services: <?php echo json_encode(array_map(fn ($s) => ['id' => (int) $s['id'], 'name' => $s['name'], 'price' => (float) $s['price']], $services)); ?>,
        preselectCustomerId: <?php echo (int) $preselectCustomerId; ?>
    };
</script>
