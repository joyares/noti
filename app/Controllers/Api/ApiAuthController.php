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
        Response::api([
            'id'           => $this->userId(),
            'email'        => $this->user['email'],
            'display_name' => $this->user['display_name'],
            'created_at'   => $this->user['created_at'],
        ]);
    }
}
