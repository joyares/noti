<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class ApiToken extends Model
{
    protected string $table = 'api_tokens';

    /** Create a token for a user; returns the plaintext token (shown once). */
    public function issue(int $userId, ?string $deviceName = null): string
    {
        $plain = bin2hex(random_bytes(32));
        $this->insert([
            'user_id'     => $userId,
            'token_hash'  => hash('sha256', $plain),
            'device_name' => $deviceName !== null ? mb_substr($deviceName, 0, 100) : null,
        ]);

        return $plain;
    }

    public function userForToken(string $plain): ?array
    {
        $row = $this->run(
            'SELECT u.*, t.id AS token_id FROM api_tokens t
               JOIN users u ON u.id = t.user_id
              WHERE t.token_hash = ?',
            [hash('sha256', $plain)]
        )->fetch();
        if (!$row) {
            return null;
        }
        $this->run('UPDATE api_tokens SET last_used_at = NOW() WHERE id = ?', [$row['token_id']]);
        unset($row['token_id']);

        return $row;
    }

    public function revoke(int $userId, string $plain): void
    {
        $this->run(
            'DELETE FROM api_tokens WHERE user_id = ? AND token_hash = ?',
            [$userId, hash('sha256', $plain)]
        );
    }
}
