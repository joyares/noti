<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Models\Note;

final class TrashController extends WebController
{
    public function index(): void
    {
        $notes = new Note();
        // Lazy purge: anything trashed longer than trash_days is gone for good.
        $notes->purgeExpired($this->userId(), (int) App::config('trash_days'));

        $this->view('trash/index', $this->shellData() + [
            'nav'    => 'trash',
            'screen' => 'trash',
            'notes'  => $notes->listTrashed($this->userId()),
            'days'   => (int) App::config('trash_days'),
        ]);
    }
}
