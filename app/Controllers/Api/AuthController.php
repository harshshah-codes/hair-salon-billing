<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\ApiTokenRepository;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;

final class AuthController extends ApiController
{
    /**
     * POST /api/auth/login  { email, password }
     */
    public function login(): void
    {
        $email = trim((string) $this->request->input('email'));
        $password = (string) $this->request->input('password');

        if ($email === '' || $password === '') {
            $this->validationError([
                'email' => 'Email is required.',
                'password' => 'Password is required.',
            ]);
        }

        $users = new UserRepository();
        $user = $users->findByEmail($email);
        if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password'])) {
            $this->error('Invalid credentials or inactive account.', 401);
        }

        $role = (new RoleRepository())->find((int) $user['role_id']);
        $permissions = $role && !empty($role['permissions']) ? (json_decode((string) $role['permissions'], true) ?: []) : [];

        $tokens = new ApiTokenRepository();
        $token = $tokens->generateToken();
        $tokens->create([
            'user_id' => (int) $user['id'],
            'token' => $token,
            'name' => 'api-login',
        ]);

        $users->update((int) $user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);

        $this->logActivity('api.login', 'API sign in: ' . $user['email']);

        $this->json([
            'success' => true,
            'message' => 'Signed in successfully.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'role' => $role['slug'] ?? '',
                'role_name' => $role['name'] ?? '',
                'permissions' => $permissions,
            ],
        ]);
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(): void
    {
        $auth = $this->request->header('Authorization') ?? '';
        $token = preg_match('/^Bearer\s+(.+)$/i', $auth, $m) ? trim($m[1]) : '';

        if ($token !== '') {
            (new ApiTokenRepository())->revoke($token);
            $this->logActivity('api.logout', 'API sign out');
        }

        $this->json(['success' => true, 'message' => 'Signed out.'], 200);
    }

    /**
     * GET /api/auth/me
     */
    public function me(): void
    {
        $user = $this->session->user();
        if (!$user) {
            $this->error('Unauthenticated.', 401);
        }

        $this->json([
            'success' => true,
            'data' => [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'avatar' => $user['avatar'],
                'role' => $user['role_slug'] ?? '',
                'permissions' => $user['permissions'] ?? [],
            ],
        ]);
    }
}
