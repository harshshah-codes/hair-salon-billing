<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

final class ApiTokenRepository extends BaseRepository
{
    protected string $table = 'api_tokens';
    protected bool $softDeletes = false;

    public function findByToken(string $token): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM api_tokens WHERE token = ? LIMIT 1'
        );
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    public function revoke(string $token): bool
    {
        $stmt = $this->db->prepare('DELETE FROM api_tokens WHERE token = ?');
        $stmt->execute([$token]);
        return $stmt->rowCount() > 0;
    }

    public function revokeAllForUser(int $userId): void
    {
        $stmt = $this->db->prepare('DELETE FROM api_tokens WHERE user_id = ?');
        $stmt->execute([$userId]);
    }

    public function touch(string $token): void
    {
        $stmt = $this->db->prepare('UPDATE api_tokens SET last_used_at = NOW() WHERE token = ?');
        $stmt->execute([$token]);
    }

    public function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
