<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;

class AuthService extends BaseService
{
    public function __construct(
        private UserRepository $users = new UserRepository(),
        private RoleRepository $roles = new RoleRepository()
    ) {}

    /**
     * Attempt a login. Returns true on success.
     */
    public function attempt(string $email, string $password): bool
    {
        $user = $this->users->findByEmail($email);
        if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password'])) {
            return false;
        }

        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            $this->users->update((int) $user['id'], ['password' => password_hash($password, PASSWORD_DEFAULT)]);
        }

        $role = $this->roles->find((int) $user['role_id']);
        $permissions = [];
        if ($role && !empty($role['permissions'])) {
            $permissions = json_decode((string) $role['permissions'], true) ?: [];
        }

        $user['role_slug'] = $role['slug'] ?? '';
        $user['permissions'] = $permissions;

        (new Session())->login($user);
        $this->users->update((int) $user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);

        $this->logActivity('auth.login', 'User signed in: ' . $user['email']);
        return true;
    }
}
