<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base controller with shared dependencies and helpers.
 */
abstract class Controller
{
    protected Request $request;
    protected Response $response;
    protected Session $session;
    protected Database $db;
    protected View $view;

    public function __construct()
    {
        $app = App::getInstance();
        $this->request = $app->request;
        $this->response = $app->response;
        $this->session = $app->session;
        $this->db = $app->db;
        $this->view = new View();
    }

    protected function render(string $template, array $data = [], string $layout = 'main'): void
    {
        $this->view->render($template, $data, $layout);
    }

    /** Alias for render(); matches the call sites in existing controllers. */
    protected function view(string $template, array $data = [], string $layout = 'main'): void
    {
        $this->render($template, $data, $layout);
    }

    /** Flash a success/error message to the session (danger is normalized to error). */
    protected function flash(string $type, string $message): void
    {
        $this->session->flash($type === 'danger' ? 'error' : $type, $message);
    }

    protected function json($data, int $status = 200): void
    {
        $this->response->json($data, $status);
    }

    protected function redirect(string $path): void
    {
        $this->response->redirect($path);
    }

    protected function back(): void
    {
        $this->response->back();
    }

    protected function validate(array $data, array $rules): array
    {
        return Validator::make($data, $rules);
    }

    /**
     * Validate the current request against rules, bail to the previous
     * page on failure (preserving input), and return the sanitized data.
     */
    protected function validateRequest(array $rules): array
    {
        $errors = $this->validate($this->request->all(), $rules);
        if (!empty($errors)) {
            $this->session->setErrors($errors);
            $this->session->setOld($this->request->all());
            $this->session->flash('error', 'Please fix the highlighted fields and try again.');
            $this->back();
        }
        return array_intersect_key($this->request->all(), array_flip(array_keys($rules)));
    }

    /**
     * Validate and bail to the previous page on failure, preserving input.
     */
    protected function validateOrFail(array $data, array $rules): void
    {
        $errors = $this->validate($data, $rules);
        if (!empty($errors)) {
            $this->session->setErrors($errors);
            $this->session->setOld($data);
            $this->session->flash('error', 'Please fix the highlighted fields and try again.');
            $this->back();
        }
    }

    protected function authorize(string $permission): void
    {
        $user = $this->session->user();
        if (!$user || !Access::can($user['role_slug'] ?? '', $user['permissions'] ?? [], $permission)) {
            $this->response->abort(403, 'You do not have permission to perform this action.');
        }
    }

    protected function logActivity(string $type, string $description, array $data = []): void
    {
        \App\Services\AuditService::log($type, $description, $data);
    }
}
