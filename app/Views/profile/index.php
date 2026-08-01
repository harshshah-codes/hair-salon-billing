<?php /** @var array $user */ ?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card text-center h-100">
            <div class="card-body py-4">
                <?php echo avatar_or_initials($user['name'], $user['avatar'], 84); ?>
                <h5 class="mt-3 mb-0 fw-bold"><?php echo e($user['name']); ?></h5>
                <div class="text-muted small"><?php echo e($user['email']); ?></div>
                <span class="badge bg-success-soft text-success mt-2"><?php echo e($user['role_name'] ?? 'Member'); ?></span>
            </div>
            <div class="card-footer bg-transparent d-flex justify-content-around py-3">
                <div class="text-center">
                    <div class="fw-bold fs-5"><?php echo $user['last_login_at'] ? e(format_datetime($user['last_login_at'])) : '—'; ?></div>
                    <small class="text-muted">Last sign in</small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-transparent"><h6 class="mb-0 fw-bold">Account Details</h6></div>
            <div class="card-body">
                <form method="post" action="<?php echo url('/profile'); ?>">
                    <?php echo csrf_field(); ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input class="form-control" name="name" value="<?php echo e($user['name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input class="form-control" name="phone" value="<?php echo e($user['phone']); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Email</label>
                            <input class="form-control" value="<?php echo e($user['email']); ?>" disabled>
                            <small class="text-muted">Email cannot be changed.</small>
                        </div>
                        <div class="col-12"><hr></div>
                        <div class="col-md-4">
                            <label class="form-label">Current Password</label>
                            <input class="form-control" type="password" name="current_password" autocomplete="current-password">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">New Password</label>
                            <input class="form-control" type="password" name="new_password" autocomplete="new-password">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button class="btn btn-primary" type="submit">Save Changes</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
