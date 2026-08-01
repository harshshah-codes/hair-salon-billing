<?php /** @var array|null $service @var array $categories */ ?>
<div class="col-12">
    <label class="form-label">Service Name <span class="text-danger">*</span></label>
    <input class="form-control" name="name" value="<?php echo e($service['name'] ?? ''); ?>" required placeholder="e.g. Hair Cut & Styling">
</div>
<div class="col-md-6">
    <label class="form-label">Category</label>
    <input class="form-control" name="category" list="serviceCategories" value="<?php echo e($service['category'] ?? ''); ?>" placeholder="Hair / Skin / Spa…">
    <datalist id="serviceCategories">
        <?php foreach ($categories as $cat): ?>
            <option value="<?php echo e($cat['category']); ?>"></option>
        <?php endforeach; ?>
    </datalist>
</div>
<div class="col-md-3">
    <label class="form-label">Duration (minutes) <span class="text-danger">*</span></label>
    <input class="form-control" name="duration_minutes" type="number" min="5" value="<?php echo (int) ($service['duration_minutes'] ?? 30); ?>" required>
</div>
<div class="col-md-3">
    <label class="form-label">Default Price (₹) <span class="text-danger">*</span></label>
    <input class="form-control" name="price" type="number" step="0.01" min="0" value="<?php echo e($service['price'] ?? ''); ?>" required>
</div>
<div class="col-md-6">
    <label class="form-label">Status</label>
    <select class="form-select" name="status">
        <option value="active" <?php echo ($service['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
        <option value="inactive" <?php echo ($service['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
    </select>
</div>
<div class="col-12">
    <label class="form-label">Description</label>
    <textarea class="form-control" name="description" rows="3" placeholder="What's included?"><?php echo e($service['description'] ?? ''); ?></textarea>
</div>
