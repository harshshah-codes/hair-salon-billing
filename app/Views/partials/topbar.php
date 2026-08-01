<?php
/** @var string $title */
$user = auth_user() ?: ['name' => 'User', 'email' => '', 'role_slug' => '', 'avatar' => ''];
$breadcrumbs = $breadcrumbs ?? [];
?>
<header class="app-topbar">
    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-icon d-lg-none" type="button" id="sidebarToggle" aria-label="Open menu">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="d-none d-sm-block">
            <nav class="breadcrumb-nav" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?php echo url('/dashboard'); ?>"><i class="fa-solid fa-house"></i></a></li>
                    <?php $count = count($breadcrumbs); $i = 0; ?>
                    <?php foreach ($breadcrumbs as $label => $href): ?>
                        <?php $i++; ?>
                        <?php if ($i === $count): ?>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo e($label); ?></li>
                        <?php else: ?>
                            <li class="breadcrumb-item"><a href="<?php echo e($href); ?>"><?php echo e($label); ?></a></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </nav>
            <h1 class="page-title"><?php echo e($title); ?></h1>
        </div>
    </div>

    <div class="d-flex align-items-center gap-2">
        <?php if (can('billing.create')): ?>
            <a href="<?php echo url('/billing'); ?>" class="btn btn-primary btn-sm d-none d-sm-inline-flex align-items-center gap-1">
                <i class="fa-solid fa-plus"></i> New Bill
            </a>
        <?php endif; ?>

        <button class="btn btn-icon theme-toggle" type="button" id="themeToggle" title="Toggle theme" aria-label="Toggle theme">
            <i class="fa-solid fa-moon"></i>
            <i class="fa-solid fa-sun" style="display:none"></i>
        </button>

        <div class="dropdown">
            <button class="btn btn-icon position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Activity">
                <i class="fa-solid fa-bell"></i>
                <span class="badge-dot"></span>
            </button>
            <div class="dropdown-menu dropdown-menu-end notification-menu">
                <div class="px-3 py-2 fw-semibold border-bottom">Recent Activity</div>
                <div class="notification-list" data-activity-list>
                    <div class="px-3 py-4 text-center text-muted small">
                        <i class="fa-solid fa-spinner fa-spin me-1"></i> Loading…
                    </div>
                </div>
                <a class="dropdown-item text-center small py-2 border-top" href="<?php echo url('/settings/activity'); ?>">View all activity</a>
            </div>
        </div>

        <div class="dropdown">
            <button class="btn btn-sm d-flex align-items-center gap-2 user-chip" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <?php echo avatar_or_initials($user['name'], $user['avatar'] ?? '', 32); ?>
                <span class="d-none d-md-block text-start">
                    <span class="d-block fw-semibold lh-sm"><?php echo e($user['name']); ?></span>
                    <span class="d-block text-muted small lh-sm"><?php echo e($user['email']); ?></span>
                </span>
                <i class="fa-solid fa-chevron-down small text-muted"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li><a class="dropdown-item" href="<?php echo url('/profile'); ?>"><i class="fa-solid fa-user me-2"></i>My Profile</a></li>
                <li><a class="dropdown-item" href="<?php echo url('/settings'); ?>"><i class="fa-solid fa-gear me-2"></i>Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="post" action="<?php echo url('/auth/logout'); ?>">
                        <?php echo csrf_field(); ?>
                        <button class="dropdown-item text-danger" type="submit"><i class="fa-solid fa-right-from-bracket me-2"></i>Sign Out</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
