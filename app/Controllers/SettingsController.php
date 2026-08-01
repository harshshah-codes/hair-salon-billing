<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\SettingsRepository;
use App\Repositories\UserRepository;
use App\Services\ActivityService;
use App\Services\SettingsService;
use Throwable;

final class SettingsController extends Controller
{
    private SettingsService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new SettingsService(
            new SettingsRepository(),
            new UserRepository(),
            new ActivityService(new \App\Repositories\ActivityLogRepository())
        );
    }

    public function index(): void
    {
        $tab = (string)$this->request->query('tab', 'business');

        $this->view('settings/index', [
            'pageTitle'     => 'Settings',
            'active'        => 'settings',
            'breadcrumbs'   => ['Settings' => '/settings'],
            'tab'           => $tab,
            'settings'      => $this->service->all(),
            'users'         => $this->service->users(),
            'roles'         => $this->service->roles(),
            'permissions'   => SettingsService::PERMISSION_GROUPS,
        ]);
    }

    public function update(): void
    {
        $section = (string)$this->request->post('section', 'business');

        try {
            switch ($section) {                case 'business':
                    $this->validateRequest([
                        'business_name'    => 'required|max:120',
                        'business_phone'   => 'nullable|max:20',
                        'business_email'   => 'nullable|email|max:190',
                        'gst_rate'         => 'nullable|numeric|min:0|max:100',
                    ]);
                    $this->service->saveBusiness($this->request->all(), $this->request->file('business_logo'));
                    $this->flash('success', 'Business information updated.');
                    $this->redirect('/settings?tab=business');
                    break;

                case 'invoice':
                    $this->validateRequest([
                        'invoice_prefix' => 'required|max:20',
                    ]);
                    $this->service->saveInvoiceSettings($this->request->all());
                    $this->flash('success', 'Invoice settings updated.');
                    $this->redirect('/settings?tab=invoice');
                    break;

                case 'theme':
                    $this->service->saveTheme((string)$this->request->post('theme', 'light'));
                    $this->flash('success', 'Default theme saved.');
                    $this->redirect('/settings?tab=preferences');
                    break;

                case 'user_create':
                    $data = $this->validateRequest([
                        'name'      => 'required|max:120',
                        'email'     => 'required|email|max:190|unique:users,email',
                        'phone'     => 'nullable|phone|max:20',
                        'role_id'   => 'required|integer|exists:roles,id',
                        'password'  => 'required|min:8',
                    ]);
                    $this->service->createUser($data);
                    $this->flash('success', 'User created.');
                    $this->redirect('/settings?tab=users');
                    break;

                case 'user_update':
                    $id = (int)$this->request->post('id', 0);
                    $data = $this->validateRequest([
                        'name'      => 'required|max:120',
                        'email'     => 'required|email|max:190|unique:users,email,' . $id,
                        'phone'     => 'nullable|phone|max:20',
                        'role_id'   => 'required|integer|exists:roles,id',
                        'password'  => 'nullable|min:8',
                    ]);
                    unset($data['password']);
                    if ((string)$this->request->post('password', '') !== '') {
                        $data['password'] = (string)$this->request->post('password');
                    }
                    $this->service->updateUser($id, $data);
                    $this->flash('success', 'User updated.');
                    $this->redirect('/settings?tab=users');
                    break;

                case 'user_delete':
                    $id = (int)$this->request->post('id', 0);
                    $this->service->deleteUser($id);
                    $this->flash('success', 'User deleted.');
                    $this->redirect('/settings?tab=users');
                    break;

                case 'roles':
                    $roleId = (int)$this->request->post('role_id', 0);
                    $permissions = $this->request->post('permissions', []) ?: [];
                    $this->service->updateRolePermissions($roleId, is_array($permissions) ? $permissions : []);
                    $this->flash('success', 'Role permissions updated.');
                    $this->redirect('/settings?tab=roles');
                    break;

                case 'backup':
                    $sql = $this->service->backupDump();
                    header('Content-Type: application/sql');
                    header('Content-Disposition: attachment; filename="salon-backup-' . date('Ymd-His') . '.sql"');
                    echo $sql;
                    exit;

                default:
                    $this->response->abort(422, 'Unknown settings section.');
            }
        } catch (Throwable $e) {
            $this->flash('danger', $e->getMessage());
            $this->back();
        }
    }

    public function theme(): void
    {
        $this->service->saveTheme((string)$this->request->post('theme', 'light'));
        $this->json(['success' => true]);
    }

    public function backup(): void
    {
        $sql = $this->service->backupDump();
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="salon-backup-' . date('Ymd-His') . '.sql"');
        echo $sql;
        exit;
    }

    public function activity(): void
    {
        $limit = min(50, max(1, (int)$this->request->query('limit', 8)));
        $activities = (new \App\Repositories\ActivityLogRepository())->recent($limit);

        if ($this->request->query('ajax', '') !== '' || $this->request->isAjax()) {
            $this->json([
                'success' => true,
                'activities' => array_map(static fn ($a) => [
                    'description' => (string) ($a['description'] ?? ''),
                    'time_ago' => time_ago($a['created_at'] ?? null),
                ], $activities),
            ]);
        }

        $this->redirect('/settings');
    }
}
