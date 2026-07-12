<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Note extends Model
{
    protected string $table = 'notes';

    private const LIST_SELECT = 'SELECT n.id, n.notebook_id, n.title, n.body_text, n.size_bytes,
            n.is_pinned, n.is_trashed, n.created_at, n.updated_at,
            nb.name AS notebook_name, nb.color AS notebook_color,
            nb.icon AS notebook_icon, nb.is_locked AS notebook_locked
       FROM notes n JOIN notebooks nb ON nb.id = n.notebook_id';

    public function listForUser(int $userId, ?int $notebookId = null, string $filter = 'all',
        int $limit = 200, int $offset = 0): array
    {
        $sql    = self::LIST_SELECT . ' WHERE n.user_id = ? AND n.is_trashed = 0';
        $params = [$userId];
        if ($notebookId !== null) {
            $sql .= ' AND n.notebook_id = ?';
            $params[] = $notebookId;
        }
        if ($filter === 'pinned') {
            $sql .= ' AND n.is_pinned = 1';
        } elseif ($filter === 'recent') {
            $sql .= ' AND n.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
        } elseif ($filter === 'open') {
            // TODO notebook: at least one unchecked checklist item remains.
            $sql .= " AND n.body LIKE '%<input type=\"checkbox\">%'";
        } elseif ($filter === 'done') {
            // Has checklist items and none of them unchecked.
            $sql .= " AND n.body LIKE '%<input type=\"checkbox\"%' AND n.body NOT LIKE '%<input type=\"checkbox\">%'";
        }
        $sql .= ' ORDER BY n.is_pinned DESC, n.updated_at DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

        return $this->attachTags($this->run($sql, $params)->fetchAll());
    }

    public function listTrashed(int $userId): array
    {
        return $this->run(
            self::LIST_SELECT . ' WHERE n.user_id = ? AND n.is_trashed = 1 ORDER BY n.trashed_at DESC',
            [$userId]
        )->fetchAll();
    }

    public function listForTag(int $userId, int $tagId, int $limit = 100, int $offset = 0): array
    {
        $rows = $this->run(
            self::LIST_SELECT . '
               JOIN note_tags nt ON nt.note_id = n.id
              WHERE n.user_id = ? AND nt.tag_id = ? AND n.is_trashed = 0
              ORDER BY n.updated_at DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset,
            [$userId, $tagId]
        )->fetchAll();

        return $this->attachTags($rows, withAttachments: true);
    }

    public function findFull(int $id, int $userId): ?array
    {
        $row = $this->run(
            'SELECT n.*, nb.name AS notebook_name, nb.color AS notebook_color,
                    nb.icon AS notebook_icon, nb.is_locked AS notebook_locked
               FROM notes n JOIN notebooks nb ON nb.id = n.notebook_id
              WHERE n.id = ? AND n.user_id = ?',
            [$id, $userId]
        )->fetch();
        if (!$row) {
            return null;
        }
        [$row] = $this->attachTags([$row], withAttachments: true);

        return $row;
    }

    public function create(int $userId, int $notebookId, string $title = '', string $body = '',
        string $bodyText = ''): int
    {
        return $this->insert([
            'user_id'     => $userId,
            'notebook_id' => $notebookId,
            'title'       => $title,
            'body'        => $body,
            'body_text'   => $bodyText,
            'size_bytes'  => strlen($body),
        ]);
    }

    public function update(int $id, array $fields): void
    {
        $this->updateById($id, $fields);
    }

    public function trash(int $id): void
    {
        $this->run('UPDATE notes SET is_trashed = 1, trashed_at = NOW() WHERE id = ?', [$id]);
    }

    public function restore(int $id): void
    {
        $this->run('UPDATE notes SET is_trashed = 0, trashed_at = NULL WHERE id = ?', [$id]);
    }

    public function purgeExpired(int $userId, int $days): void
    {
        $this->run(
            'DELETE FROM notes WHERE user_id = ? AND is_trashed = 1
              AND trashed_at < DATE_SUB(NOW(), INTERVAL ? DAY)',
            [$userId, $days]
        );
    }

    public function deleteForever(int $id): void
    {
        $this->run('DELETE FROM notes WHERE id = ?', [$id]);
    }

    /** Attach `tags` (and optionally `attachments`) arrays to each note row. */
    private function attachTags(array $notes, bool $withAttachments = false): array
    {
        if ($notes === []) {
            return $notes;
        }
        $ids   = array_column($notes, 'id');
        $marks = implode(',', array_fill(0, count($ids), '?'));

        $tagRows = $this->run(
            "SELECT nt.note_id, t.id, t.name FROM note_tags nt
               JOIN tags t ON t.id = nt.tag_id
              WHERE nt.note_id IN ($marks) ORDER BY t.name",
            $ids
        )->fetchAll();

        $byNote = [];
        foreach ($tagRows as $t) {
            $byNote[$t['note_id']][] = ['id' => (int) $t['id'], 'name' => $t['name']];
        }

        $attByNote = [];
        if ($withAttachments) {
            $attRows = $this->run(
                "SELECT * FROM attachments WHERE note_id IN ($marks) ORDER BY id",
                $ids
            )->fetchAll();
            foreach ($attRows as $a) {
                $attByNote[$a['note_id']][] = $a;
            }
        }

        foreach ($notes as &$n) {
            $n['tags'] = $byNote[$n['id']] ?? [];
            if ($withAttachments) {
                $n['attachments'] = $attByNote[$n['id']] ?? [];
            }
        }

        return $notes;
    }
}
