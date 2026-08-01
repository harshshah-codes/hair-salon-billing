<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\ActivityLogRepository;
use App\Repositories\SettingsRepository;
use App\Repositories\UserRepository;
use App\Services\ActivityService;
use App\Services\SettingsService;

final class SettingsController extends ApiController
{
    private function service(): SettingsService
    {
        return new SettingsService(
            new SettingsRepository(),
            new UserRepository(),
            new ActivityService(new ActivityLogRepository())
        );
    }

    /** GET /api/settings — all settings key/value map */
    public function index(): void
    {
        $this->authorizeApi('settings.view');
        $this->ok([
            'settings' => $this->service()->all(),
            'users' => $this->service()->users(),
            'roles' => $this->service()->roles(),
            'permission_groups' => SettingsService::PERMISSION_GROUPS,
        ]);
    }

    /** POST /api/settings  { section: business|invoice|theme, ... } */
    public function update(): void
    {
        $this->authorizeApi('settings.edit');
        $section = (string) $this->request->input('section', 'business');
        $data = $this->request->all();

        try {
            switch ($section) {
                case 'business':
                    $this->validateInput([
                        'business_name' => 'required|max:120',
                        'business_phone' => 'nullable|max:20',
                        'business_email' => 'nullable|email|max:190',
                        'gst_rate' => 'nullable|numeric|min:0|max:100',
                    ]);
                    $this->service()->saveBusiness($data, $this->request->file('business_logo'));
                    break;

                case 'invoice':
                    $this->validateInput(['invoice_prefix' => 'required|max:20']);
                    $this->service()->saveInvoiceSettings($data);
                    break;

                case 'theme':
                    $this->service()->saveTheme((string) $this->request->input('theme', 'light'));
                    break;

                default:
                    $this->error('Unknown settings section.', 422);
            }
            $this->ok(null, 'Settings updated successfully.');
        } catch (\Throwable $e) {
            $this->error($e->getMessage(), 422);
        }
    }

    /** POST /api/settings/users — create a user */
    public function createUser(): void
    {
        $this->authorizeApi('settings.edit');
        $data = $this->validateInput([
            'name' => 'required|max:120',
            'email' => 'required|email|max:190|unique:users,email',
            'phone' => 'nullable|max:20',
            'role_id' => 'required|integer|exists:roles,id',
            'password' => 'required|min:8',
        ]);
        $id = $this->service()->createUser($data);
        $this->ok(['id' => $id], 'User created successfully.');
    }

    /** PUT/POST /api/settings/users/{id} */
    public function updateUser(int $id): void
    {
        $this->authorizeApi('settings.edit');
        $data = $this->validateInput([
            'name' => 'nullable|max:120',
            'email' => 'nullable|email|max:190|unique:users,email,' . $id,
            'phone' => 'nullable|max:20',
            'role_id' => 'nullable|integer|exists:roles,id',
            'password' => 'nullable|min:8',
        ]);
        $this->service()->updateUser($id, $data);
        $this->ok(null, 'User updated successfully.');
    }

    /** DELETE /api/settings/users/{id} */
    public function deleteUser(int $id): void
    {
        $this->authorizeApi('settings.edit');
        $this->service()->deleteUser($id);
        $this->ok(null, 'User deleted.');
    }

    /** POST /api/settings/roles  { role_id, permissions: [...] } */
    public function updateRolePermissions(): void
    {
        $this->authorizeApi('settings.edit');
        $data = $this->validateInput([
            'role_id' => 'required|integer|exists:roles,id',
        ]);
        $permissions = $this->request->input('permissions', []);
        $this->service()->updateRolePermissions((int) $data['role_id'], (array) $permissions);
        $this->ok(null, 'Role permissions updated.');
    }
}
