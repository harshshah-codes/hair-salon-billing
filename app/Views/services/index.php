<?php
/** @var array $services @var array $paginator @var array $categories @var string $search @var string $category @var string $status */
?>
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Services</h4>
        <p class="text-muted mb-0 small">Your salon's service catalog with pricing.</p>
    </div>
    <?php if (can('services.create')): ?>
        <button type="button" class="btn btn-primary" id="btnAddService">
            <i class="fa-solid fa-plus me-1"></i> Create Service
        </button>
    <?php endif; ?>
</div>

<form class="filter-bar" method="get">
    <div class="input-group" style="max-width:300px">
        <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
        <input type="search" class="form-control" name="search" value="<?php echo e($search); ?>" placeholder="Search services…">
    </div>
    <select class="form-select" name="category">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?php echo e($cat['category']); ?>" <?php echo $category === $cat['category'] ? 'selected' : ''; ?>><?php echo e($cat['category']); ?></option>
        <?php endforeach; ?>
    </select>
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
                    <th>Service</th>
                    <th>Category</th>
                    <th class="text-center">Duration</th>
                    <th class="text-end">Default Price</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($services)): ?>
                    <tr><td colspan="6">
                        <?php include APP_PATH . '/Views/partials/empty_state.php'; ?>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($services as $service): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="icon-sq bg-success-soft text-success"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
                                    <div>
                                        <div class="fw-semibold"><?php echo e($service['name']); ?></div>
                                        <?php if ($service['description']): ?>
                                            <small class="text-muted"><?php echo read_more($service['description'], 55); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo $service['category'] ? '<span class="badge bg-secondary-soft">' . e($service['category']) . '</span>' : '<span class="text-muted small">—</span>'; ?></td>
                            <td class="text-center">
                                <?php if ($service['duration_minutes'] >= 60): ?>
                                    <?php echo floor($service['duration_minutes'] / 60) . 'h ' . ($service['duration_minutes'] % 60 ? ($service['duration_minutes'] % 60) . 'm' : ''); ?>
                                <?php else: ?>
                                    <?php echo (int) $service['duration_minutes']; ?> min
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-semibold"><?php echo money($service['price']); ?></td>
                            <td><span class="status-pill status-<?php echo e($service['status']); ?>"><?php echo ucfirst($service['status']); ?></span></td>
                            <td class="text-end text-nowrap">
                                <?php if (can('services.edit')): ?>
                                    <button class="btn btn-sm btn-icon btn-edit-service" data-id="<?php echo (int) $service['id']; ?>" title="Edit"><i class="fa-solid fa-pen"></i></button>
                                <?php endif; ?>
                                <?php if (can('services.delete')): ?>
                                    <button class="btn btn-sm btn-icon text-danger btn-delete-service"
                                            data-url="<?php echo url('/services/' . $service['id'] . '/delete'); ?>"
                                            data-name="<?php echo e($service['name']); ?>" title="Delete"><i class="fa-solid fa-trash"></i></button>
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

<!-- Service Modal -->
<div class="modal fade" id="serviceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="serviceForm">
                <input type="hidden" name="id" id="serviceId">
                <div class="modal-header">
                    <h5 class="modal-title" id="serviceModalTitle">Create Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3" id="serviceFormFields"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Save Service</button>
                </div>
            </form>
        </div>
    </div>
</div>
