<?php /** @var string $title */ ?>
<div class="text-center mb-4">
    <div class="display-5 mb-2"><i class="fa-solid fa-lock text-success"></i></div>
    <h4 class="fw-bold">Welcome back</h4>
    <p class="text-muted small">Sign in to continue to your dashboard</p>
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
    <button type="submit" class="btn btn-primary btn-lg w-100 mt-2">
        <i class="fa-solid fa-right-to-bracket me-2"></i>Sign In
    </button>
</form>
