<?php
/** @var string  $tab */
/** @var array   $settings */
/** @var array   $users */
/** @var array   $roles */
/** @var array   $permissions */
$tabs = [
    'business'    => 'Business',
    'invoice'     => 'Invoice',
    'preferences' => 'Preferences',
    'branches'    => 'Branches',
    'users'       => 'Users & Access',
    'roles'       => 'Roles',
];
$current = isset($tabs[$tab]) ? $tab : 'business';
$currentTheme = setting('theme_mode', 'light');
?>
<div class="page-header">
    <div>
        <h1>Settings</h1>
        <p>Business profile, invoices, users and access.</p>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body py-2">
        <ul class="nav nav-pills report-tabs flex-wrap">
            <?php foreach ($tabs as $key => $label): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $current === $key ? 'active' : '' ?>" href="<?= e(url('/settings?tab=' . $key)) ?>"><?= e($label) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<?php if ($current === 'business'): ?>
    <div class="card">
        <div class="card-header"><h5 class="mb-0">Business Information</h5></div>
        <div class="card-body">
            <form method="post" action="<?= e(url('/settings')) ?>" enctype="multipart/form-data" class="row g-3">
                <?= csrf_field() ?>
                <input type="hidden" name="section" value="business">
                <div class="col-md-6">
                    <label class="form-label">Business Name</label>
                    <input type="text" name="business_name" class="form-control" value="<?= e($settings['business_name'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">GST Rate (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="gst_rate" class="form-control" value="<?= e($settings['gst_rate'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input type="text" name="business_phone" class="form-control" value="<?= e($settings['business_phone'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="business_email" class="form-control" value="<?= e($settings['business_email'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">GST Number</label>
                    <input type="text" name="business_gst" class="form-control" value="<?= e($settings['business_gst'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Currency</label>
                    <select name="currency" class="form-select">
                        <option value="INR" <?= ($settings['currency'] ?? 'INR') === 'INR' ? 'selected' : '' ?>>INR (₹)</option>
                        <option value="USD" <?= ($settings['currency'] ?? '') === 'USD' ? 'selected' : '' ?>>USD ($)</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea name="business_address" class="form-control" rows="2"><?= e($settings['business_address'] ?? '') ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Logo</label>
                    <input type="file" name="business_logo" class="form-control" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                    <?php if (!empty($settings['business_logo'])): ?>
                        <div class="mt-2"><img src="<?= e(url($settings['business_logo'])) ?>" alt="Logo" style="max-height:48px" class="rounded"></div>
                    <?php endif; ?>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-save me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
<?php elseif ($current === 'invoice'): ?>
    <div class="card">
        <div class="card-header"><h5 class="mb-0">Invoice Preferences</h5></div>
        <div class="card-body">
            <form method="post" action="<?= e(url('/settings')) ?>" class="row g-3">
                <?= csrf_field() ?>
                <input type="hidden" name="section" value="invoice">
                <div class="col-md-6">
                    <label class="form-label">Invoice Prefix</label>
                    <input type="text" name="invoice_prefix" class="form-control" value="<?= e($settings['invoice_prefix'] ?? 'INV-') ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Invoice Footer Note</label>
                    <textarea name="invoice_footer" class="form-control" rows="2"><?= e($settings['invoice_footer'] ?? '') ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Terms & Conditions</label>
                    <textarea name="invoice_terms" class="form-control" rows="2"><?= e($settings['invoice_terms'] ?? '') ?></textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-save me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
<?php elseif ($current === 'preferences'): ?>
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Default Theme</h5></div>
                <div class="card-body">
                    <form method="post" action="<?= e(url('/settings')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="section" value="theme">
                        <div class="d-flex gap-3 mb-3">
                            <button type="button" class="btn theme-option" data-theme="light">
                                <i class="fa-solid fa-sun"></i> Light
                            </button>
                            <button type="button" class="btn theme-option" data-theme="dark">
                                <i class="fa-solid fa-moon"></i> Dark
                            </button>
                        </div>
                        <input type="hidden" name="theme" value="<?= e($currentTheme) ?>">
                        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-save me-1"></i>Save</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Recent Activity</h5></div>
                <div class="card-body p-0" data-activity-list>
                    <div class="px-3 py-4 text-center text-muted small">Loading…</div>
                </div>
            </div>
        </div>
    </div>
<?php elseif ($current === 'branches'): ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Branches</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Name</th><th>Address</th><th>Phone</th><th>Employees</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($branches as $branch): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($branch['name']) ?></td>
                        <td class="text-muted"><?= e($branch['address'] ?? '—') ?></td>
                        <td><?= e($branch['phone'] ?? '—') ?></td>
                        <td><?= (int) ($branch['employee_count'] ?? 0) ?></td>
                        <td><span class="status-pill status-<?= e($branch['status']) ?>"><?= e(ucfirst($branch['status'])) ?></span></td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#branchModal"
                                    data-id="<?= (int)$branch['id'] ?>"
                                    data-name="<?= e($branch['name']) ?>"
                                    data-address="<?= e($branch['address'] ?? '') ?>"
                                    data-phone="<?= e($branch['phone'] ?? '') ?>"
                                    data-status="<?= e($branch['status']) ?>"><i class="fa-solid fa-pen"></i></button>
                            <form method="post" action="<?= e(url('/settings')) ?>" class="d-inline" data-confirm data-confirm-message="Delete branch <?= e($branch['name']) ?>?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="section" value="branch_delete">
                                <input type="hidden" name="id" value="<?= (int)$branch['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header"><h5 class="mb-0">Add New Branch</h5></div>
        <div class="card-body">
            <form method="post" action="<?= e(url('/settings')) ?>" class="row g-3">
                <?= csrf_field() ?>
                <input type="hidden" name="section" value="branch_create">
                <div class="col-md-4">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Main Outlet" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" class="form-control">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-plus me-1"></i>Create Branch</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="branchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="<?= e(url('/settings')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="section" value="branch_update">
                    <input type="hidden" name="id" value="">
                    <div class="modal-header"><h5 class="modal-title">Edit Branch</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php elseif ($current === 'users'): ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Users</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Branch</th><th>Status</th><th>Last Login</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($user['name']) ?></td>
                        <td><?= e($user['email']) ?></td>
                        <td><?= e($user['phone'] ?? '—') ?></td>
                        <td><span class="badge bg-primary-soft"><?= e($user['role_name']) ?></span></td>
                        <td class="text-muted"><?= e($user['branch_name'] ?? '—') ?></td>
                        <td><span class="status-pill status-<?= e($user['status']) ?>"><?= e(ucfirst($user['status'])) ?></span></td>
                        <td class="text-muted"><?= e($user['last_login_at'] ? format_datetime($user['last_login_at']) : 'Never') ?></td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#userModal"
                                    data-id="<?= (int)$user['id'] ?>"
                                    data-name="<?= e($user['name']) ?>"
                                    data-email="<?= e($user['email']) ?>"
                                    data-phone="<?= e($user['phone'] ?? '') ?>"
                                    data-role="<?= (int)$user['role_id'] ?>"
                                    data-branch="<?= (int)($user['branch_id'] ?? 0) ?>"><i class="fa-solid fa-pen"></i></button>
                            <form method="post" action="<?= e(url('/settings')) ?>" class="d-inline" data-confirm data-confirm-message="Delete user <?= e($user['name']) ?>?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="section" value="user_delete">
                                <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header"><h5 class="mb-0">Add New User</h5></div>
        <div class="card-body">
            <form method="post" action="<?= e(url('/settings')) ?>" class="row g-3">
                <?= csrf_field() ?>
                <input type="hidden" name="section" value="user_create">
                <div class="col-md-4">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Role</label>
                    <select name="role_id" class="form-select" required>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= (int)$role['id'] ?>"><?= e($role['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-select">
                        <option value="">— All / None —</option>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?= (int)$branch['id'] ?>"><?= e($branch['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Password (min 8)</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-user-plus me-1"></i>Create User</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="<?= e(url('/settings')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="section" value="user_update">
                    <input type="hidden" name="id" value="">
                    <div class="modal-header"><h5 class="modal-title">Edit User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select name="role_id" class="form-select" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= (int)$role['id'] ?>"><?= e($role['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" class="form-select">
                                <option value="">— All / None —</option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?= (int)$branch['id'] ?>"><?= e($branch['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password <span class="text-muted small">(leave blank to keep)</span></label>
                            <input type="password" name="password" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header"><h5 class="mb-0">Roles & Permissions</h5></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Role</th><th>Permissions</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($roles as $role): ?>
                    <?php $rolePerms = json_decode($role['permissions'] ?? '[]', true) ?: []; ?>
                    <tr>
                        <td class="fw-semibold"><?= e($role['name']) ?></td>
                        <td class="small">
                            <?php if (($rolePerms['*'] ?? null) === true): ?>
                                <span class="badge bg-success-soft">All modules</span>
                            <?php elseif ($rolePerms === []): ?>
                                <span class="text-muted">No permissions</span>
                            <?php else: ?>
                                <span class="d-flex flex-wrap gap-1">
                                <?php foreach ($permissions as $section => $group): ?>
                                    <?php if (isset($rolePerms[$section])): ?>
                                        <?php $actions = $rolePerms[$section] === true ? ['*'] : (array)$rolePerms[$section]; ?>
                                        <?php foreach ($actions as $action): ?>
                                            <span class="badge bg-secondary-soft"><?= e($group['title']) ?> · <?= e($action) ?></span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#roleModal"
                                    data-id="<?= (int)$role['id'] ?>"
                                    data-name="<?= e($role['name']) ?>"
                                    data-perms="<?= e(json_encode($rolePerms, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>"><i class="fa-solid fa-shield-halved me-1"></i>Edit</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="roleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post" action="<?= e(url('/settings')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="section" value="roles">
                    <input type="hidden" name="role_id" value="">
                    <div class="modal-header"><h5 class="modal-title">Edit Role Permissions</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <p class="small text-muted mb-3">Role: <strong data-role-name></strong></p>
                        <div class="row g-3">
                            <?php foreach ($permissions as $section => $group): ?>
                                <div class="col-md-6">
                                    <div class="border rounded-3 p-3 h-100">
                                        <div class="form-check mb-1">
                                            <input class="form-check-input section-toggle" type="checkbox" id="sec-<?= e($section) ?>">
                                            <label class="form-check-label fw-semibold" for="sec-<?= e($section) ?>"><?= e($group['title']) ?></label>
                                        </div>
                                        <?php foreach ($group['actions'] as $action): ?>
                                            <div class="form-check ms-4">
                                                <input class="form-check-input role-perm" type="checkbox"
                                                       name="permissions[<?= e($section) ?>][]" value="<?= e($action) ?>"
                                                       id="perm-<?= e($section) ?>-<?= e($action) ?>">
                                                <label class="form-check-label" for="perm-<?= e($section) ?>-<?= e($action) ?>"><?= e(ucfirst($action)) ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    $(function () {
        $('.theme-option').on('click', function () {
            $('.theme-option').removeClass('btn-primary').addClass('btn-light');
            const t = $(this).addClass('btn-primary').removeClass('btn-light').data('theme');
            $('input[name="theme"]').val(t);
        });
        const activeTheme = '<?= e($currentTheme) ?>';
        $(`.theme-option[data-theme="${activeTheme}"]`).addClass('btn-primary').removeClass('btn-light');

        $('#userModal').on('show.bs.modal', function (e) {
            const b = $(e.relatedTarget);
            const m = $(this);
            m.find('input[name="id"]').val(b.data('id'));
            m.find('input[name="name"]').val(b.data('name'));
            m.find('input[name="email"]').val(b.data('email'));
            m.find('input[name="phone"]').val(b.data('phone'));
            m.find('select[name="role_id"]').val(String(b.data('role')));
            m.find('select[name="branch_id"]').val(String(b.data('branch') || ''));
        });

        $('#branchModal').on('show.bs.modal', function (e) {
            const b = $(e.relatedTarget);
            const m = $(this);
            m.find('input[name="id"]').val(b.data('id'));
            m.find('input[name="name"]').val(b.data('name'));
            m.find('input[name="phone"]').val(b.data('phone'));
            m.find('input[name="address"]').val(b.data('address'));
            m.find('select[name="status"]').val(b.data('status') || 'active');
        });

        $('#roleModal').on('show.bs.modal', function (e) {
            const b = $(e.relatedTarget);
            const m = $(this);
            m.find('input[name="role_id"]').val(b.data('id'));
            m.find('[data-role-name]').text(b.data('name'));
            m.find('.role-perm').prop('checked', false);
            let perms = {};
            try { perms = JSON.parse(b.data('perms') || '{}') || {}; } catch (err) { perms = {}; }
            Object.keys(perms).forEach((section) => {
                const actions = perms[section] === true ? ['*'] : (Array.isArray(perms[section]) ? perms[section] : []);
                actions.forEach((action) => {
                    m.find(`.role-perm[value="${action}"][name^="permissions[${section}]"]`).prop('checked', true);
                });
            });
            m.find('.section-toggle').each(function () {
                const section = this.id.replace('sec-', '');
                const any = $(this).closest('.border').find('.role-perm:checked').length > 0;
                $(this).prop('checked', any);
            });
        });
        $(document).on('change', '.section-toggle', function () {
            const checked = $(this).prop('checked');
            $(this).closest('.border').find('.role-perm').prop('checked', checked);
        });
        $(document).on('change', '.role-perm', function () {
            const box = $(this).closest('.border');
            const allChecked = box.find('.role-perm:checked').length === box.find('.role-perm').length;
            box.find('.section-toggle').prop('checked', allChecked);
        });
    });
</script>
