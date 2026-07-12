<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiController;
use App\Core\Response;
use App\Models\Attachment;
use App\Models\Note;
use App\Models\Notebook;
use App\Models\Tag;
use App\Services\LockService;
use App\Services\NoteService;
use App\Services\UploadService;

final class ApiNoteController extends ApiController
{
    public function index(): void
    {
        $p     = $this->pagination();
        $notes = (new Note())->listForUser(
            $this->userId(),
            null,
            (string) $this->request->query('filter', 'all'),
            $p['per_page'],
            $p['offset']
        );
        Response::api(['notes' => $notes, 'page' => $p['page'], 'per_page' => $p['per_page']]);
    }

    public function store(): void
    {
        $notebookId = (int) $this->request->input('notebook_id', 0);
        $notebook   = (new Notebook())->findOwned($notebookId, $this->userId());
        if ($notebook === null) {
            Response::api(null, 'notebook_id required and must be yours', 422);
        }
        $this->guardLocked($notebook);

        $id = (new NoteService())->create(
            $this->userId(),
            $notebookId,
            (string) $this->request->input('title', ''),
            (string) $this->request->input('body', '')
        );
        Response::api((new Note())->findFull($id, $this->userId()), null, 201);
    }

    public function show(string $id): void
    {
        $note = $this->owned((int) $id);
        $this->guardNoteLocked($note);
        Response::api($note);
    }

    public function update(string $id): void
    {
        $note = $this->owned((int) $id);
        $this->guardNoteLocked($note);

        $input = [];
        foreach (['title', 'body', 'is_pinned', 'notebook_id'] as $key) {
            $value = $this->request->input($key);
            if ($value !== null) {
                $input[$key] = $value;
            }
        }
        Response::api((new NoteService())->update($this->userId(), $note, $input));
    }

    public function destroy(string $id): void
    {
        $note = $this->owned((int) $id);
        (new Note())->trash((int) $note['id']);
        Response::api(true);
    }

    public function restore(string $id): void
    {
        $note = $this->owned((int) $id);
        (new Note())->restore((int) $note['id']);
        Response::api(true);
    }

    public function trash(): void
    {
        Response::api((new Note())->listTrashed($this->userId()));
    }

    public function addTag(string $id): void
    {
        $note = $this->owned((int) $id);
        $name = tag_slug((string) $this->request->input('name', ''));
        if ($name === '') {
            Response::api(null, 'Tag name required', 422);
        }
        $tags  = new Tag();
        $tagId = $tags->ensure($this->userId(), $name);
        $tags->attach((int) $note['id'], $tagId);
        Response::api(['id' => $tagId, 'name' => $name], null, 201);
    }

    public function removeTag(string $id, string $tagId): void
    {
        $note = $this->owned((int) $id);
        $tag  = (new Tag())->findOwned((int) $tagId, $this->userId());
        if ($tag !== null) {
            (new Tag())->detach((int) $note['id'], (int) $tag['id']);
        }
        Response::api(true);
    }

    public function uploadAttachment(string $id): void
    {
        $note = $this->owned((int) $id);
        $this->guardNoteLocked($note);
        if (!isset($_FILES['file'])) {
            Response::api(null, 'Multipart field "file" required', 422);
        }
        try {
            $att = (new UploadService())->store($this->userId(), (int) $note['id'], $_FILES['file']);
        } catch (\RuntimeException $e) {
            Response::api(null, $e->getMessage(), 422);
        }
        Response::api($att, null, 201);
    }

    private function owned(int $id): array
    {
        $note = (new Note())->findFull($id, $this->userId());
        if ($note === null) {
            Response::api(null, 'Not found', 404);
        }

        return $note;
    }

    private function guardNoteLocked(array $note): void
    {
        if ((int) $note['notebook_locked'] === 1) {
            $token = $this->request->header('X-Unlock-Token');
            if (!(new LockService())->isUnlockedApi($this->userId(), (int) $note['notebook_id'], $token)) {
                Response::api(null, 'Notebook is locked — unlock first', 423);
            }
        }
    }

    private function guardLocked(array $notebook): void
    {
        if ((int) $notebook['is_locked'] === 1) {
            $token = $this->request->header('X-Unlock-Token');
            if (!(new LockService())->isUnlockedApi($this->userId(), (int) $notebook['id'], $token)) {
                Response::api(null, 'Notebook is locked — unlock first', 423);
            }
        }
    }
}
