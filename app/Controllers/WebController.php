<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Notebook;

abstract class WebController extends Controller
{
    /** Data every app screen needs (sidebar / tab bar). */
    protected function shellData(): array
    {
        return [
            'notebooks' => (new Notebook())->allForUser($this->userId()),
        ];
    }
}
