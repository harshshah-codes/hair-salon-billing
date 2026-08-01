<?php
/** Plain error page (no layout dependency). */
$status = $status ?? 404;
$message = $message ?? 'Page not found';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo e($status); ?> · Error</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{background:#f8fafc;font-family:'Inter',system-ui,sans-serif}</style>
</head>
<body class="d-flex align-items-center" style="min-height:100vh">
<div class="container text-center">
    <div class="display-1 fw-bold text-success" style="font-size:5rem"><?php echo e($status); ?></div>
    <h4 class="mb-2"><?php echo e($message); ?></h4>
    <p class="text-muted">Something went wrong while processing your request.</p>
    <a href="<?php echo url('/dashboard'); ?>" class="btn btn-primary mt-2"><i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard</a>
</div>
</body>
</html>
