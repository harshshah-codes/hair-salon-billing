<?php
/** @var int    $status */
/** @var string $message */
$status  = $status ?? 404;
$titles  = [403 => 'Access denied', 404 => 'Page not found', 405 => 'Not allowed', 419 => 'Session expired', 500 => 'Server error'];
$icons   = [403 => 'fa-lock', 404 => 'fa-compass', 405 => 'fa-ban', 419 => 'fa-hourglass-half', 500 => 'fa-bug'];
$message = $message ?? ($titles[$status] ?? 'Something went wrong');
?>
<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($status) ?> · <?= e(business_name()) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; background: #f4f6f9; min-height: 100vh; display: grid; place-items: center; }
        .error-card { text-align: center; padding: 3rem; max-width: 460px; }
        .error-code { font-size: 5rem; font-weight: 800; color: #059669; letter-spacing: -2px; }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-code"><?= (int)$status ?></div>
        <h1 class="h4 fw-bold mt-3"><?= e($titles[$status] ?? 'Oops!') ?></h1>
        <p class="text-muted"><?= e($message) ?></p>
        <a href="<?= e(url(auth_check() ? '/dashboard' : '/login')) ?>" class="btn btn-primary mt-2">
            <i class="fa-solid fa-arrow-left me-2"></i>Back to home
        </a>
    </div>
</body>
</html>
