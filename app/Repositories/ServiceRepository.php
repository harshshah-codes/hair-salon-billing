<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

final class ServiceRepository extends BaseRepository
{
    protected string $table = 'services';

    public function listing(string $search = '', string $status = 'all', string $category = '', int $page = 1, int $perPage = 20): array
    {
        $where  = ['s.deleted_at IS NULL'];
        $params = [];

        if ($status === 'active' || $status === 'inactive') {
            $where[] = 's.status = ?';
            $params[] = $status;
        }

        if (trim($category) !== '') {
            $where[] = 's.category = ?';
            $params[] = trim($category);
        }

        if (trim($search) !== '') {
            $where[] = '(s.name LIKE ? OR s.category LIKE ?)';
            $params[] = '%' . trim($search) . '%';
            $params[] = '%' . trim($search) . '%';
        }

        $whereSql = implode(' AND ', $where);
        $countSql = "SELECT COUNT(*) FROM services s WHERE {$whereSql}";
        $selectSql = "SELECT s.* FROM services s WHERE {$whereSql} ORDER BY s.name ASC";

        return $this->paginateQuery($countSql, $selectSql, $params, $page, $perPage);
    }

    /** Active services for the billing POS. */
    public function active(): array
    {
        return $this->db->query(
            "SELECT * FROM services WHERE status='active' AND deleted_at IS NULL ORDER BY name ASC"
        )->fetchAll();
    }

    /** Searchable list for the billing autocomplete. */
    public function search(string $q, int $limit = 15): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, name, category, price, duration_minutes
             FROM services
             WHERE status = 'active' AND deleted_at IS NULL AND (name LIKE ? OR category LIKE ?)
             ORDER BY name ASC LIMIT {$limit}"
        );
        $like = '%' . $q . '%';
        $stmt->execute([$like, $like]);
        return $stmt->fetchAll();
    }

    public function categories(): array
    {
        return $this->db->query(
            "SELECT DISTINCT category FROM services
             WHERE deleted_at IS NULL AND category IS NOT NULL AND category != ''
             ORDER BY category ASC"
        )->fetchAll();
    }
}
