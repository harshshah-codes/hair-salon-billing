<?php

declare(strict_types=1);

/**
 * One-time installer. Run once at: /install.php
 * Creates the database schema, seeds reference data and creates the admin account.
 * DELETE THIS FILE after installation.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once dirname(__DIR__) . '/config/bootstrap.php';

use App\Core\App;

$app = App::getInstance();
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = $app->db->pdo();

        $schema = file_get_contents(BASE_PATH . '/database/schema.sql');
        $seed = file_get_contents(BASE_PATH . '/database/seed.sql');

        if ($schema === false || $seed === false) {
            throw new RuntimeException('Could not read database files.');
        }

        $pdo->exec($schema);
        $pdo->exec($seed);

        $name = trim($_POST['name'] ?? 'Administrator');
        $email = trim($_POST['email'] ?? 'admin@salon.local');
        $password = $_POST['password'] ?? 'admin123';
        $phone = trim($_POST['phone'] ?? '9000000000');

        if (strlen((string) $password) < 6) {
            throw new RuntimeException('Password must be at least 6 characters.');
        }

        $role = $pdo->query("SELECT id FROM roles WHERE slug = 'superadmin' LIMIT 1")->fetch();
        if (!$role) {
            throw new RuntimeException('Superadmin role not found. Schema may have failed.');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO users (role_id, name, email, password, phone, status)
             VALUES (?, ?, ?, ?, ?, \'active\')
             ON DUPLICATE KEY UPDATE role_id = VALUES(role_id), name = VALUES(name),
                 password = VALUES(password), phone = VALUES(phone)'
        );
        $stmt->execute([(int) $role['id'], $name, $email, password_hash((string) $password, PASSWORD_DEFAULT), $phone]);

        $success = 'Installation complete. You can now sign in.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Install - <?php echo e(App::config('app.name')); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body{background:#0f172a;font-family:'Inter',system-ui,sans-serif;display:flex;align-items:center;min-height:100vh}
    .card{border:0;border-radius:16px;box-shadow:0 20px 50px rgba(0,0,0,.35);max-width:480px}
    .brand{color:#10b981;font-weight:800;font-size:1.4rem}
</style>
</head>
<body>
<div class="container">
    <div class="card mx-auto p-4">
        <div class="text-center mb-3"><div class="brand">Nirav Hair Storm</div>
        <div class="text-muted small">Salon &amp; Spa Billing System — Setup</div></div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small"><?php echo e($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success py-2 small">
                <?php echo e($success); ?><br>
                <a class="fw-semibold" href="<?php echo e(url('/auth/login')); ?>">Go to sign in →</a><br>
                <span class="text-danger">Please delete <code>public/install.php</code> now.</span>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label class="form-label small fw-semibold">Admin Name</label>
                <input class="form-control" name="name" value="Administrator" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Admin Email</label>
                <input class="form-control" type="email" name="email" value="admin@salon.local" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Password</label>
                <input class="form-control" type="text" name="password" value="admin123" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Phone</label>
                <input class="form-control" name="phone" value="9000000000">
            </div>
            <button class="btn btn-success w-100" type="submit">Install Application</button>
            <p class="text-muted mt-3 mb-0 small text-center">Creates tables and seeds demo services, packages &amp; employees.</p>
        </form>
    </div>
</div>
</body>
</html>
