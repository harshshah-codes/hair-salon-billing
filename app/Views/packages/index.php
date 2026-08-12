<?php
/** @var array $packages @var array $usage @var array $paginator @var string $search @var string $status */
?>
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Packages</h4>
        <p class="text-muted mb-0 small">Reusable package templates you assign to customers.</p>
    </div>
    <?php if (can('packages.create')): ?>
        <button type="button" class="btn btn-primary" id="btnAddPackage">
            <i class="fa-solid fa-plus me-1"></i> Create Package
        </button>
    <?php endif; ?>
</div>

<form class="filter-bar" method="get" id="packageFilters">
    <div class="input-group" style="max-width:320px">
        <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
        <input type="search" class="form-control" name="search" value="<?php echo e($search); ?>" placeholder="Search packages…">
    </div>
    <select class="form-select" name="status">
        <option value="">All Status</option>
        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
    </select>
    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter me-1"></i>Apply</button>
</form>

<div class="card table-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th>Package Name</th>
                    <th class="text-end">Selling Price</th>
                    <th class="text-center">Credits</th>
                    <th class="text-center">Validity</th>
                    
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($packages)): ?>
                    <tr><td colspan="7">
                        <?php include APP_PATH . '/Views/partials/empty_state.php'; ?>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($packages as $package): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?php echo e($package['name']); ?></div>
                                <?php if ($package['description']): ?>
                                    <small class="text-muted"><?php echo read_more($package['description'], 60); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-semibold"><?php echo money($package['selling_price']); ?></td>
                            <td class="text-center"><span class="badge bg-success-soft"><?php echo (int) $package['credits']; ?></span></td>
                            <td class="text-center"><?php echo $package['validity_days'] === 0 ? 'Lifetime' : (int) $package['validity_days'] . ' days'; ?></td>
                            <td><span class="status-pill status-<?php echo e($package['status']); ?>"><?php echo ucfirst($package['status']); ?></span></td>
                            <td class="text-end text-nowrap">
                                <?php if (can('packages.edit')): ?>
                                    <button class="btn btn-sm btn-icon btn-edit-package" data-id="<?php echo (int) $package['id']; ?>" title="Edit"><i class="fa-solid fa-pen"></i></button>
                                <?php endif; ?>
                                <?php if (can('packages.delete')): ?>
                                    <button class="btn btn-sm btn-icon text-danger btn-delete-package"
                                            data-url="<?php echo url('/packages/' . $package['id'] . '/delete'); ?>"
                                            data-name="<?php echo e($package['name']); ?>" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include APP_PATH . '/Views/partials/pagination.php'; ?>

<!-- Package Modal -->
<div class="modal fade" id="packageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="packageForm">
                <input type="hidden" name="id" id="packageId">
                <div class="modal-header">
                    <h5 class="modal-title" id="packageModalTitle">Create Package</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3" id="packageFormFields"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Save Package</button>
                </div>
            </form>
        </div>
    </div>
</div>
