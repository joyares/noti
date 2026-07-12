<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Models\Attachment;

final class UploadService
{
    private const KIND_BY_MIME = [
        'image/' => 'image',
        'audio/' => 'audio',
    ];

    private const BLOCKED_EXT = ['php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'cgi', 'exe', 'bat', 'cmd', 'sh', 'js', 'html', 'htm', 'svg'];

    /**
     * Store an uploaded file for a note. $file is one entry from $_FILES.
     * Returns the attachment row, or throws on validation failure.
     */
    public function store(int $userId, int $noteId, array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload failed (error ' . ($file['error'] ?? '?') . ')');
        }
        $max = (int) App::config('max_upload');
        if ($file['size'] > $max) {
            throw new \RuntimeException('File exceeds ' . human_bytes($max));
        }

        $originalName = basename((string) $file['name']);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (in_array($ext, self::BLOCKED_EXT, true)) {
            throw new \RuntimeException('File type not allowed');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';

        $kind = 'file';
        foreach (self::KIND_BY_MIME as $prefix => $k) {
            if (str_starts_with($mime, $prefix)) {
                $kind = $k;
                break;
            }
        }

        $dir = rtrim((string) App::config('upload_dir'), '/\\') . '/' . $userId;
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Cannot create upload directory');
        }

        $storedName = bin2hex(random_bytes(16)) . ($ext !== '' ? '.' . $ext : '');
        $target     = $dir . '/' . $storedName;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new \RuntimeException('Could not store file');
        }

        $relPath = 'storage/uploads/' . $userId . '/' . $storedName;
        $model   = new Attachment();
        $id      = $model->create($noteId, $kind, $relPath, $originalName, (int) $file['size'], $mime);

        return $model->find($id);
    }

    public function delete(array $attachment): void
    {
        (new Attachment())->delete((int) $attachment['id']);
        $abs = BASE_PATH . '/' . $attachment['file_path'];
        if (is_file($abs)) {
            @unlink($abs);
        }
    }
}
