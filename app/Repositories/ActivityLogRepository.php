<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

final class ActivityLogRepository extends BaseRepository
{
    protected string $table = 'activity_logs';

    public function log(string $type, ?string $entityType = null, ?int $entityId = null, array $data = []): void
    {
        $description = (string) ($data['description'] ?? '');
        if ($description === '') {
            $description = $type . ($entityId !== null ? ' #' . $entityId : '');
        }
        $stmt = $this->db->prepare(
            'INSERT INTO activity_logs (user_id, type, description, data, ip, user_agent)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            (new \App\Core\Session())->userId(),
            $type,
            substr($description, 0, 255),
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $_SERVER['REMOTE_ADDR'] ?? null,
            substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);
    }

    public function recent(int $limit = 20): array
    {
        $stmt = $this->db->query(
            "SELECT a.*, u.name AS user_name
             FROM activity_logs a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.id DESC LIMIT {$limit}"
        );
        return $stmt->fetchAll();
    }
}
