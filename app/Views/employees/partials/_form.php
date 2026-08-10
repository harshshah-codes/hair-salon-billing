<?php /** @var array|null $employee @var array $branches */ ?>
<div class="col-md-6">
    <label class="form-label">Full Name <span class="text-danger">*</span></label>
    <input class="form-control" name="name" value="<?php echo e($employee['name'] ?? ''); ?>" required>
</div>
<div class="col-md-6">
    <label class="form-label">Branch <span class="text-danger">*</span></label>
    <select class="form-select" name="branch_id" required>
        <option value="">Select branch…</option>
        <?php foreach ($branches as $branch): ?>
            <option value="<?php echo (int) $branch['id']; ?>" <?php echo (int) ($employee['branch_id'] ?? 0) === (int) $branch['id'] ? 'selected' : ''; ?>>
                <?php echo e($branch['name']); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<div class="col-md-6">
    <label class="form-label">Mobile <span class="text-danger">*</span></label>
    <input class="form-control" name="mobile" value="<?php echo e($employee['mobile'] ?? ''); ?>" required>
</div>
<div class="col-md-6">
    <label class="form-label">Email</label>
    <input class="form-control" type="email" name="email" value="<?php echo e($employee['email'] ?? ''); ?>">
</div>
<div class="col-md-6">
    <label class="form-label">Role / Designation</label>
    <input class="form-control" name="designation" value="<?php echo e($employee['designation'] ?? ''); ?>" placeholder="e.g. Senior Stylist">
</div>
<div class="col-md-4">
    <label class="form-label">Commission Rate (%)</label>
    <div class="input-group">
        <input class="form-control" name="commission_rate" type="number" step="0.01" min="0" max="100" value="<?php echo e($employee['commission_rate'] ?? 0); ?>">
        <span class="input-group-text">%</span>
    </div>
</div>
<div class="col-md-4">
    <label class="form-label">Joined Date</label>
    <input class="form-control" type="date" name="joined_at" value="<?php echo e($employee['joined_at'] ?? ''); ?>">
</div>
<div class="col-md-4">
    <label class="form-label">Status</label>
    <select class="form-select" name="status">
        <option value="active" <?php echo ($employee['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
        <option value="inactive" <?php echo ($employee['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
    </select>
</div>
<div class="col-md-6">
    <label class="form-label">Photo</label>
    <input class="form-control" name="photo" type="file" accept="image/*">
</div>
