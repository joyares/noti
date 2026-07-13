<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Response;
use App\Models\User;

final class ProfileController extends WebController
{
    private const AVATAR_MAX = 5 * 1024 * 1024; // 5 MB
    private const AVATAR_EXT = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];

    public function index(): void
    {
        $this->render();
    }

    /** POST /profile — display name + email. */
    public function update(): void
    {
        $email = trim((string) $this->request->input('email', ''));
        $name  = trim((string) $this->request->input('display_name', ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->render(error: 'Enter a valid email address.');
        }
        if ($name === '' || mb_strlen($name) > 100) {
            $this->render(error: 'Enter a display name (max 100 chars).');
        }

        $users    = new User();
        $existing = $users->findByEmail($email);
        if ($existing !== null && (int) $existing['id'] !== $this->userId()) {
            $this->render(error: 'That email is already taken.');
        }

        $users->updateProfile($this->userId(), $email, $name);
        $this->render(success: 'Profile updated.');
    }

    /** POST /profile/password — requires the current password. */
    public function password(): void
    {
        $current = (string) $this->request->input('current_password', '');
        $new     = (string) $this->request->input('new_password', '');
        $confirm = (string) $this->request->input('confirm_password', '');

        $users = new User();
        if (!$users->verifyPassword($this->user, $current)) {
            $this->render(error: 'Current password is wrong.', section: 'password');
        }
        if (strlen($new) < 8) {
            $this->render(error: 'New password must be at least 8 characters.', section: 'password');
        }
        if ($new !== $confirm) {
            $this->render(error: 'New passwords do not match.', section: 'password');
        }

        $users->updatePassword($this->userId(), $new);
        $this->render(success: 'Password changed.', section: 'password');
    }

    /** POST /profile/avatar — multipart field "avatar"; images only. */
    public function avatar(): void
    {
        $file = $_FILES['avatar'] ?? null;
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->render(error: 'Choose an image to upload.', section: 'avatar');
        }
        if ($file['size'] > self::AVATAR_MAX) {
            $this->render(error: 'Image must be under ' . human_bytes(self::AVATAR_MAX) . '.', section: 'avatar');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']) ?: '';
        if (!isset(self::AVATAR_EXT[$mime])) {
            $this->render(error: 'Use a JPG, PNG, GIF or WebP image.', section: 'avatar');
        }

        $dir = rtrim((string) App::config('upload_dir'), '/\\') . '/avatars';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            $this->render(error: 'Cannot create the avatar directory.', section: 'avatar');
        }

        $name   = $this->userId() . '-' . bin2hex(random_bytes(8)) . '.' . self::AVATAR_EXT[$mime];
        $target = $dir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            $this->render(error: 'Could not store the image.', section: 'avatar');
        }

        // Replace: remove the previous file after the new one is in place.
        $old = $this->user['avatar_path'] ?? null;
        (new User())->updateAvatar($this->userId(), 'storage/uploads/avatars/' . $name);
        if ($old !== null && is_file(BASE_PATH . '/' . $old)) {
            @unlink(BASE_PATH . '/' . $old);
        }

        $this->render(success: 'Profile picture updated.', section: 'avatar');
    }

    /** POST /profile/avatar/remove — back to the initial-letter avatar. */
    public function removeAvatar(): void
    {
        $old = $this->user['avatar_path'] ?? null;
        (new User())->updateAvatar($this->userId(), null);
        if ($old !== null && is_file(BASE_PATH . '/' . $old)) {
            @unlink(BASE_PATH . '/' . $old);
        }
        $this->render(success: 'Profile picture removed.', section: 'avatar');
    }

    /** GET /avatar — serve the logged-in user's picture. */
    public function image(): void
    {
        $path = $this->user['avatar_path'] ?? null;
        $abs  = $path !== null ? BASE_PATH . '/' . $path : null;
        if ($abs === null || !is_file($abs)) {
            Response::notFound($this->request);
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        header('Content-Type: ' . ($finfo->file($abs) ?: 'application/octet-stream'));
        header('Content-Length: ' . filesize($abs));
        header('Cache-Control: private, max-age=60');
        header('X-Content-Type-Options: nosniff');
        readfile($abs);
        exit;
    }

    private function render(?string $error = null, ?string $success = null, string $section = 'profile'): never
    {
        // Re-read: updates above must show immediately.
        $fresh = (new User())->find($this->userId());
        $this->user = $fresh;
        $this->view('profile/index', $this->shellData() + [
            'nav'     => 'settings',
            'screen'  => 'profile',
            'error'   => $error,
            'success' => $success,
            'section' => $section,
        ]);
        exit;
    }
}
