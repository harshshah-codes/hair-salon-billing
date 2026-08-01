<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

final class UserRepository extends BaseRepository
{
    protected string $table = 'users';

    public function listing(string $search = ''): array
    {
        $where  = ['u.deleted_at IS NULL'];
        $params = [];
        if (trim($search) !== '') {
            $where[] = '(u.name LIKE ? OR u.email LIKE ?)';
            $params[] = '%' . trim($search) . '%';
            $params[] = '%' . trim($search) . '%';
        }

        $stmt = $this->db->prepare(
            "SELECT u.*, r.name AS role_name, r.slug AS role_slug
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE " . implode(' AND ', $where) . " ORDER BY u.created_at DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function withRole(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT u.*, r.name AS role_name, r.slug AS role_slug
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.id = ? AND u.deleted_at IS NULL LIMIT 1"
        );
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $set  = [];
        $args = [];
        foreach (['role_id', 'name', 'email', 'phone', 'status', 'password', 'last_login_at'] as $col) {
            if (array_key_exists($col, $data)) {
                $set[]  = "{$col} = ?";
                $args[] = $data[$col];
            }
        }
        if ($set === []) {
            return true;
        }
        $args[] = $id;
        $stmt = $this->db->prepare(
            'UPDATE users SET ' . implode(', ', $set) . ", updated_at = NOW() WHERE id = ?"
        );
        $stmt->execute($args);
        return $stmt->rowCount() > 0;
    }

    public function roles(): array
    {
        return $this->db->query('SELECT * FROM roles ORDER BY name ASC')->fetchAll();
    }

    public function createUser(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (role_id, name, email, phone, password, status) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            (int)$data['role_id'],
            $data['name'],
            $data['email'],
            $data['phone'] ?? null,
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['status'] ?? 'active',
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateUser(int $id, array $data): void
    {
        $set  = [];
        $args = [];
        foreach (['role_id', 'name', 'email', 'phone', 'status'] as $col) {
            if (array_key_exists($col, $data)) {
                $set[]  = "{$col} = ?";
                $args[] = $data[$col];
            }
        }
        if (isset($data['password']) && $data['password'] !== '') {
            $set[]  = 'password = ?';
            $args[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        if ($set !== []) {
            $set[] = 'updated_at = NOW()';
            $args[] = $id;
            $stmt = $this->db->prepare("UPDATE users SET " . implode(', ', $set) . " WHERE id = ?");
            $stmt->execute($args);
        }
    }

    public function deleteUser(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE users SET deleted_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function updateRolePermissions(int $roleId, array $permissions): void
    {
        $nested = [];
        foreach ($permissions as $section => $actions) {
            if (!is_array($actions)) {
                continue;
            }
            $actions = array_values(array_filter(array_map('strval', $actions)));
            if ($actions === []) {
                continue;
            }
            $nested[$section] = in_array('*', $actions, true) ? true : array_values(array_unique($actions));
        }

        $stmt = $this->db->prepare("UPDATE roles SET permissions = ? WHERE id = ?");
        $stmt->execute([json_encode($nested, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $roleId]);
    }
}
