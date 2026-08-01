<?php
/** @var array|null $customer @var bool $isEdit */
$c = $customer ?: [];
?>
<div class="col-md-6">
    <label class="form-label">Full Name <span class="text-danger">*</span></label>
    <input class="form-control" name="name" value="<?php echo e($c['name'] ?? ''); ?>" required placeholder="e.g. Priya Sharma">
</div>
<div class="col-md-6">
    <label class="form-label">Mobile <span class="text-danger">*</span></label>
    <input class="form-control" name="mobile" value="<?php echo e($c['mobile'] ?? ''); ?>" required placeholder="10-digit mobile">
</div>
<div class="col-md-6">
    <label class="form-label">Email</label>
    <input class="form-control" name="email" type="email" value="<?php echo e($c['email'] ?? ''); ?>" placeholder="customer@email.com">
</div>
<div class="col-md-3">
    <label class="form-label">Gender</label>
    <select class="form-select" name="gender">
        <option value="">—</option>
        <?php foreach (['male', 'female', 'other'] as $g): ?>
            <option value="<?php echo $g; ?>" <?php echo ($c['gender'] ?? '') === $g ? 'selected' : ''; ?>><?php echo ucfirst($g); ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="col-md-3">
    <label class="form-label">Date of Birth</label>
    <input class="form-control" name="dob" type="date" value="<?php echo e($c['dob'] ?? ''); ?>">
</div>
<div class="col-md-7">
    <label class="form-label">Address</label>
    <input class="form-control" name="address" value="<?php echo e($c['address'] ?? ''); ?>" placeholder="Street, area">
</div>
<div class="col-md-5">
    <label class="form-label">City</label>
    <input class="form-control" name="city" value="<?php echo e($c['city'] ?? ''); ?>" placeholder="City">
</div>
<div class="col-md-6">
    <label class="form-label">Status</label>
    <select class="form-select" name="status">
        <option value="active" <?php echo ($c['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
        <option value="inactive" <?php echo ($c['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
    </select>
</div>
<div class="col-md-6">
    <label class="form-label">Photo</label>
    <input class="form-control" name="photo" type="file" accept="image/*">
    <?php if (!empty($c['photo'])): ?>
        <small class="text-muted">Current: <?php echo e(basename($c['photo'])); ?></small>
    <?php endif; ?>
</div>
<div class="col-12">
    <label class="form-label">Notes</label>
    <textarea class="form-control" name="notes" rows="3" placeholder="Preferences, allergies, notes…"><?php echo e($c['notes'] ?? ''); ?></textarea>
</div>
