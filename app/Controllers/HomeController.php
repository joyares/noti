<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Note;

final class HomeController extends WebController
{
    public function index(): void
    {
        $notes = (new Note())->listForUser($this->userId());

        $this->view('notes/index', $this->shellData() + [
            'screen'         => 'home',
            'nav'            => 'home',
            'activeNotebook' => null,
            'notes'          => $notes,
            'currentNote'    => null,
            'filter'         => 'all',
            'locked'         => false,
            'listTitle'      => 'All notes',
        ]);
    }
}
