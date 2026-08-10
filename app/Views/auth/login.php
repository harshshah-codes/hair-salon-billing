<?php /** @var string $title */ ?>
<div class="text-center mb-4">
    <h2 class="auth-welcome-title">Welcome back</h2>
    <p class="text-muted small mb-0">Sign in to continue to your dashboard</p>
</div>

<form method="post" action="<?php echo url('/auth/login'); ?>" autocomplete="off">
    <?php echo csrf_field(); ?>
    <div class="mb-3">
        <label class="form-label">Email address</label>
        <div class="input-group input-group-lg">
            <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
            <input type="email" name="email" class="form-control" placeholder="you@salon.com" required autofocus>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Password</label>
        <div class="input-group input-group-lg">
            <span class="input-group-text"><i class="fa-solid fa-key"></i></span>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Branch</label>
        <div class="input-group input-group-lg">
            <span class="input-group-text"><i class="fa-solid fa-store"></i></span>
            <select name="branch_id" class="form-select" required>
                <option value="">Select branch…</option>
                <?php foreach ($branches as $branch): ?>
                    <option value="<?php echo (int) $branch['id']; ?>"><?php echo e($branch['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <button type="submit" class="btn btn-primary btn-lg w-100 mt-2">
        <i class="fa-solid fa-right-to-bracket me-2"></i>Sign In
    </button>
</form>
