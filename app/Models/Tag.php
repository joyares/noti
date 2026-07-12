<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Tag extends Model
{
    protected string $table = 'tags';

    /** All tags for a user with usage counts (non-trashed notes only). */
    public function allForUser(int $userId): array
    {
        return $this->run(
            'SELECT t.id, t.name,
                    COUNT(CASE WHEN n.is_trashed = 0 THEN nt.note_id END) AS note_count
               FROM tags t
               LEFT JOIN note_tags nt ON nt.tag_id = t.id
               LEFT JOIN notes n ON n.id = nt.note_id
              WHERE t.user_id = ?
              GROUP BY t.id, t.name
              ORDER BY t.name',
            [$userId]
        )->fetchAll();
    }

    public function findByName(int $userId, string $name): ?array
    {
        $row = $this->run(
            'SELECT * FROM tags WHERE user_id = ? AND name = ?',
            [$userId, tag_slug($name)]
        )->fetch();

        return $row ?: null;
    }

    /** Find or create by (slugged) name; returns tag id. */
    public function ensure(int $userId, string $name): int
    {
        $slug = tag_slug($name);
        if ($slug === '') {
            throw new \InvalidArgumentException('Empty tag name');
        }
        $existing = $this->findByName($userId, $slug);
        if ($existing !== null) {
            return (int) $existing['id'];
        }

        return $this->insert(['user_id' => $userId, 'name' => $slug]);
    }

    public function attach(int $noteId, int $tagId): void
    {
        $this->run('INSERT IGNORE INTO note_tags (note_id, tag_id) VALUES (?, ?)', [$noteId, $tagId]);
    }

    public function detach(int $noteId, int $tagId): void
    {
        $this->run('DELETE FROM note_tags WHERE note_id = ? AND tag_id = ?', [$noteId, $tagId]);
    }
}
