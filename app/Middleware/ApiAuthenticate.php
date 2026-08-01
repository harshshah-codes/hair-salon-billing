<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\ApiTokenRepository;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;

/**
 * Require a valid API bearer token (Authorization: Bearer <token>).
 * Resolves the token to a user and hydrates role + permissions into
 * the session so auth_id()/can()/Access work inside API controllers.
 */
class ApiAuthenticate
{
    public function handle(Request $request, Response $response, ?string $param = null): void
    {
        $auth = $request->header('Authorization') ?? '';
        $token = '';
        if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
            $token = trim($m[1]);
        }

        if ($token === '') {
            $response->json(['success' => false, 'message' => 'Authentication token required.'], 401);
        }

        $repo = new ApiTokenRepository();
        $record = $repo->findByToken($token);
        if (!$record) {
            $response->json(['success' => false, 'message' => 'Invalid or expired token.'], 401);
        }

        if (!empty($record['expires_at']) && strtotime($record['expires_at']) < time()) {
            $repo->revoke($token);
            $response->json(['success' => false, 'message' => 'Token expired.'], 401);
        }

        $user = (new UserRepository())->findWithTrashed((int) $record['user_id']);
        if (!$user || $user['status'] !== 'active') {
            $response->json(['success' => false, 'message' => 'Account is inactive or not found.'], 401);
        }

        $role = (new RoleRepository())->find((int) $user['role_id']);
        $permissions = [];
        if ($role && !empty($role['permissions'])) {
            $permissions = json_decode((string) $role['permissions'], true) ?: [];
        }
        $user['role_slug'] = $role['slug'] ?? '';
        $user['permissions'] = $permissions;

        $repo->touch($token);

        (new Session())->login($user);
    }
}
