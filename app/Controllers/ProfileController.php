<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\UserRepository;

class ProfileController extends Controller
{
    public function index(): void
    {
        $user = (new UserRepository())->withRole($this->session->userId());
        $this->render('profile.index', [
            'title' => 'My Profile',
            'active' => 'profile',
            'user' => $user,
            'breadcrumbs' => ['Profile' => '/profile'],
        ]);
    }

    public function update(): void
    {
        $repo = new UserRepository();
        $user = $repo->find($this->session->userId());

        $name = trim((string) $this->request->input('name'));
        $phone = trim((string) $this->request->input('phone'));
        $current = (string) $this->request->input('current_password');
        $newPassword = (string) $this->request->input('new_password');

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }

        if ($current !== '' || $newPassword !== '') {
            if (!password_verify($current, $user['password'])) {
                $errors['current_password'] = 'Current password is incorrect.';
            } elseif (strlen($newPassword) < 6) {
                $errors['new_password'] = 'New password must be at least 6 characters.';
            }
        }

        if ($errors) {
            $this->session->setErrors($errors);
            $this->session->flash('error', 'Please fix the highlighted fields.');
            $this->back();
        }

        $data = ['name' => $name, 'phone' => $phone ?: null];
        if ($newPassword !== '') {
            $data['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }
        $repo->update($user['id'], $data);

        // Refresh session identity
        $fresh = $repo->find($user['id']);
        $session = $this->session;
        $session->set('user', array_merge($session->user() ?? [], [
            'id' => (int) $fresh['id'],
            'name' => $fresh['name'],
        ]));

        $this->logActivity('profile.update', 'Profile updated');
        $this->session->flash('success', 'Profile updated successfully.');
        $this->back();
    }
}
