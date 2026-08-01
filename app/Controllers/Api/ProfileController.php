<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\UserRepository;

final class ProfileController extends ApiController
{
    /** GET /api/profile */
    public function index(): void
    {
        $repo = new UserRepository();
        $user = $repo->withRole($this->session->userId());
        if (!$user) {
            $this->error('User not found.', 404);
        }
        unset($user['password'], $user['remember_token']);
        $this->ok(['user' => $user]);
    }

    /** PUT/POST /api/profile  { name, phone, current_password, new_password } */
    public function update(): void
    {
        $repo = new UserRepository();
        $user = $repo->withRole($this->session->userId());
        if (!$user) {
            $this->error('User not found.', 404);
        }

        $name = trim((string) $this->request->input('name'));
        $phone = trim((string) $this->request->input('phone'));
        $currentPassword = (string) $this->request->input('current_password');
        $newPassword = (string) $this->request->input('new_password');

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }
        if ($currentPassword !== '' || $newPassword !== '') {
            if (!password_verify($currentPassword, $user['password'])) {
                $errors['current_password'] = 'Current password is incorrect.';
            } elseif (strlen($newPassword) < 6) {
                $errors['new_password'] = 'New password must be at least 6 characters.';
            }
        }
        if ($errors !== []) {
            $this->validationError($errors);
        }

        $data = ['name' => $name, 'phone' => $phone !== '' ? $phone : null];
        if ($newPassword !== '') {
            $data['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        $repo->update((int) $user['id'], $data);
        $this->logActivity('profile.update', 'Profile updated');

        $fresh = $repo->withRole((int) $user['id']);
        unset($fresh['password'], $fresh['remember_token']);
        $this->ok(['user' => $fresh], 'Profile updated successfully.');
    }
}
