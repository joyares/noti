<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiController;
use App\Core\Request;
use App\Core\Response;
use App\Models\ApiToken;
use App\Models\User;
use App\Services\NotebookSeeder;

final class ApiAuthController extends ApiController
{
    /** login/register are public; other actions call boot() themselves. */
    public function __construct(Request $request)
    {
        $this->request = $request;
        // boot() (auth) is called per-action below where needed.
    }

    public function register(): void
    {
        $email    = trim((string) $this->request->input('email', ''));
        $name     = trim((string) $this->request->input('display_name', ''));
        $password = (string) $this->request->input('password', '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::api(null, 'Invalid email', 422);
        }
        if ($name === '' || mb_strlen($name) > 100) {
            Response::api(null, 'Invalid display name', 422);
        }
        if (strlen($password) < 8) {
            Response::api(null, 'Password must be at least 8 characters', 422);
        }
        $users = new User();
        if ($users->findByEmail($email) !== null) {
            Response::api(null, 'Email already registered', 409);
        }

        $userId = $users->create($email, $name, $password);
        (new NotebookSeeder())->seed($userId);
        $token = (new ApiToken())->issue($userId, (string) $this->request->input('device_name', 'api'));

        Response::api([
            'token' => $token,
            'user'  => ['id' => $userId, 'email' => $email, 'display_name' => $name],
        ], null, 201);
    }

    public function login(): void
    {
        $email    = trim((string) $this->request->input('email', ''));
        $password = (string) $this->request->input('password', '');

        $users = new User();
        $user  = $users->findByEmail($email);
        if ($user === null || !$users->verifyPassword($user, $password)) {
            Response::api(null, 'Wrong email or password', 401);
        }

        $token = (new ApiToken())->issue((int) $user['id'], (string) $this->request->input('device_name', 'api'));

        Response::api([
            'token' => $token,
            'user'  => [
                'id'           => (int) $user['id'],
                'email'        => $user['email'],
                'display_name' => $user['display_name'],
            ],
        ]);
    }

    public function logout(): void
    {
        $this->boot();
        $plain = $this->request->bearerToken();
        if ($plain !== null) {
            (new ApiToken())->revoke($this->userId(), $plain);
        }
        Response::api(true);
    }

    public function me(): void
    {
        $this->boot();
        Response::api($this->mePayload());
    }

    /** PATCH /api/me — display_name and/or email. */
    public function updateMe(): void
    {
        $this->boot();
        $users = new User();

        $email = $this->request->input('email');
        if ($email !== null) {
            $email = trim((string) $email);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Response::api(null, 'Invalid email', 422);
            }
            $existing = $users->findByEmail($email);
            if ($existing !== null && (int) $existing['id'] !== $this->userId()) {
                Response::api(null, 'Email already taken', 409);
            }
        }
        $name = $this->request->input('display_name');
        if ($name !== null) {
            $name = trim((string) $name);
            if ($name === '' || mb_strlen($name) > 100) {
                Response::api(null, 'Invalid display name', 422);
            }
        }

        $users->updateProfile(
            $this->userId(),
            $email ?? $this->user['email'],
            $name ?? $this->user['display_name']
        );
        $this->user = $users->find($this->userId());
        Response::api($this->mePayload());
    }

    /** POST /api/me/password {current_password, new_password}. */
    public function updatePassword(): void
    {
        $this->boot();
        $users = new User();
        if (!$users->verifyPassword($this->user, (string) $this->request->input('current_password', ''))) {
            Response::api(null, 'Current password is wrong', 403);
        }
        $new = (string) $this->request->input('new_password', '');
        if (strlen($new) < 8) {
            Response::api(null, 'New password must be at least 8 characters', 422);
        }
        $users->updatePassword($this->userId(), $new);
        Response::api(true);
    }

    private function mePayload(): array
    {
        return [
            'id'           => $this->userId(),
            'email'        => $this->user['email'],
            'display_name' => $this->user['display_name'],
            'has_avatar'   => !empty($this->user['avatar_path']),
            'created_at'   => $this->user['created_at'],
        ];
    }
}
