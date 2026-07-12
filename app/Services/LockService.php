<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Models\User;

/**
 * Passwords-notebook lock. Locked notebooks (is_locked=1) hide their notes
 * until the user re-enters the account password.
 *
 * Web: unlock state lives in the session with a TTL.
 * API: stateless HMAC-signed unlock token passed via X-Unlock-Token.
 */
final class LockService
{
    public function isUnlockedWeb(int $notebookId): bool
    {
        $until = $_SESSION['unlocked'][$notebookId] ?? 0;

        return $until > time();
    }

    /** Verify password and open the unlock window. Returns success. */
    public function unlockWeb(array $user, int $notebookId, string $password): bool
    {
        if (!(new User())->verifyPassword($user, $password)) {
            return false;
        }
        $_SESSION['unlocked'][$notebookId] = time() + (int) App::config('unlock_ttl');

        return true;
    }

    /** Verify password and mint a short-lived unlock token for the API. */
    public function unlockApi(array $user, int $notebookId, string $password): ?string
    {
        if (!(new User())->verifyPassword($user, $password)) {
            return null;
        }
        $payload = $user['id'] . '|' . $notebookId . '|' . (time() + (int) App::config('unlock_ttl'));
        $sig     = hash_hmac('sha256', $payload, (string) App::config('app_secret'));

        return base64_encode($payload) . '.' . $sig;
    }

    public function isUnlockedApi(int $userId, int $notebookId, ?string $token): bool
    {
        if ($token === null || !str_contains($token, '.')) {
            return false;
        }
        [$b64, $sig] = explode('.', $token, 2);
        $payload = base64_decode($b64, true);
        if ($payload === false) {
            return false;
        }
        $expected = hash_hmac('sha256', $payload, (string) App::config('app_secret'));
        if (!hash_equals($expected, $sig)) {
            return false;
        }
        $parts = explode('|', $payload);
        if (count($parts) !== 3) {
            return false;
        }
        [$uid, $nid, $expires] = $parts;

        return (int) $uid === $userId && (int) $nid === $notebookId && (int) $expires > time();
    }
}
