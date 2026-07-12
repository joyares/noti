<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiController;
use App\Core\Response;
use App\Models\Note;
use App\Models\Tag;

final class ApiTagController extends ApiController
{
    public function index(): void
    {
        Response::api((new Tag())->allForUser($this->userId()));
    }

    public function notes(string $name): void
    {
        $tag = (new Tag())->findByName($this->userId(), $name);
        if ($tag === null) {
            Response::api(null, 'Not found', 404);
        }
        $p = $this->pagination();
        $notes = (new Note())->listForTag($this->userId(), (int) $tag['id'], $p['per_page'], $p['offset']);
        Response::api([
            'tag'      => $tag,
            'notes'    => $notes,
            'page'     => $p['page'],
            'per_page' => $p['per_page'],
        ]);
    }
}
