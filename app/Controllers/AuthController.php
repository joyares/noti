<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\View;
use App\Models\User;
use App\Services\NotebookSeeder;

final class AuthController extends Controller
{
    /** Auth pages are public — skip the login gate, keep CSRF on POSTs. */
    protected function boot(): void
    {
        if ($this->request->method === 'POST' && !Csrf::validate($this->request)) {
            http_response_code(419);
            echo 'Invalid CSRF token';
            exit;
        }
    }

    public function showLogin(): void
    {
        if (Auth::userWeb() !== null) {
            Response::redirect('/');
        }
        View::render('auth/login', [], 'layouts/auth');
    }

    public function login(): void
    {
        $email    = trim((string) $this->request->input('email', ''));
        $password = (string) $this->request->input('password', '');

        $users = new User();
        $user  = $users->findByEmail($email);
        if ($user === null || !$users->verifyPassword($user, $password)) {
            View::render('auth/login', ['error' => 'Wrong email or password.', 'email' => $email], 'layouts/auth');

            return;
        }

        Auth::loginWeb($user);
        Response::redirect('/');
    }

    public function showRegister(): void
    {
        if (Auth::userWeb() !== null) {
            Response::redirect('/');
        }
        View::render('auth/register', [], 'layouts/auth');
    }

    public function register(): void
    {
        $email    = trim((string) $this->request->input('email', ''));
        $name     = trim((string) $this->request->input('display_name', ''));
        $password = (string) $this->request->input('password', '');

        $error = null;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid email address.';
        } elseif ($name === '' || mb_strlen($name) > 100) {
            $error = 'Enter a display name (max 100 chars).';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ((new User())->findByEmail($email) !== null) {
            $error = 'That email is already registered.';
        }

        if ($error !== null) {
            View::render('auth/register', [
                'error' => $error, 'email' => $email, 'display_name' => $name,
            ], 'layouts/auth');

            return;
        }

        $users  = new User();
        $userId = $users->create($email, $name, $password);
        (new NotebookSeeder())->seed($userId);

        Auth::loginWeb($users->find($userId));
        Response::redirect('/');
    }

    public function logout(): void
    {
        Auth::logoutWeb();
        Response::redirect('/login');
    }
}
