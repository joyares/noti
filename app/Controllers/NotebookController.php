<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Models\Notebook;
use App\Services\LockService;
use App\Services\NotebookSeeder;

final class NotebookController extends WebController
{
    /** GET /notebooks — overview grid. */
    public function index(): void
    {
        $this->view('notebooks/index', $this->shellData() + [
            'nav'     => 'notebooks',
            'screen'  => 'notebooks',
            'palette' => NotebookSeeder::colors(),
        ]);
    }

    /** GET /notebooks/{id} — notebook note list (delegates to the 3-pane view). */
    public function show(string $id): void
    {
        Response::redirect('/notes?notebook=' . (int) $id);
    }

    /** POST /notebooks {name, color} */
    public function store(): void
    {
        $name  = trim((string) $this->request->input('name', ''));
        $color = (string) $this->request->input('color', '#57c785');
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#57c785';
        }

        $notebooks = new Notebook();
        if ($name === '' || mb_strlen($name) > 100) {
            $this->fail('Enter a notebook name (max 100 chars).');
        }
        if ($notebooks->nameExists($this->userId(), $name)) {
            $this->fail('You already have a notebook with that name.');
        }

        $id = $notebooks->create($this->userId(), $name, $color);

        if ($this->request->wantsJson()) {
            Response::json(['data' => $notebooks->find($id), 'error' => null], 201);
        }
        Response::redirect('/notebooks');
    }

    /** PATCH /notebooks/{id} — color always; name only for user notebooks. */
    public function update(string $id): void
    {
        $notebook = $this->owned((int) $id);
        $model    = new Notebook();

        $color = $this->request->input('color');
        if (is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $model->updateColor((int) $notebook['id'], $color);
        }

        $name = $this->request->input('name');
        if (is_string($name) && trim($name) !== '') {
            if ((int) $notebook['is_fixed'] === 1) {
                Response::json(['data' => null, 'error' => 'Fixed notebooks cannot be renamed'], 422);
            }
            $name = trim($name);
            if (mb_strlen($name) > 100 || $model->nameExists($this->userId(), $name)) {
                Response::json(['data' => null, 'error' => 'Invalid or duplicate name'], 422);
            }
            $model->rename((int) $notebook['id'], $name);
        }

        Response::json(['data' => $model->find((int) $notebook['id']), 'error' => null]);
    }

    public function destroy(string $id): void
    {
        $notebook = $this->owned((int) $id);
        if ((int) $notebook['is_fixed'] === 1) {
            Response::json(['data' => null, 'error' => 'Fixed notebooks cannot be deleted'], 422);
        }
        // Notes in the notebook go with it (FK requires cleanup first: move to trash-less delete).
        \App\Core\Database::pdo()->prepare('DELETE FROM notes WHERE notebook_id = ? AND user_id = ?')
            ->execute([(int) $notebook['id'], $this->userId()]);
        (new Notebook())->delete((int) $notebook['id']);

        if ($this->request->wantsJson()) {
            Response::json(['data' => true, 'error' => null]);
        }
        Response::redirect('/notebooks');
    }

    /** POST /notebooks/{id}/unlock {password} — Passwords notebook. */
    public function unlock(string $id): void
    {
        $notebook = $this->owned((int) $id);
        $password = (string) $this->request->input('password', '');
        $ok = (new LockService())->unlockWeb($this->user, (int) $notebook['id'], $password);

        if ($this->request->wantsJson()) {
            if (!$ok) {
                Response::json(['data' => null, 'error' => 'Wrong password'], 403);
            }
            Response::json(['data' => true, 'error' => null]);
        }
        Response::redirect('/notes?notebook=' . $notebook['id'] . ($ok ? '' : '&unlock_error=1'));
    }

    private function owned(int $id): array
    {
        $notebook = (new Notebook())->findOwned($id, $this->userId());
        if ($notebook === null) {
            Response::notFound($this->request);
        }

        return $notebook;
    }

    private function fail(string $message): never
    {
        if ($this->request->wantsJson()) {
            Response::json(['data' => null, 'error' => $message], 422);
        }
        Response::redirect('/notebooks?error=' . urlencode($message));
    }
}
