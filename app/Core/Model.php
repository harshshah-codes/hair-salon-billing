<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Base model: generic CRUD with soft-delete support.
 * Concrete models declare $table, $fillable and $softDeletes.
 */
abstract class Model
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected bool $softDeletes = true;
    protected PDO $db;

    public function __construct()
    {
        $this->db = App::getInstance()->db->pdo();
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function query(): PDO
    {
        return $this->db;
    }

    public function find(int $id): ?array
    {
        return $this->findWhere([$this->primaryKey => $id]);
    }

    public function findWhere(array $conditions): ?array
    {
        $sql = "SELECT * FROM {$this->table}";
        $where = [];
        $args = [];
        if ($this->softDeletes) {
            $where[] = 'deleted_at IS NULL';
        }
        foreach ($conditions as $column => $value) {
            $where[] = "{$column} = ?";
            $args[]  = $value;
        }
        $sql .= ' WHERE ' . implode(' AND ', $where) . ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($args);
        return $stmt->fetch() ?: null;
    }

    public function all(string $orderBy = 'created_at DESC'): array
    {
        $sql = "SELECT * FROM {$this->table}";
        if ($this->softDeletes) {
            $sql .= ' WHERE deleted_at IS NULL';
        }
        $sql .= " ORDER BY {$orderBy}";
        return $this->db->query($sql)->fetchAll();
    }

    public function create(array $data): int
    {
        $data = $this->fillable($data);
        $columns = array_keys($data);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', array_fill(0, count($columns), '?'))
        );
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $data = $this->fillable($data);
        if ($data === []) {
            return true;
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        $set = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        $sql = "UPDATE {$this->table} SET {$set} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([...array_values($data), $id]);
        return $stmt->rowCount() >= 0;
    }

    public function delete(int $id): bool
    {
        if (!$this->softDeletes) {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        }

        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET deleted_at = NOW() WHERE {$this->primaryKey} = ?"
        );
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function restore(int $id): bool
    {
        if (!$this->softDeletes) {
            return false;
        }
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET deleted_at = NULL WHERE {$this->primaryKey} = ?"
        );
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        if ($this->softDeletes) {
            $sql .= ' WHERE deleted_at IS NULL';
        }
        return (int)$this->db->query($sql)->fetchColumn();
    }

    protected function fillable(array $data): array
    {
        if ($this->fillable === []) {
            return $data;
        }
        $result = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $this->fillable, true)) {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}
