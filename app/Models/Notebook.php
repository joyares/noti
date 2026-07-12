<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Notebook extends Model
{
    protected string $table = 'notebooks';

    /** All notebooks for a user with live note counts, fixed first. */
    public function allForUser(int $userId): array
    {
        return $this->run(
            'SELECT nb.*,
                    (SELECT COUNT(*) FROM notes n
                      WHERE n.notebook_id = nb.id AND n.is_trashed = 0) AS note_count,
                    (SELECT MAX(n.updated_at) FROM notes n
                      WHERE n.notebook_id = nb.id AND n.is_trashed = 0) AS last_note_at
               FROM notebooks nb
              WHERE nb.user_id = ?
              ORDER BY nb.is_fixed DESC, nb.sort_order ASC, nb.name ASC',
            [$userId]
        )->fetchAll();
    }

    public function create(int $userId, string $name, string $color, string $icon = 'book'): int
    {
        return $this->insert([
            'user_id'    => $userId,
            'name'       => $name,
            'color'      => $color,
            'icon'       => $icon,
            'sort_order' => 100,
        ]);
    }

    public function updateColor(int $id, string $color): void
    {
        $this->updateById($id, ['color' => $color]);
    }

    public function rename(int $id, string $name): void
    {
        $this->updateById($id, ['name' => $name]);
    }

    public function delete(int $id): void
    {
        $this->run('DELETE FROM notebooks WHERE id = ?', [$id]);
    }

    public function nameExists(int $userId, string $name): bool
    {
        return (bool) $this->run(
            'SELECT 1 FROM notebooks WHERE user_id = ? AND name = ?',
            [$userId, $name]
        )->fetch();
    }
}
