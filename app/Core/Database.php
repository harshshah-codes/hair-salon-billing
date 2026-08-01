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
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        } catch (PDOException $e) {
            if (App::config('app.debug', false)) {
                throw $e;
            }
            http_response_code(500);
            echo '<h1>Database connection failed</h1><p>Please check config/database.php and ensure MySQL is running.</p>';
            exit(1);
        }
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
