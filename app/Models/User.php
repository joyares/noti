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

    public function updateProfile(int $id, string $email, string $displayName): void
    {
        $this->updateById($id, ['email' => $email, 'display_name' => $displayName]);
    }

    public function updatePassword(int $id, string $password): void
    {
        $this->updateById($id, ['password_hash' => password_hash($password, PASSWORD_BCRYPT)]);
    }

    public function updateAvatar(int $id, ?string $path): void
    {
        $this->updateById($id, ['avatar_path' => $path]);
    }
}
