<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

final class BranchRepository extends BaseRepository
{
    protected string $table = 'branches';
    protected bool $softDeletes = false;

    public function active(): array
    {
        return $this->db->query(
            "SELECT * FROM branches WHERE status = 'active' ORDER BY name ASC"
        )->fetchAll();
    }

    public function listing(string $search = '', int $page = 1, int $perPage = 20): array
    {
        $where  = ['1 = 1'];
        $params = [];

        if (trim($search) !== '') {
            $where[] = '(name LIKE ? OR address LIKE ? OR phone LIKE ?)';
            $params[] = '%' . trim($search) . '%';
            $params[] = '%' . trim($search) . '%';
            $params[] = '%' . trim($search) . '%';
        }

        $whereSql = implode(' AND ', $where);
        $countSql = "SELECT COUNT(*) FROM branches WHERE {$whereSql}";
        $selectSql = "SELECT b.*,
                        (SELECT COUNT(*) FROM employees e WHERE e.branch_id = b.id AND e.deleted_at IS NULL) AS employee_count
                      FROM branches b
                      WHERE {$whereSql}
                      ORDER BY b.created_at DESC";

        return $this->paginateQuery($countSql, $selectSql, $params, $page, $perPage);
    }
}
