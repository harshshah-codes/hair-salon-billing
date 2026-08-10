<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Session;
use App\Repositories\BranchRepository;
use App\Repositories\SettingsRepository;
use App\Repositories\UserRepository;
use RuntimeException;

final class SettingsService
{
    public const PERMISSION_GROUPS = [
        'dashboard' => ['title' => 'Dashboard', 'actions' => ['view']],
        'customers' => ['title' => 'Customers', 'actions' => ['view', 'create', 'edit', 'delete']],
        'billing'   => ['title' => 'Billing', 'actions' => ['view', 'create']],
        'packages'  => ['title' => 'Packages', 'actions' => ['view', 'create', 'edit', 'delete']],
        'services'  => ['title' => 'Services', 'actions' => ['view', 'create', 'edit', 'delete']],
        'employees' => ['title' => 'Employees', 'actions' => ['view', 'create', 'edit', 'delete']],
        'reports'   => ['title' => 'Reports', 'actions' => ['view']],
        'settings'  => ['title' => 'Settings', 'actions' => ['view', 'edit']],
    ];

    public function __construct(
        private SettingsRepository $settings,
        private UserRepository $users,
        private ActivityService $activity,
        private ?BranchRepository $branches = null
    ) {
        $this->branches = $this->branches ?? new BranchRepository();
    }

    public function all(): array
    {
        return $this->settings->all();
    }

    public function saveBusiness(array $data, ?array $logoFile = null): void
    {
        $values = [
            'business_name'    => $data['business_name'] ?? null,
            'business_address' => $data['business_address'] ?? null,
            'business_phone'   => $data['business_phone'] ?? null,
            'business_email'   => $data['business_email'] ?? null,
            'business_gst'     => $data['business_gst'] ?? null,
            'gst_rate'         => (string)(float)($data['gst_rate'] ?? 0),
            'currency'         => $data['currency'] ?? 'INR',
        ];

        if ($logoFile && $logoFile['error'] === UPLOAD_ERR_OK) {
            $path = $this->storeLogo($logoFile);
            if ($path !== null) {
                $values['business_logo'] = $path;
            }
        }

        $this->settings->setMany($values);
        $this->activity->log('settings.business_updated', 'settings');
    }

    public function saveInvoiceSettings(array $data): void
    {
        $this->settings->setMany([
            'invoice_prefix' => $data['invoice_prefix'] ?? 'INV-',
            'invoice_footer' => $data['invoice_footer'] ?? null,
            'invoice_terms'  => $data['invoice_terms'] ?? null,
        ]);
        $this->activity->log('settings.invoice_updated', 'settings');
    }

    public function saveTheme(string $theme): void
    {
        $this->settings->set('theme_mode', $theme === 'dark' ? 'dark' : 'light');
        $this->activity->log('settings.theme_updated', 'settings');
    }

    private function storeLogo(array $file): ?string
    {
        $allowed = ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'];
        if (!in_array($file['type'] ?? '', $allowed, true)) {
            throw new RuntimeException('Logo must be a PNG, JPG, WEBP or SVG image.');
        }

        $ext = match ($file['type']) {
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            default      => 'jpg',
        };

        $name = 'logo-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = UPLOAD_PATH . '/logos/' . $name;

        if (!is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return null;
        }

        return 'uploads/logos/' . $name;
    }

    /* ---------------------------------------------------------
     * Users & roles
     * ------------------------------------------------------- */

    public function users(): array
    {
        return $this->users->listing();
    }

    public function roles(): array
    {
        return $this->users->roles();
    }

    public function createUser(array $data): int
    {
        $id = $this->users->createUser($data);
        $this->activity->log('user.created', 'user', $id, ['name' => $data['name']]);
        return $id;
    }

    public function updateUser(int $id, array $data): void
    {
        $this->users->updateUser($id, $data);
        $this->activity->log('user.updated', 'user', $id);
    }

    public function deleteUser(int $id): void
    {
        if ($id === auth_id()) {
            throw new RuntimeException('You cannot delete your own account.');
        }
        $this->users->deleteUser($id);
        $this->activity->log('user.deleted', 'user', $id);
    }

    public function updateRolePermissions(int $roleId, array $permissions): void
    {
        $this->users->updateRolePermissions($roleId, $permissions);
        $this->activity->log('role.permissions_updated', 'role', $roleId);
    }

    /* ---------------------------------------------------------
     * Branches
     * ------------------------------------------------------- */

    public function branches(): array
    {
        return $this->branches->all('name ASC');
    }

    public function createBranch(array $data): int
    {
        $id = $this->branches->create([
            'name'    => trim((string) $data['name']),
            'address' => trim((string) ($data['address'] ?? '')) ?: null,
            'phone'   => trim((string) ($data['phone'] ?? '')) ?: null,
            'status'  => (($data['status'] ?? 'active') === 'inactive') ? 'inactive' : 'active',
        ]);
        $this->activity->log('branch.created', 'branch', $id, ['name' => $data['name']]);
        return $id;
    }

    public function updateBranch(int $id, array $data): void
    {
        $branch = $this->branches->find($id);
        if (!$branch) {
            throw new RuntimeException('Branch not found.');
        }
        $this->branches->update($id, [
            'name'    => trim((string) $data['name']),
            'address' => trim((string) ($data['address'] ?? '')) ?: null,
            'phone'   => trim((string) ($data['phone'] ?? '')) ?: null,
            'status'  => (($data['status'] ?? 'active') === 'inactive') ? 'inactive' : 'active',
        ]);
        $this->activity->log('branch.updated', 'branch', $id, ['name' => $data['name']]);
    }

    public function deleteBranch(int $id): void
    {
        $branch = $this->branches->find($id);
        if (!$branch) {
            throw new RuntimeException('Branch not found.');
        }
        if ($id === (int) Session::branchId()) {
            throw new RuntimeException('You cannot delete the branch you are signed into.');
        }
        $this->branches->delete($id);
        $this->activity->log('branch.deleted', 'branch', $id, ['name' => $branch['name']]);
    }

    /* ---------------------------------------------------------
     * Backup
     * ------------------------------------------------------- */

    public function backupDump(): string
    {
        $pdo = App::getInstance()->db->pdo();
        $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

        $sql = "-- ============================================================\n";
        $sql .= "-- Nirav Salon & Spa database backup\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- ============================================================\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($tables as $table) {
            $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_NUM);
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $create[1] . ";\n\n";

            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_NUM);
            foreach ($rows as $row) {
                $values = array_map(
                    static fn($value) => $value === null ? 'NULL' : $pdo->quote((string)$value),
                    $row
                );
                $sql .= "INSERT INTO `{$table}` VALUES (" . implode(', ', $values) . ");\n";
            }
            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        return $sql;
    }
}
