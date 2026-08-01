<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

final class PackageRepository extends BaseRepository
{
    protected string $table = 'packages';

    public function listing(string $search = '', string $status = 'all', int $page = 1, int $perPage = 20): array
    {
        $where  = ['p.deleted_at IS NULL'];
        $params = [];

        if ($status === 'active' || $status === 'inactive') {
            $where[] = 'p.status = ?';
            $params[] = $status;
        }

        if (trim($search) !== '') {
            $where[] = 'p.name LIKE ?';
            $params[] = '%' . trim($search) . '%';
        }

        $whereSql = implode(' AND ', $where);
        $countSql = "SELECT COUNT(*) FROM packages p WHERE {$whereSql}";
        $selectSql = "SELECT p.*,
                        (SELECT COUNT(*) FROM customer_packages cp
                          WHERE cp.package_id = p.id AND cp.status = 'active') AS customers_using
                      FROM packages p WHERE {$whereSql} ORDER BY p.created_at DESC";

        return $this->paginateQuery($countSql, $selectSql, $params, $page, $perPage);
    }

    public function active(): array
    {
        return $this->db->query(
            "SELECT * FROM packages WHERE status = 'active' AND deleted_at IS NULL ORDER BY name ASC"
        )->fetchAll();
    }
}
