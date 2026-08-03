<?php
/** @var string $title */
/** @var string $active */
/** @var array $scripts */
/** @var array $styles */
/** @var string $content */
$title = $title ?? $pageTitle ?? App\Core\App::config('app.name', 'Nirav Hair Storm');
$user = auth_user();
$user = $user ?: ['name' => 'User', 'email' => '', 'role_slug' => '', 'avatar' => ''];
$theme = setting('theme_mode', 'light');
$businessName = setting('business_name', 'Nirav Hair Storm');
$flash = (new App\Core\Session());
$toastSuccess = $flash->getFlash('success');
$toastError = $flash->getFlash('error');
$hasErrors = $flash->hasErrors();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo e($theme === 'dark' ? 'dark' : 'light'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    <title><?php echo e($title); ?> · <?php echo e($businessName); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <link href="<?php echo url('css/app.css'); ?>" rel="stylesheet">
    <?php foreach ((array) $styles as $s): ?>
        <link href="<?php echo url($s); ?>" rel="stylesheet">
    <?php endforeach; ?>
</head>
<body class="<?php echo e($bodyClass ?? ''); ?>">
<div class="app-shell">

    <?php include APP_PATH . '/Views/partials/sidebar.php'; ?>

    <div class="app-main" id="appMain">
        <?php include APP_PATH . '/Views/partials/topbar.php'; ?>

        <main class="app-content">
            <div class="container-fluid px-3 px-lg-4 py-3 py-lg-4">
                <?php if ($hasErrors): ?>
                    <?php $errors = $flash->errorsAll(); ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Please fix the following:</strong>
                        <ul class="mb-0 mt-1 small">
                            <?php foreach ($errors as $field => $message): ?>
                                <li><?php echo e($message); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php echo $content; ?>
            </div>
        </main>

        <footer class="app-footer d-none d-md-flex">
            <span>© <?php echo date('Y'); ?> <?php echo e($businessName); ?> · v<?php echo e(App\Core\App::config('app.version')); ?></span>
            <span class="ms-auto text-muted">Powered by Nirav Hair Storm</span>
        </footer>
    </div>
</div>

<?php include APP_PATH . '/Views/partials/flash.php'; ?>
<?php include APP_PATH . '/Views/partials/confirm_modal.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?php echo url('js/app.js'); ?>"></script>
<?php foreach ((array) $scripts as $s): ?>
    <script src="<?php echo url($s); ?>"></script>
<?php endforeach; ?>

<?php App\Core\Session::sweep(); ?>
</body>
</html>
