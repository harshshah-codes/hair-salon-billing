<?php
/** @var array $employees @var int $preselectCustomerId */
?>
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">New Transaction</h4>
        <p class="text-muted mb-0 small">Enter the services performed, allocate employees and charge the wallet — all in one place.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo url('/billing/history'); ?>" class="btn btn-light"><i class="fa-solid fa-receipt me-1"></i>Transactions</a>
    </div>
</div>

<div class="pos-layout">
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

        <!-- Services (custom rows) -->
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
                            <th style="width:150px">Date</th>
                            <th style="min-width:220px">Service Name</th>
                            <th style="min-width:180px">Allocation</th>
                            <th style="width:130px" class="text-end">Price (₹)</th>
                            <th style="width:70px" class="text-center">Qty</th>
                            <th style="width:40px"></th>
                        </tr>
                        </thead>
                        <tbody id="billingItemsBody"><tr class="text-center text-muted"><td colspan="5" style="padding:34px">
                            <i class="fa-solid fa-plus fa-2x d-block mb-2 text-muted-2"></i>
                            Click <span class="fw-semibold">"Add Service"</span> and type the service name &amp; price.
                        </td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ RIGHT 30% ============ -->
    <div class="pos-right">
        <div class="pos-summary-card">
            <div class="summary-head"><i class="fa-solid fa-wallet"></i> Transaction Summary</div>
            <div class="summary-body">
                <!-- Wallet balance -->
                <div class="mb-3">
                    <div class="fw-bold small text-uppercase text-muted mb-2">Wallet Balance</div>
                    <div class="package-strip">
                        <div>
                            <div class="text-muted small">Available Balance</div>
                            <div class="fs-5 fw-bold text-success" id="pkgAvailable">₹0.00</div>
                        </div>
                        <div class="text-end">
                            <div class="text-muted small">Balance After</div>
                            <div class="fw-semibold" id="tBalanceAfter">₹0.00</div>
                        </div>
                    </div>
                </div>

                <div class="payment-totals mb-3">
                    <div class="total-row"><span>Total</span><span class="fw-semibold" id="tTotal">₹0.00</span></div>
                    <div class="total-row grand"><span>Charge to Wallet</span><span class="amount" id="tPayable">₹0.00</span></div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" id="billNotes" rows="2" placeholder="Optional note on this transaction"></textarea>
                </div>

                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary btn-lg" id="btnGenerateInvoice">
                        <i class="fa-solid fa-bolt me-2"></i>Create Transaction
                    </button>
                    <button type="button" class="btn btn-light" id="btnCancelBill">Cancel</button>
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

<!-- Create customer modal -->
<?php include APP_PATH . '/Views/partials/create_customer_modal.php'; ?>

<script>
    window.BILLING = {
        employees: <?php echo json_encode(array_map(fn ($e) => ['id' => (int) $e['id'], 'name' => $e['name']], $employees)); ?>,
        preselectCustomerId: <?php echo (int) $preselectCustomerId; ?>
    };
</script>
