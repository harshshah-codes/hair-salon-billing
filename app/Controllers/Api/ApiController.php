<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Validator;

/**
 * Base controller for the REST API. All responses are JSON.
 */
abstract class ApiController extends Controller
{
    /** 200 with data (data wrapped under 'data' unless already carrying keys). */
    protected function ok($data = null, string $message = ''): void
    {
        $payload = ['success' => true];
        if ($message !== '') {
            $payload['message'] = $message;
        }
        if ($data !== null) {
            $payload['data'] = $data;
        }
        $this->json($payload);
    }

    protected function error(string $message, int $status = 400): void
    {
        $this->json(['success' => false, 'message' => $message], $status);
    }

    protected function validationError(array $errors): void
    {
        $this->json(['success' => false, 'errors' => $errors], 422);
    }

    /** Validate request input; on failure responds 422 and stops. */
    protected function validateInput(array $rules): array
    {
        $data = $this->request->all();
        $errors = Validator::make($data, $rules);
        if ($errors !== []) {
            $this->validationError($errors);
        }
        return $data;
    }

    /** Shortcut: require permission, JSON 403 on failure. */
    protected function authorizeApi(string $permission): void
    {
        $user = $this->session->user();
        if (!$user || !\App\Core\Access::can($user['role_slug'] ?? '', $user['permissions'] ?? [], $permission)) {
            $this->error('You do not have permission to perform this action.', 403);
        }
    }
}
