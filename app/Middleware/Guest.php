<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Redirect already-authenticated users away from guest-only pages.
 */
class Guest
{
    public function handle(Request $request, Response $response, ?string $param = null): void
    {
        $session = new Session();
        if ($session->isAuthenticated()) {
            $response->redirect('/dashboard');
        }
    }
}
