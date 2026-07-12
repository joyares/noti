<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class SearchService
{
    /**
     * FULLTEXT search over title + body_text, with `tag:foo` prefix filters.
     * "tag:shopping milk" → notes tagged #shopping matching "milk".
     */
    public function search(int $userId, string $query, int $limit = 50, int $offset = 0): array
    {
        $tags = [];
        $terms = [];
        foreach (preg_split('/\s+/', trim($query)) ?: [] as $word) {
            if (str_starts_with(strtolower($word), 'tag:')) {
                $slug = tag_slug(substr($word, 4));
                if ($slug !== '') {
                    $tags[] = $slug;
                }
            } elseif ($word !== '') {
                $terms[] = $word;
            }
        }

        $sql = 'SELECT n.id, n.notebook_id, n.title, n.body_text, n.size_bytes, n.is_pinned,
                       n.updated_at, nb.name AS notebook_name, nb.color AS notebook_color,
                       nb.icon AS notebook_icon, nb.is_locked AS notebook_locked
                  FROM notes n
                  JOIN notebooks nb ON nb.id = n.notebook_id
                 WHERE n.user_id = ? AND n.is_trashed = 0';
        $params = [$userId];

        if ($terms !== []) {
            $boolean = implode(' ', array_map(
                static fn ($t) => '+' . preg_replace('/[+\-<>()~*"@]/', '', $t) . '*',
                $terms
            ));
            $sql .= ' AND MATCH(n.title, n.body_text) AGAINST (? IN BOOLEAN MODE)';
            $params[] = $boolean;
        }

        foreach ($tags as $slug) {
            $sql .= ' AND EXISTS (SELECT 1 FROM note_tags nt JOIN tags t ON t.id = nt.tag_id
                       WHERE nt.note_id = n.id AND t.user_id = n.user_id AND t.name = ?)';
            $params[] = $slug;
        }

        if ($terms === [] && $tags === []) {
            return [];
        }

        $sql .= ' ORDER BY n.updated_at DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}
