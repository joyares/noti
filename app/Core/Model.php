<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

abstract class Model
{
    protected string $table;

    protected function db(): PDO
    {
        return Database::pdo();
    }

    protected function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    public function find(int $id): ?array
    {
        $row = $this->run("SELECT * FROM {$this->table} WHERE id = ?", [$id])->fetch();

        return $row ?: null;
    }

    public function findOwned(int $id, int $userId): ?array
    {
        $row = $this->run(
            "SELECT * FROM {$this->table} WHERE id = ? AND user_id = ?",
            [$id, $userId]
        )->fetch();

        return $row ?: null;
    }

    protected function insert(array $data): int
    {
        $cols = implode(', ', array_keys($data));
        $marks = implode(', ', array_fill(0, count($data), '?'));
        $this->run("INSERT INTO {$this->table} ($cols) VALUES ($marks)", array_values($data));

        return (int) $this->db()->lastInsertId();
    }

    protected function updateById(int $id, array $data): void
    {
        $set = implode(', ', array_map(static fn ($c) => "$c = ?", array_keys($data)));
        $this->run("UPDATE {$this->table} SET $set WHERE id = ?", [...array_values($data), $id]);
    }
}
