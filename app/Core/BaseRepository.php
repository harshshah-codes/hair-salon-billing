<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Base repository: shared query helpers. Repositories are the single
 * place that owns SQL against a table; services and controllers consume
 * them instead of touching PDO directly.
 */
abstract class BaseRepository
{
    protected PDO $db;
    protected string $table;
    /** Whether the backing table has a soft-delete (deleted_at) column. */
    protected bool $softDeletes = true;

    public function __construct()
    {
        $this->db = App::getInstance()->db->pdo();
    }

    public function find(int $id): ?array
    {
        $where = $this->softDeletes ? ' AND deleted_at IS NULL' : '';
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?{$where} LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findWithTrashed(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function all(string $orderBy = 'created_at DESC'): array
    {
        $where = $this->softDeletes ? ' WHERE deleted_at IS NULL' : '';
        return $this->db->query(
            "SELECT * FROM {$this->table}{$where} ORDER BY {$orderBy}"
        )->fetchAll();
    }

    public function count(string $where = '', array $params = []): int
    {
        if ($where === '') {
            $where = $this->softDeletes ? 'deleted_at IS NULL' : '1';
        }
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE {$where}");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function create(array $data): int
    {
        $columns = array_keys($data);
        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $this->table,
            implode('`, `', $columns),
            implode(', ', array_fill(0, count($columns), '?'))
        );
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        if ($data === []) {
            return true;
        }
        $set = implode(', ', array_map(static fn ($col) => "`$col` = ?", array_keys($data)));
        $stmt = $this->db->prepare("UPDATE `{$this->table}` SET {$set} WHERE `id` = ?");
        $stmt->execute([...array_values($data), $id]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        if ($this->softDeletes) {
            $stmt = $this->db->prepare("UPDATE `{$this->table}` SET `deleted_at` = NOW() WHERE `id` = ?");
        } else {
            $stmt = $this->db->prepare("DELETE FROM `{$this->table}` WHERE `id` = ?");
        }
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Simple paginated result set.
     */
    public function paginate(
        string $select = '*',
        string $where = '',
        array $params = [],
        string $orderBy = 'created_at DESC',
        int $page = 1,
        int $perPage = 20
    ): array {
        if ($where === '') {
            $where = $this->softDeletes ? 'deleted_at IS NULL' : '1';
        }
        $page = max(1, $page);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE {$where}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare(
            "SELECT {$select} FROM {$this->table}
             WHERE {$where} ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $lastPage = (int)ceil($total / max(1, $perPage));

        return [
            'items'     => $rows,
            'rows'      => $rows,
            'total'     => $total,
            'page'      => $page,
            'perPage'   => $perPage,
            'per_page'  => $perPage,
            'lastPage'  => $lastPage,
            'last_page' => $lastPage,
        ];
    }

    /**
     * Paginate an arbitrary SQL query. Pass the full COUNT query and the
     * full SELECT query (already ordered), plus the bind parameters.
     */
    protected function paginateQuery(
        string $countSql,
        string $selectSql,
        array $params,
        int $page = 1,
        int $perPage = 20
    ): array {
        $page = max(1, $page);

        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare($selectSql . " LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $lastPage = (int)ceil($total / max(1, $perPage));

        return [
            'items'     => $rows,
            'rows'      => $rows,
            'total'     => $total,
            'page'      => $page,
            'perPage'   => $perPage,
            'per_page'  => $perPage,
            'lastPage'  => $lastPage,
            'last_page' => $lastPage,
        ];
    }
}
