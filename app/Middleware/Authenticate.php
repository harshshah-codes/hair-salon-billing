<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Require an authenticated session.
 */
class Authenticate
{
    public function handle(Request $request, Response $response, ?string $param = null): void
    {
        $session = new Session();
        if (!$session->isAuthenticated()) {
            $session->flash('error', 'Please sign in to continue.');
            $response->redirect('/auth/login');
        }
    }
}
