<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Models\Attachment;
use App\Models\Note;
use App\Models\Notebook;
use App\Models\Tag;
use App\Services\LockService;
use App\Services\NoteService;
use App\Services\UploadService;

final class NoteController extends WebController
{
    /** GET /notes — the 3-pane desktop view. ?notebook=&note=&filter= */
    public function index(): void
    {
        $notebookId = $this->request->query('notebook');
        $notebookId = $notebookId !== null ? (int) $notebookId : null;
        $filter     = (string) $this->request->query('filter', 'all');

        $activeNotebook = null;
        $locked = false;
        if ($notebookId !== null) {
            $activeNotebook = (new Notebook())->findOwned($notebookId, $this->userId());
            if ($activeNotebook === null) {
                Response::notFound($this->request);
            }
            $locked = (int) $activeNotebook['is_locked'] === 1
                && !(new LockService())->isUnlockedWeb($notebookId);
        }

        $notes = $locked ? [] : (new Note())->listForUser($this->userId(), $notebookId, $filter);

        $currentNote = null;
        $noteId = $this->request->query('note');
        if ($noteId !== null && !$locked) {
            $currentNote = $this->loadUnlockedNote((int) $noteId);
        }

        $this->view('notes/index', $this->shellData() + [
            'screen'         => 'list',
            'nav'            => $notebookId === null ? 'notes' : 'notebooks',
            'activeNotebook' => $activeNotebook,
            'notes'          => $notes,
            'currentNote'    => $currentNote,
            'filter'         => $filter,
            'locked'         => $locked,
            'listTitle'      => $activeNotebook['name'] ?? 'All notes',
        ]);
    }

    /** GET /notes/{id} — editor (full page on mobile, 3-pane with note open on desktop). */
    public function show(string $id): void
    {
        $note = $this->loadUnlockedNote((int) $id, redirectWhenLocked: true);
        if ($note === null) {
            Response::notFound($this->request);
        }

        if ($this->request->wantsJson()) {
            Response::json(['data' => $note, 'error' => null]);
        }

        $notebookId = (int) $note['notebook_id'];
        $notes = (new Note())->listForUser($this->userId(), $notebookId);

        $this->view('notes/index', $this->shellData() + [
            'screen'         => 'editor',
            'nav'            => 'notebooks',
            'activeNotebook' => (new Notebook())->findOwned($notebookId, $this->userId()),
            'notes'          => $notes,
            'currentNote'    => $note,
            'filter'         => 'all',
            'locked'         => false,
            'listTitle'      => $note['notebook_name'],
        ]);
    }

    /** POST /notes — create (form or fetch). */
    public function store(): void
    {
        $notebookId = (int) $this->request->input('notebook_id', 0);
        $notebook   = (new Notebook())->findOwned($notebookId, $this->userId());
        if ($notebook === null) {
            // Default to the first fixed notebook (Bookmarks) if none given.
            $all = (new Notebook())->allForUser($this->userId());
            $notebook = $all[0] ?? null;
            if ($notebook === null) {
                Response::notFound($this->request);
            }
            $notebookId = (int) $notebook['id'];
        }
        $this->guardUnlocked($notebook);

        $id = (new NoteService())->create(
            $this->userId(),
            $notebookId,
            (string) $this->request->input('title', ''),
            (string) $this->request->input('body', '')
        );

        if ($this->request->wantsJson()) {
            $note = (new Note())->findFull($id, $this->userId());
            Response::json(['data' => $note, 'error' => null], 201);
        }
        Response::redirect('/notes/' . $id);
    }

    /** PATCH /notes/{id} — autosave {title, body, is_pinned, notebook_id}. */
    public function update(string $id): void
    {
        $note = $this->ownedNote((int) $id);
        $this->guardNoteUnlocked($note);

        $input = [];
        foreach (['title', 'body', 'is_pinned', 'notebook_id'] as $key) {
            $value = $this->request->input($key);
            if ($value !== null) {
                $input[$key] = $value;
            }
        }

        $result = (new NoteService())->update($this->userId(), $note, $input);
        Response::json(['data' => $result, 'error' => null]);
    }

