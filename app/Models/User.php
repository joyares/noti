<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class User extends Model
{
    protected string $table = 'users';

    public function findByEmail(string $email): ?array
    {
        $row = $this->run('SELECT * FROM users WHERE email = ?', [$email])->fetch();

        return $row ?: null;
    }

    public function create(string $email, string $displayName, string $password): int
    {
        return $this->insert([
            'email'         => $email,
            'display_name'  => $displayName,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);
    }

    public function verifyPassword(array $user, string $password): bool
    {
        return password_verify($password, $user['password_hash']);
    }
}
