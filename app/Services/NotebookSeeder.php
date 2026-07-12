<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Notebook;
use App\Models\Tag;

final class NotebookSeeder
{
    /** name => [color, icon key]. Order = sidebar order. */
    public const FIXED = [
        'Bookmarks'   => ['#5cb3c9', 'bookmark'],
        'Passwords'   => ['#d1857a', 'key'],
        'Links'       => ['#6ea3d8', 'link'],
        'Socials'     => ['#a487d6', 'at'],
        'Prompts'     => ['#57c785', 'terminal'],
        'Designs'     => ['#c97fae', 'pencil'],
        'TODO'        => ['#c9a05f', 'checkbox'],
        'Ideas'       => ['#bfae5c', 'bulb'],
        'Tips'        => ['#5fbcb0', 'info'],
        'Tricks'      => ['#8a94dd', 'star'],
        'Names'       => ['#b57fc9', 'person'],
        'Travels'     => ['#cf9268', 'plane'],
        'Necessaries' => ['#8ab873', 'box'],
        'Daily Needs' => ['#d0808e', 'sun'],
        'Shopping'    => ['#a3b45f', 'bag'],
        'Dress'       => ['#c98f7f', 'shirt'],
        'Articles'    => ['#7fa8c9', 'document'],
        'Sketch'      => ['#9c8fd0', 'pen'],
    ];

    /** The 18 fixed notebook colors — also used as the swatch picker for user notebooks. */
    public static function colors(): array
    {
        return array_values(array_map(static fn ($v) => $v[0], self::FIXED));
    }

    /** Seed the 18 fixed notebooks + their tags for a freshly registered user. */
    public function seed(int $userId): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $nbStmt = $pdo->prepare(
                'INSERT INTO notebooks (user_id, name, color, icon, is_fixed, is_locked, sort_order)
                 VALUES (?, ?, ?, ?, 1, ?, ?)'
            );
            $tagModel = new Tag();
            $order    = 0;
            foreach (self::FIXED as $name => [$color, $icon]) {
                $nbStmt->execute([$userId, $name, $color, $icon, $name === 'Passwords' ? 1 : 0, $order++]);
                $tagModel->ensure($userId, $name);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
