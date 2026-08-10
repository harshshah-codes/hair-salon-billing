<div class="page-header">
    <div>
        <h1>Dashboard</h1>
        <p>Quick access to everything.</p>
    </div>
    <div class="page-actions">
        <a href="<?= e(url('/billing')) ?>" class="btn btn-primary">
            <i class="fa-solid fa-bolt me-2"></i>New Bill
        </a>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <label class="form-label fw-semibold" for="dashboardCustomerSearch"><i class="fa-solid fa-magnifying-glass me-1"></i>Find a customer</label>
        <div class="position-relative">
            <input type="text" class="form-control form-control-lg" id="dashboardCustomerSearch"
                   placeholder="Search by name or mobile…" autocomplete="off">
            <div id="dashboardCustomerResults" class="list-group position-absolute w-100 d-none"
                 style="z-index:1050;max-height:340px;overflow-y:auto;box-shadow:0 .5rem 1rem rgba(0,0,0,.15)"></div>
        </div>
        <div class="form-text">Select a customer to jump straight into billing, or create a new one.</div>
    </div>
</div>

<div class="row g-3">
    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
        <a href="<?= e(url('/billing')) ?>" class="card quick-link">
            <div class="card-body text-center py-4">
                <span class="stat-icon bg-primary-soft text-primary"><i class="fa-solid fa-bolt"></i></span>
                <div class="fw-semibold mt-2">Billing</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
        <a href="<?= e(url('/billing/history')) ?>" class="card quick-link">
            <div class="card-body text-center py-4">
                <span class="stat-icon bg-success-soft text-success"><i class="fa-solid fa-receipt"></i></span>
                <div class="fw-semibold mt-2">History</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
        <a href="<?= e(url('/customers')) ?>" class="card quick-link">
            <div class="card-body text-center py-4">
                <span class="stat-icon bg-info-soft text-info"><i class="fa-solid fa-users"></i></span>
                <div class="fw-semibold mt-2">Customers</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
        <a href="<?= e(url('/employees')) ?>" class="card quick-link">
            <div class="card-body text-center py-4">
                <span class="stat-icon bg-warning-soft text-warning"><i class="fa-solid fa-user-tie"></i></span>
                <div class="fw-semibold mt-2">Employees</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
        <a href="<?= e(url('/packages')) ?>" class="card quick-link">
            <div class="card-body text-center py-4">
                <span class="stat-icon bg-secondary-soft text-secondary"><i class="fa-solid fa-box-open"></i></span>
                <div class="fw-semibold mt-2">Packages</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
        <a href="<?= e(url('/services')) ?>" class="card quick-link">
            <div class="card-body text-center py-4">
                <span class="stat-icon bg-success-soft text-success"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
                <div class="fw-semibold mt-2">Services</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
        <a href="<?= e(url('/reports')) ?>" class="card quick-link">
            <div class="card-body text-center py-4">
                <span class="stat-icon bg-info-soft text-info"><i class="fa-solid fa-chart-line"></i></span>
                <div class="fw-semibold mt-2">Reports</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
        <a href="<?= e(url('/settings')) ?>" class="card quick-link">
            <div class="card-body text-center py-4">
                <span class="stat-icon bg-secondary-soft text-secondary"><i class="fa-solid fa-gear"></i></span>
                <div class="fw-semibold mt-2">Settings</div>
            </div>
        </a>
    </div>
</div>

<?php include APP_PATH . '/Views/partials/create_customer_modal.php'; ?>
