<?php
$toastSuccess = (new App\Core\Session())->getFlash('success');
$toastError = (new App\Core\Session())->getFlash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($title ?? 'Sign In'); ?> · <?php echo e(App\Core\App::config('app.name')); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="<?php echo url('css/app.css'); ?>" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="auth-wrapper">
        <div class="auth-glow auth-glow-1"></div>
        <div class="auth-glow auth-glow-2"></div>
        <div class="auth-card">
            <div class="auth-head text-center mb-4">
                <div class="brand-lockup mb-3">
                    <span class="brand-mark brand-mark-lg"><i class="fa-solid fa-scissors"></i></span>
                </div>
                <h1 class="auth-title"><?php echo e(App\Core\App::config('app.name')); ?></h1>
                <p class="auth-tagline mb-0">Salon &amp; Spa Billing Management</p>
            </div>

            <?php if ($toastSuccess): ?>
                <div class="alert alert-success py-2 small"><?php echo e($toastSuccess); ?></div>
            <?php endif; ?>
            <?php if ($toastError): ?>
                <div class="alert alert-danger py-2 small"><?php echo e($toastError); ?></div>
            <?php endif; ?>

            <?php echo $content; ?>

            <p class="text-center text-muted small mt-4 mb-0">© <?php echo date('Y'); ?> <?php echo e(App\Core\App::config('app.name')); ?></p>
        </div>
    </div>
</body>
</html>
