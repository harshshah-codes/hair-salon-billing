<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\BranchRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;

class AuthController extends Controller
{
    public function login(): void
    {
        $this->render('auth.login', [
            'title'    => 'Sign In',
            'branches' => (new BranchRepository())->active(),
        ], 'auth');
    }

    public function authenticate(): void
    {
        $email = trim((string) $this->request->input('email'));
        $password = (string) $this->request->input('password');
        $branchId = (int) $this->request->input('branch_id');

        if ($email === '' || $password === '') {
            $this->session->flash('error', 'Please enter your email and password.');
            $this->back();
        }

        $branch = (new BranchRepository())->find($branchId);
        if (!$branch || $branch['status'] !== 'active') {
            $this->session->flash('error', 'Please select a valid branch.');
            $this->back();
        }

        // A user attached to a branch can only sign in to that branch.
        $user = (new UserRepository())->findByEmail($email);
        if ($user && !empty($user['branch_id']) && (int) $user['branch_id'] !== $branchId) {
            $this->session->flash('error', 'This account can only access its assigned branch.');
            $this->back();
        }

        if ((new AuthService())->attempt($email, $password)) {
            $this->session->setBranch($branchId);
            $this->session->flash('success', 'Welcome back!');
            $this->redirect('/dashboard');
        }

        $this->session->flash('error', 'Invalid credentials or inactive account.');
        $this->back();
    }

    public function logout(): void
    {
        $this->logActivity('auth.logout', 'User signed out: ' . ($this->session->user()['email'] ?? ''));
        $this->session->logout();
        $this->session->flash('success', 'You have been signed out.');
        $this->redirect('/auth/login');
    }
}
