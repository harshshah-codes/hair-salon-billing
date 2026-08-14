<?php /** @var string $active */ ?>
<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <a href="<?php echo url('/dashboard'); ?>" class="d-flex align-items-center gap-2 text-decoration-none">
            <span class="brand-mark">
                <i class="fa-solid fa-scissors"></i>
            </span>
            <span class="brand-name">Nirav <em>Hairstorm</em></span>
        </a>
        <button class="btn btn-icon d-lg-none" type="button" id="sidebarClose" aria-label="Close menu">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <span class="nav-label">Main</span>
        <a class="nav-item <?php echo active_class('dashboard', $active ?? ''); ?>" href="<?php echo url('/dashboard'); ?>">
            <i class="fa-solid fa-chart-pie"></i><span>Dashboard</span>
        </a>

        <span class="nav-label">Operations</span>
        <a class="nav-item <?php echo active_class('billing', $active ?? ''); ?>" href="<?php echo url('/billing'); ?>">
            <i class="fa-solid fa-bolt"></i><span>Billing</span>
        </a>
        <a class="nav-item <?php echo active_class('customers', $active ?? ''); ?>" href="<?php echo url('/customers'); ?>">
            <i class="fa-solid fa-users"></i><span>Customers</span>
        </a>
        <a class="nav-item <?php echo active_class('employees', $active ?? ''); ?>" href="<?php echo url('/employees'); ?>">
            <i class="fa-solid fa-user-tie"></i><span>Employees</span>
        </a>

        <span class="nav-label">Catalog</span>
        <a class="nav-item <?php echo active_class('packages', $active ?? ''); ?>" href="<?php echo url('/packages'); ?>">
            <i class="fa-solid fa-box-open"></i><span>Packages</span>
        </a>
        <a class="nav-item <?php echo active_class('services', $active ?? ''); ?>" href="<?php echo url('/services'); ?>">
            <i class="fa-solid fa-wand-magic-sparkles"></i><span>Services</span>
        </a>

        <span class="nav-label">Insights</span>
        <a class="nav-item <?php echo active_class('reports', $active ?? ''); ?>" href="<?php echo url('/reports'); ?>">
            <i class="fa-solid fa-chart-line"></i><span>Reports</span>
        </a>

        <?php if (can('settings.view')): ?>
        <span class="nav-label">System</span>
        <a class="nav-item <?php echo active_class('settings', $active ?? ''); ?>" href="<?php echo url('/settings'); ?>">
            <i class="fa-solid fa-gear"></i><span>Settings</span>
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <?php $u = auth_user() ?: ['name' => 'User', 'email' => '']; ?>
            <?php echo avatar_or_initials($u['name'], $u['avatar'] ?? '', 36); ?>
            <div class="sidebar-user-meta">
                <span class="fw-semibold text-truncate"><?php echo e($u['name']); ?></span>
                <small class="text-muted text-truncate"><?php echo e(ucwords(str_replace('-', ' ', $u['role_slug'] ?? ''))); ?></small>
            </div>
        </div>
    </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
