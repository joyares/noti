<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiController;
use App\Core\Response;
use App\Models\Note;
use App\Models\Notebook;
use App\Services\LockService;
use App\Services\NotebookSeeder;

final class ApiNotebookController extends ApiController
{
    public function index(): void
    {
        Response::api((new Notebook())->allForUser($this->userId()));
    }

    public function store(): void
    {
        $name  = trim((string) $this->request->input('name', ''));
        $color = (string) $this->request->input('color', '#57c785');
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#57c785';
        }
        $notebooks = new Notebook();
        if ($name === '' || mb_strlen($name) > 100) {
            Response::api(null, 'Invalid notebook name', 422);
        }
        if ($notebooks->nameExists($this->userId(), $name)) {
            Response::api(null, 'Duplicate notebook name', 409);
        }
        $id = $notebooks->create($this->userId(), $name, $color);
        Response::api($notebooks->find($id), null, 201);
    }

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
                Response::api(null, 'Fixed notebooks cannot be renamed', 422);
            }
            $name = trim($name);
            if (mb_strlen($name) > 100 || $model->nameExists($this->userId(), $name)) {
                Response::api(null, 'Invalid or duplicate name', 422);
            }
            $model->rename((int) $notebook['id'], $name);
        }

        Response::api($model->find((int) $notebook['id']));
    }

    public function destroy(string $id): void
    {
        $notebook = $this->owned((int) $id);
        if ((int) $notebook['is_fixed'] === 1) {
            Response::api(null, 'Fixed notebooks cannot be deleted', 422);
        }
        \App\Core\Database::pdo()->prepare('DELETE FROM notes WHERE notebook_id = ? AND user_id = ?')
            ->execute([(int) $notebook['id'], $this->userId()]);
        (new Notebook())->delete((int) $notebook['id']);
        Response::api(true);
    }

    /** GET /api/notebooks/{id}/notes — 423 while a locked notebook is not unlocked. */
    public function notes(string $id): void
    {
        $notebook = $this->owned((int) $id);
        if ((int) $notebook['is_locked'] === 1) {
            $token = $this->request->header('X-Unlock-Token');
            if (!(new LockService())->isUnlockedApi($this->userId(), (int) $notebook['id'], $token)) {
                Response::api(null, 'Notebook is locked — unlock first', 423);
            }
        }

        $p     = $this->pagination();
        $notes = (new Note())->listForUser(
            $this->userId(),
            (int) $notebook['id'],
            (string) $this->request->query('filter', 'all'),
            $p['per_page'],
            $p['offset']
        );

        Response::api(['notes' => $notes, 'page' => $p['page'], 'per_page' => $p['per_page']]);
    }

    /** POST /api/notebooks/{id}/unlock {password} → short-lived unlock token. */
    public function unlock(string $id): void
    {
        $notebook = $this->owned((int) $id);
        $token = (new LockService())->unlockApi(
            $this->user,
            (int) $notebook['id'],
            (string) $this->request->input('password', '')
        );
        if ($token === null) {
            Response::api(null, 'Wrong password', 403);
        }
        Response::api(['unlock_token' => $token, 'expires_in' => \App\Core\App::config('unlock_ttl')]);
    }

    private function owned(int $id): array
    {
        $notebook = (new Notebook())->findOwned($id, $this->userId());
        if ($notebook === null) {
            Response::api(null, 'Not found', 404);
        }

        return $notebook;
    }
}