    public function destroy(string $id): void
    {
        $note = $this->ownedNote((int) $id);
        (new Note())->trash((int) $note['id']);

        if ($this->request->wantsJson()) {
            Response::json(['data' => true, 'error' => null]);
        }
        Response::redirect('/notes?notebook=' . $note['notebook_id']);
    }

    public function restore(string $id): void
    {
        $note = $this->ownedNote((int) $id);
        (new Note())->restore((int) $note['id']);

        if ($this->request->wantsJson()) {
            Response::json(['data' => true, 'error' => null]);
        }
        Response::redirect('/trash');
    }

    public function destroyForever(string $id): void
    {
        $note = $this->ownedNote((int) $id);
        foreach ((new Attachment())->forNote((int) $note['id']) as $att) {
            (new UploadService())->delete($att);
        }
        (new Note())->deleteForever((int) $note['id']);

        if ($this->request->wantsJson()) {
            Response::json(['data' => true, 'error' => null]);
        }
        Response::redirect('/trash');
    }

    /** POST /notes/{id}/tags {name} */
    public function addTag(string $id): void
    {
        $note = $this->ownedNote((int) $id);
        $name = tag_slug((string) $this->request->input('name', ''));
        if ($name === '') {
            Response::json(['data' => null, 'error' => 'Tag name required'], 422);
        }

        $tags  = new Tag();
        $tagId = $tags->ensure($this->userId(), $name);
        $tags->attach((int) $note['id'], $tagId);

        Response::json(['data' => ['id' => $tagId, 'name' => $name], 'error' => null], 201);
    }

    public function removeTag(string $id, string $tagId): void
    {
        $note = $this->ownedNote((int) $id);
        $tag  = (new Tag())->findOwned((int) $tagId, $this->userId());
        if ($tag !== null) {
            (new Tag())->detach((int) $note['id'], (int) $tag['id']);
        }
        Response::json(['data' => true, 'error' => null]);
    }

    /** POST /notes/{id}/attachments — multipart, field name "file". */
    public function uploadAttachment(string $id): void
    {
        $note = $this->ownedNote((int) $id);
        $this->guardNoteUnlocked($note);
        if (!isset($_FILES['file'])) {
            Response::json(['data' => null, 'error' => 'No file'], 422);
        }
        try {
            $att = (new UploadService())->store($this->userId(), (int) $note['id'], $_FILES['file']);
        } catch (\RuntimeException $e) {
            Response::json(['data' => null, 'error' => $e->getMessage()], 422);
        }
        Response::json(['data' => $att, 'error' => null], 201);
    }

    public function deleteAttachment(string $id): void
    {
        $att = (new Attachment())->findWithOwner((int) $id);
        if ($att === null || (int) $att['user_id'] !== $this->userId()) {
            Response::notFound($this->request);
        }
        (new UploadService())->delete($att);
        Response::json(['data' => true, 'error' => null]);
    }

    // ── helpers ────────────────────────────────────────────────────

    private function ownedNote(int $id): array
    {
        $note = (new Note())->findFull($id, $this->userId());
        if ($note === null) {
            Response::notFound($this->request);
        }

        return $note;
    }

    private function loadUnlockedNote(int $id, bool $redirectWhenLocked = false): ?array
    {
        $note = (new Note())->findFull($id, $this->userId());
        if ($note === null) {
            return null;
        }
        if ((int) $note['notebook_locked'] === 1
            && !(new LockService())->isUnlockedWeb((int) $note['notebook_id'])) {
            if ($redirectWhenLocked) {
                Response::redirect('/notes?notebook=' . $note['notebook_id']);
            }

            return null;
        }

        return $note;
    }

    private function guardNoteUnlocked(array $note): void
    {
        if ((int) $note['notebook_locked'] === 1
            && !(new LockService())->isUnlockedWeb((int) $note['notebook_id'])) {
            Response::json(['data' => null, 'error' => 'Notebook is locked'], 423);
        }
    }

    private function guardUnlocked(array $notebook): void
    {
        if ((int) $notebook['is_locked'] === 1
            && !(new LockService())->isUnlockedWeb((int) $notebook['id'])) {
            if ($this->request->wantsJson()) {
                Response::json(['data' => null, 'error' => 'Notebook is locked'], 423);
            }
            Response::redirect('/notes?notebook=' . $notebook['id']);
        }
    }
}
