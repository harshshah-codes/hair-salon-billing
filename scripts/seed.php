#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * CLI seeder.
 *
 * Creates the database (if missing), applies database/schema.sql,
 * seeds reference data from database/seed.sql, and creates/updates the
 * admin user with a hashed password.
 *
 * Usage:
 *   php scripts/seed.php
 *   php scripts/seed.php admin@example.com "s3cret-pass" "Owner" "9000000000"
 */

$config = require dirname(__DIR__) . '/config/database.php';

$charset = $config['charset'] ?? 'utf8mb4';
$dbName  = (string) $config['database'];

$connect = static function (bool $withDatabase): PDO {
    global $config;
    $dsn = sprintf(
        'mysql:host=%s;port=%d;charset=%s%s',
        $config['host'],
        (int) $config['port'],
        $config['charset'] ?? 'utf8mb4',
        $withDatabase ? ';dbname=' . $config['database'] : ''
    );
    return new PDO($dsn, $config['username'], $config['password'], $config['options']);
};

try {
    $pdo = $connect(false);
} catch (PDOException $e) {
    fwrite(STDERR, "DB connection failed: {$e->getMessage()}\n");
    exit(1);
}

echo "Creating database `{$dbName}` if needed...\n";
$pdo->exec(
    "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET {$charset} COLLATE {$charset}_unicode_ci"
);
$pdo->exec("USE `{$dbName}`");

foreach (['schema.sql', 'seed.sql'] as $file) {
    $path = dirname(__DIR__) . '/database/' . $file;
    $sql  = file_get_contents($path);
    if ($sql === false) {
        fwrite(STDERR, "Could not read {$path}\n");
        exit(1);
    }
    echo "Applying {$file}...\n";
    $pdo->exec($sql);
}

$name     = $argv[3] ?? 'Administrator';
$email    = $argv[1] ?? 'admin@salon.local';
$phone    = $argv[4] ?? '9000000000';
$password = $argv[2] ?? 'admin123';

if (strlen($password) < 6) {
    fwrite(STDERR, "Password must be at least 6 characters.\n");
    exit(1);
}

$role = $pdo->query("SELECT id FROM roles WHERE slug = 'admin' LIMIT 1")->fetch();
if (!$role) {
    fwrite(STDERR, "Admin role not found. Schema may have failed.\n");
    exit(1);
}

$stmt = $pdo->prepare(
    'INSERT INTO users (role_id, name, email, password, phone, status)
     VALUES (?, ?, ?, ?, ?, \'active\')
     ON DUPLICATE KEY UPDATE
         role_id = VALUES(role_id),
         name    = VALUES(name),
         password = VALUES(password),
         phone   = VALUES(phone)'
);
$stmt->execute([
    (int) $role['id'],
    $name,
    $email,
    password_hash($password, PASSWORD_DEFAULT),
    $phone,
]);

echo "Seeding complete.\n";
echo "  Sign in:  {$email}\n";
echo "  Password: {$password}\n";
echo "  (change it after first login — Settings or /profile)\n";
