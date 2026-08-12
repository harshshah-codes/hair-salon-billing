<?php /** @var array $package */ ?>
<div class="col-12">
    <label class="form-label">Package Name <span class="text-danger">*</span></label>
    <input class="form-control" name="name" value="<?php echo e($package ? $package['name'] : ''); ?>" required>
</div>
<div class="col-4">
    <label class="form-label">Selling Price (₹) <span class="text-danger">*</span></label>
    <input class="form-control" name="selling_price" type="number" step="0.01" min="0" value="<?php echo e($package ? $package['selling_price'] : ''); ?>" required>
</div>
<div class="col-4">
    <label class="form-label">Credits <span class="text-danger">*</span></label>
    <input class="form-control" name="credits" type="number" min="1" value="<?php echo e($package ? (int) $package['credits'] : ''); ?>" required>
</div>
<div class="col-4">
    <label class="form-label">Validity (days)</label>
    <input class="form-control" name="validity_days" type="number" min="1" value="<?php echo e($package ? (int) $package['validity_days'] : ''); ?>">
</div>
<div class="col-6">
    <label class="form-label">Status</label>
    <select class="form-select" name="status">
        <option value="active" <?php echo $package && $package['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
        <option value="inactive" <?php echo $package && $package['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
    </select>
</div>
<div class="col-12">
    <label class="form-label">Description</label>
    <textarea class="form-control" name="description" rows="3"><?php echo e($package ? $package['description'] : ''); ?></textarea>
</div>
