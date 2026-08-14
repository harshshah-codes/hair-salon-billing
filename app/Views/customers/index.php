<?php
/** @var array  $pagination */
/** @var string $search */
/** @var string $filter */
/** @var string $sort */
/** @var int    $branch */
/** @var array  $branches */

$buildQuery = static function (array $overrides): string {
    $params = array_merge($_GET, $overrides);
    unset($params['page']);
    return url('?' . http_build_query($params));
};

$filters = [
    'all'            => 'All Customers',
    'active-package' => 'Active Package',
    'outstanding'    => 'Outstanding',
    'inactive'       => 'Inactive',
    'no-visit'       => 'Not Visited (30d+)',
];

$sorts = [
    'created'    => 'Newest First',
    'name'       => 'Name (A–Z)',
    'last_visit' => 'Last Visit',
    'outstanding'=> 'Highest Outstanding',
];
?>
<div class="page-header">
    <div>
        <h1>Customers</h1>
        <p>Manage your customer base, packages and balances.</p>
    </div>
    <div class="page-actions">
        <a href="<?= e(url('/billing')) ?>" class="btn btn-outline-primary">
            <i class="fa-solid fa-file-invoice-dollar me-2"></i>New Bill
        </a>
        <a href="<?= e(url('/customers/create')) ?>" class="btn btn-primary">
            <i class="fa-solid fa-user-plus me-2"></i>Add Customer
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body py-3">
        <form id="customerFilterForm" method="get" action="<?= e(url('/customers')) ?>" class="d-flex flex-wrap align-items-center gap-3">
            <div class="search-tool">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="search" class="form-control" name="search" value="<?= e($search) ?>"
                       placeholder="Search by name or mobile…" data-live-search autocomplete="off">
            </div>

            <div class="filter-chip-group">
                <?php foreach ($filters as $key => $label): ?>
                    <button type="submit" name="filter" value="<?= e($key) ?>"
                            class="filter-chip <?= $filter === $key ? 'active' : '' ?>"><?= e($label) ?></button>
                <?php endforeach; ?>
            </div>

            <div class="d-flex align-items-center gap-2">
                <label class="form-label mb-0 text-nowrap" for="branch">Branch</label>
                <select class="form-select form-select-sm w-auto" id="branch" name="branch">
                    <option value="">All Branches</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= (int)$b['id'] ?>" <?= $branch === (int)$b['id'] ? 'selected' : '' ?>>
                            <?= e($b['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="d-flex align-items-center gap-2">
                <label class="form-label mb-0 text-nowrap" for="sort">Sort</label>
                <select class="form-select form-select-sm w-auto" id="sort" name="sort">
                    <?php foreach ($sorts as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $sort === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-sm btn-primary">Apply</button>
        </form>
    </div>
</div>

<div class="card">
    <?php if (empty($pagination['items'])): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-users"></i></div>
            <h5><?= $search ? 'No matching customers' : 'No customers yet' ?></h5>
            <p><?= $search ? 'Try a different search term or clear your filters.' : 'Add your first customer to start billing services and assigning packages.' ?></p>
            <a href="<?= e(url('/customers/create')) ?>" class="btn btn-primary mt-3">
                <i class="fa-solid fa-user-plus me-2"></i>Add Customer
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th>Customer</th>
                    <th>Mobile</th>
                    <th>Current Package</th>
                    <th>Package Balance</th>
                    <th>Outstanding</th>
                    <th>Last Visit</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($pagination['items'] as $customer): ?>
                    <tr>
                        <td>
                            <a href="<?= e(url('/customers/' . $customer['id'])) ?>" class="text-decoration-none d-flex align-items-center gap-2">
                                <div class="avatar bg-brand-soft text-brand">
                                    <?= e(strtoupper(substr($customer['name'], 0, 1))) ?>
                                </div>
                                <div>
                                    <strong class="d-block"><?= e($customer['name']) ?></strong>
                                    <?php if ($customer['email']): ?>
                                        <small class="text-muted"><?= e($customer['email']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </td>
                        <td><?= e($customer['mobile'] ?: '—') ?></td>
                        <td><?= e($customer['current_package_name'] ?: '—') ?></td>
                        <td>
                            <?php if ($customer['package_balance'] !== null): ?>
                                <span class="fw-semibold"><?= (int)$customer['package_balance'] ?> credits</span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((float)$customer['outstanding'] > 0): ?>
                                <span class="fw-semibold text-danger"><?= e(money($customer['outstanding'])) ?></span>
                            <?php else: ?>
                                <span class="text-muted"><?= e(money(0)) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted"><?= e(format_date($customer['last_visit_at'])) ?></td>
                        <td>
                            <span class="badge <?= $customer['status'] === 'active' ? 'status-active' : 'status-inactive' ?>">
                                <?= e(ucfirst($customer['status'])) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="<?= e(url('/customers/' . $customer['id'])) ?>" class="btn btn-icon" title="View">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="<?= e(url('/customers/' . $customer['id'] . '/edit')) ?>" class="btn btn-icon" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <?php if (can('customers.delete')): ?>
                                <form method="post" action="<?= e(url('/customers/' . $customer['id'] . '/delete')) ?>"
                                      class="d-inline confirm-form" data-confirm-title="Delete customer?"
                                      data-confirm-text="This will permanently remove <?= e(addslashes($customer['name'])) ?> and their history.">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-icon btn-ghost-danger" title="Delete">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-body pt-0">
            <?php partial('partials/pagination', ['paginator' => $pagination]) ?>
        </div>
    <?php endif; ?>
</div>

<?php $scripts = ob_start(); ?>
<script>
    $(function () {
        let timer;
        $('[data-live-search]').on('input', function () {
            clearTimeout(timer);
            timer = setTimeout(() => {
                const val = $(this).val();
                const form = $('#customerFilterForm');
                // keep filter + sort, reset to page 1 on search
                form.find('input[name="page"]').remove();
                if (!val) { form.find('[name="search"]').val(''); }
                form.submit();
            }, 400);
        });

        $('.confirm-form').each(function () {
            const $form = $(this);
            $form.find('button[type="submit"]').on('click', function (e) {
                e.preventDefault();
                confirmAction($form[0], {
                    title: $form.data('confirm-title') || 'Are you sure?',
                    text: $form.data('confirm-text') || 'This action cannot be undone.',
                    confirmText: 'Yes, delete',
                });
            });
        });
    });
</script>
<?php $scripts = ob_get_clean(); ?>
