<div class="modal fade" id="createCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-user-plus me-2"></i>Create Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createCustomerForm" novalidate>
                    <?php echo csrf_field(); ?>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="ccName">Full name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="ccName" name="name" required>
                            <div class="invalid-feedback d-none" data-error-for="name"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="ccPhone">Mobile</label>
                            <input type="tel" class="form-control" id="ccPhone" name="phone">
                            <div class="invalid-feedback d-none" data-error-for="phone"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="ccEmail">Email</label>
                            <input type="email" class="form-control" id="ccEmail" name="email">
                            <div class="invalid-feedback d-none" data-error-for="email"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="ccAddress">Address</label>
                            <input type="text" class="form-control" id="ccAddress" name="address">
                        </div>
                    </div>

                    <hr class="my-4">

                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-box-open me-2"></i>Sell a Package <span class="text-muted small fw-normal">(optional — select a package to auto-fill, values are editable)</span></h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="ccPackageId">Package Template</label>
                            <select class="form-select" id="ccPackageId">
                                <option value="">Select a package…</option>
                                <?php foreach ($templates as $tpl): ?>
                                    <option value="<?php echo (int) $tpl['id']; ?>"
                                            data-price="<?php echo (float) $tpl['selling_price']; ?>"
                                            data-credits="<?php echo (int) $tpl['credits']; ?>"
                                            data-validity="<?php echo (int) $tpl['validity_days']; ?>">
                                        <?php echo e($tpl['name']); ?> — ₹<?php echo number_format((float) $tpl['selling_price'], 2); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="ccSoldBy">Sold By <span class="text-danger">*</span> <span class="text-muted small fw-normal">(required to sell a package)</span></label>
                            <select class="form-select" id="ccSoldBy" name="package_sold_by">
                                <option value="">Select staff…</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?php echo (int) $emp['id']; ?>"><?php echo e($emp['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback d-none" data-error-for="package_sold_by"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="ccPackageName">Package Name</label>
                            <input type="text" class="form-control" id="ccPackageName" name="package_name" placeholder="e.g. Platinum">
                            <div class="invalid-feedback d-none" data-error-for="package_name"></div>
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label" for="ccPackagePrice">Amount (₹)</label>
                            <input type="number" class="form-control" id="ccPackagePrice" name="package_price" step="0.01" min="0">
                            <div class="invalid-feedback d-none" data-error-for="package_price"></div>
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label" for="ccPackageCredits">Credits</label>
                            <input type="number" class="form-control" id="ccPackageCredits" name="package_credits" min="1">
                            <div class="invalid-feedback d-none" data-error-for="package_credits"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="ccPackageValidity">Validity (days)</label>
                            <input type="number" class="form-control" id="ccPackageValidity" name="package_validity_days" min="1" placeholder="Lifetime">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="ccPackageDate">Purchase Date</label>
                            <input type="date" class="form-control" id="ccPackageDate" name="package_purchase_date">
                            <div class="form-text">Backdated entries supported.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="ccPackageNotes">Notes</label>
                            <input type="text" class="form-control" id="ccPackageNotes" name="package_notes" placeholder="Optional">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnCreateCustomer">
                    <i class="fa-solid fa-check me-1"></i>Create Customer
                </button>
            </div>
        </div>
    </div>
</div>
