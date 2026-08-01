<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;

class AuthController extends Controller
{
    public function login(): void
    {
        $this->render('auth.login', ['title' => 'Sign In'], 'auth');
    }

    public function authenticate(): void
    {
        $email = trim((string) $this->request->input('email'));
        $password = (string) $this->request->input('password');

        if ($email === '' || $password === '') {
            $this->session->flash('error', 'Please enter your email and password.');
            $this->back();
        }

        if ((new AuthService())->attempt($email, $password)) {
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
