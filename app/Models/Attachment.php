<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Attachment extends Model
{
    protected string $table = 'attachments';

    public function forNote(int $noteId): array
    {
        return $this->run('SELECT * FROM attachments WHERE note_id = ? ORDER BY id', [$noteId])->fetchAll();
    }

    public function create(int $noteId, string $kind, string $filePath, string $fileName,
        int $fileSize, string $mime): int
    {
        return $this->insert([
            'note_id'   => $noteId,
            'kind'      => $kind,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'mime'      => $mime,
        ]);
    }

    /** Attachment row joined with its note's user_id for ownership checks. */
    public function findWithOwner(int $id): ?array
    {
        $row = $this->run(
            'SELECT a.*, n.user_id FROM attachments a JOIN notes n ON n.id = a.note_id WHERE a.id = ?',
            [$id]
        )->fetch();

        return $row ?: null;
    }

    public function delete(int $id): void
    {
        $this->run('DELETE FROM attachments WHERE id = ?', [$id]);
    }
}
