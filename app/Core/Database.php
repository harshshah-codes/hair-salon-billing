<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;

/**
 * PDO wrapper with prepared-statement helpers.
 */
class Database
{
    private PDO $pdo;

    public function __construct(array $config)
    {
        $autoCreate = (bool) ($config['auto_create'] ?? true);

        try {
            $this->pdo = $this->connect($config, true);
        } catch (PDOException $e) {
            // Auto-create the database when the server is up but the
            // database does not exist yet (MySQL error 1049 / 1044).
            if (!$autoCreate || ($e->getCode() !== '1049' && $e->getCode() !== '1044')) {
                $this->fail($e);
            }

            try {
                $admin = $this->connect($config, false);
                $admin->exec(sprintf(
                    'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET %s COLLATE %s_unicode_ci',
                    $this->quoteIdentifier($config['database']),
                    $config['charset'],
                    $config['charset']
                ));
                $admin = null;
                $this->pdo = $this->connect($config, true);
            } catch (PDOException $e) {
                $this->fail($e);
            }
        }

        if ($autoCreate) {
            $this->installIfEmpty();
        }
    }

    /**
     * Fresh-database bootstrap: if the schema is empty, apply schema.sql +
     * seed.sql and create a usable admin account so the app just works.
     */
    private function installIfEmpty(): void
    {
        $tables = (int) $this->pdo->query(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()'
        )->fetchColumn();

        if ($tables > 0) {
            return;
        }

        foreach (['schema.sql', 'seed.sql'] as $file) {
            $path = BASE_PATH . '/database/' . $file;
            $sql  = file_get_contents($path);
            if ($sql === false) {
                throw new \RuntimeException("Could not read database file: {$path}");
            }
            $this->pdo->exec($sql);
        }

        $role = $this->pdo->query("SELECT id FROM roles WHERE slug = 'admin' LIMIT 1")->fetch();
        if ($role) {
            $email    = getenv('DB_ADMIN_EMAIL') ?: 'admin@salon.local';
            $password = getenv('DB_ADMIN_PASSWORD') ?: 'admin123';
            $stmt = $this->pdo->prepare(
                "INSERT INTO users (role_id, name, email, password, phone, status)
                 VALUES (?, 'Administrator', ?, ?, '9000000000', 'active')
                 ON DUPLICATE KEY UPDATE role_id = VALUES(role_id), password = VALUES(password)"
            );
            $stmt->execute([(int) $role['id'], $email, password_hash($password, PASSWORD_DEFAULT)]);
        }
    }

    /**
     * Build a PDO connection, optionally selecting the target database.
     */
    private function connect(array $config, bool $withDatabase): PDO
    {
        $dsn = sprintf(
            '%s:host=%s;port=%s;charset=%s%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['charset'],
            $withDatabase ? ';dbname=' . $config['database'] : ''
        );

        return new PDO($dsn, $config['username'], $config['password'], $config['options']);
    }

    /**
     * Sanitize an identifier for use inside backticks.
     */
    private function quoteIdentifier(string $identifier): string
    {
        return str_replace('`', '``', $identifier);
    }

    private function fail(PDOException $e): never
    {
        if (App::config('app.debug', false)) {
            throw $e;
        }
        http_response_code(500);
        echo '<h1>Database connection failed</h1><p>Please check config/database.php and ensure MySQL is running.</p>';
        exit(1);
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetchColumn(string $sql, array $params = []): ?string
    {
        $value = $this->query($sql, $params)->fetchColumn();
        return $value === false ? null : (string) $value;
    }

    public function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn ($c) => ':' . $c, $columns);

        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $table,
            implode('`, `', $columns),
            implode(', ', $placeholders)
        );

        $this->query($sql, $this->bound($data));
        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $sets = [];
        $params = [];
        foreach ($data as $column => $value) {
            $key = ':' . $column . '_u';
            $sets[] = "`$column` = $key";
            $params[$key] = $value;
        }
        foreach ($whereParams as $k => $v) {
            $params[$k] = $v;
        }

        $sql = sprintf('UPDATE `%s` SET %s WHERE %s', $table, implode(', ', $sets), $where);
        return $this->query($sql, $params)->rowCount();
    }

    public function delete(string $table, string $where, array $params = []): int
    {
        return $this->query(sprintf('DELETE FROM `%s` WHERE %s', $table, $where), $params)->rowCount();
    }

    public function count(string $sql, array $params = []): int
    {
        return (int) $this->query($sql, $params)->fetchColumn();
    }

    public function transaction(callable $callback)
    {
        $this->pdo->beginTransaction();
        try {
            $result = $callback();
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Convert associative array to named bound params (:key => value).
     */
    private function bound(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            $out[':' . $key] = $value;
        }
        return $out;
    }
}
