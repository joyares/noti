<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Models\Note;
use App\Models\Tag;

final class TagController extends WebController
{
    public function index(): void
    {
        $this->renderTags(null);
    }

    public function show(string $name): void
    {
        $this->renderTags($name);
    }

    private function renderTags(?string $activeName): void
    {
        $tags = (new Tag())->allForUser($this->userId());

        $activeTag = null;
        $results   = [];
        if ($activeName !== null) {
            $activeTag = (new Tag())->findByName($this->userId(), $activeName);
            if ($activeTag === null) {
                Response::notFound($this->request);
            }
            $results = (new Note())->listForTag($this->userId(), (int) $activeTag['id']);
        }

        $this->view('tags/index', $this->shellData() + [
            'nav'       => 'tags',
            'screen'    => 'tags',
            'tags'      => $tags,
            'activeTag' => $activeTag,
            'results'   => $results,
        ]);
    }
}
