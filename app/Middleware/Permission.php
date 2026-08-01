<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Access;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Require a specific role permission. Param: e.g. "customers.view"
 */
class Permission
{
    public function handle(Request $request, Response $response, ?string $param = null): void
    {
        $session = new Session();
        $user = $session->user();
        if (!$user || !Access::can($user['role_slug'] ?? '', $user['permissions'] ?? [], (string) $param)) {
            $response->abort(403, 'You do not have permission to perform this action.');
        }
    }
}
