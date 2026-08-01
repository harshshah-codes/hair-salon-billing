<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

class RoleRepository extends BaseRepository
{
    protected string $table = 'roles';

    public function allActive(): array
    {
        $stmt = $this->db->prepare('SELECT * FROM roles WHERE deleted_at IS NULL ORDER BY id');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function usersCount(int $roleId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM users WHERE role_id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$roleId]);
        return (int) $stmt->fetchColumn();
    }
}
