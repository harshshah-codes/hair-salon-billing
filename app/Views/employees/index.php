<?php
/** @var array $employees @var array $revenue @var array $paginator @var string $search @var string $status */
?>
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Employees</h4>
        <p class="text-muted mb-0 small">Your team, their roles and earnings.</p>
    </div>
    <?php if (can('employees.create')): ?>
        <button type="button" class="btn btn-primary" id="btnAddEmployee">
            <i class="fa-solid fa-plus me-1"></i> Add Employee
        </button>
    <?php endif; ?>
</div>

<form class="filter-bar" method="get">
    <div class="input-group" style="max-width:300px">
        <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
        <input type="search" class="form-control" name="search" value="<?php echo e($search); ?>" placeholder="Search employees…">
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
                    <th>Photo</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th class="text-end">Revenue</th>
                    <th class="text-center">Services Completed</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($employees)): ?>
                    <tr><td colspan="8">
                        <?php include APP_PATH . '/Views/partials/empty_state.php'; ?>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td><?php echo avatar_or_initials($employee['name'], $employee['photo'], 38); ?></td>
                            <td>
                                <a href="<?php echo url('/employees/' . $employee['id']); ?>" class="fw-semibold text-decoration-none"><?php echo e($employee['name']); ?></a>
                            </td>
                            <td class="text-nowrap"><?php echo e($employee['mobile']); ?></td>
                            <td><?php echo $employee['designation'] ? e($employee['designation']) : '<span class="text-muted small">—</span>'; ?></td>
                            <td class="text-end fw-semibold text-success"><?php echo money($revenue[$employee['id']]['revenue'] ?? 0); ?></td>
                            <td class="text-center">
                                <span class="badge bg-info-soft"><?php echo (int) ($revenue[$employee['id']]['services'] ?? 0); ?></span>
                            </td>
                            <td><span class="status-pill status-<?php echo e($employee['status']); ?>"><?php echo ucfirst($employee['status']); ?></span></td>
                            <td class="text-end text-nowrap">
                                <a href="<?php echo url('/employees/' . $employee['id']); ?>" class="btn btn-sm btn-icon" title="Profile"><i class="fa-solid fa-id-badge"></i></a>
                                <?php if (can('employees.edit')): ?>
                                    <button class="btn btn-sm btn-icon btn-edit-employee" data-id="<?php echo (int) $employee['id']; ?>" title="Edit"><i class="fa-solid fa-pen"></i></button>
                                <?php endif; ?>
                                <?php if (can('employees.delete')): ?>
                                    <button class="btn btn-sm btn-icon text-danger btn-delete-employee"
                                            data-url="<?php echo url('/employees/' . $employee['id'] . '/delete'); ?>"
                                            data-name="<?php echo e($employee['name']); ?>" title="Delete"><i class="fa-solid fa-trash"></i></button>
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

<!-- Employee Modal -->
<div class="modal fade" id="employeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="employeeForm" enctype="multipart/form-data">
                <input type="hidden" name="id" id="employeeId">
                <div class="modal-header">
                    <h5 class="modal-title" id="employeeModalTitle">Add Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3" id="employeeFormFields"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Save Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>
