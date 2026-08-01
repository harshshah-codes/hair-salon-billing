<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

final class SettingsRepository extends BaseRepository
{
    protected string $table = 'settings';

    public function all(): array
    {
        $rows = $this->db->query('SELECT `key`, `value` FROM settings')->fetchAll();
        $map = [];
        foreach ($rows as $row) {
            $map[$row['key']] = $row['value'];
        }
        return $map;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $stmt = $this->db->prepare('SELECT `value` FROM settings WHERE `key` = ? LIMIT 1');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value === false ? $default : $value;
    }

    public function set(string $key, mixed $value): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO settings (`key`, `value`, updated_at)
             VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = NOW()"
        );
        $stmt->execute([$key, (string)$value]);
    }

    public function setMany(array $values): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO settings (`key`, `value`, updated_at)
             VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = NOW()"
        );
        foreach ($values as $key => $value) {
            $stmt->execute([$key, (string)$value]);
        }
    }
}
