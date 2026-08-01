<?php
/** @var array|null $customer */
$customer = $customer ?? null;
$isEdit   = $customer !== null;
?>
<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title"><?= $isEdit ? 'Edit Customer' : 'New Customer' ?></h5>
                <a href="<?= e(url('/customers')) ?>" class="btn btn-sm btn-link text-muted text-decoration-none">
                    <i class="fa-solid fa-arrow-left me-1"></i>Back to list
                </a>
            </div>
            <div class="card-body">
                <form method="post" action="<?= e(url($isEdit ? '/customers/' . $customer['id'] . '/update' : '/customers')) ?>"
                      novalidate data-enter-submit>
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="name" class="form-label">Full name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name"
                                   value="<?= e(old('name', $customer['name'] ?? '')) ?>" required>
                            <?php if ($err = error('name')): ?><div class="invalid-feedback d-block"><?= e($err) ?></div><?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?= e(old('email', $customer['email'] ?? '')) ?>">
                            <?php if ($err = error('email')): ?><div class="invalid-feedback d-block"><?= e($err) ?></div><?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label">Mobile number</label>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                   value="<?= e(old('phone', $customer['mobile'] ?? '')) ?>">
                            <?php if ($err = error('phone')): ?><div class="invalid-feedback d-block"><?= e($err) ?></div><?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="address" name="address"
                                   value="<?= e(old('address', $customer['address'] ?? '')) ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="dob" class="form-label">Date of birth</label>
                            <input type="date" class="form-control" id="dob" name="dob"
                                   value="<?= e(old('dob', $customer['dob'] ?? '')) ?>">
                            <?php if ($err = error('dob')): ?><div class="invalid-feedback d-block"><?= e($err) ?></div><?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-select" id="gender" name="gender">
                                <option value="">Not specified</option>
                                <?php foreach (['male', 'female', 'other'] as $g): ?>
                                    <option value="<?= e($g) ?>" <?= old('gender', $customer['gender'] ?? '') === $g ? 'selected' : '' ?>>
                                        <?= e(ucfirst($g)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="notes" class="form-label">Internal notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?= e(old('notes', $customer['notes'] ?? '')) ?></textarea>
                            <div class="form-hint mt-1">Visible only to staff.</div>
                        </div>

                        <?php if ($isEdit): ?>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?= $customer['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $customer['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="status" value="active">
                        <?php endif; ?>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= e(url('/customers')) ?>" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-check me-2"></i><?= $isEdit ? 'Save Changes' : 'Create Customer' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
