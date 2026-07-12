<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Services\SearchService;

final class SearchController extends WebController
{
    /** GET /search?q= — JSON for the ⌘K overlay, full page on mobile. */
    public function index(): void
    {
        $q       = trim((string) $this->request->query('q', ''));
        $results = $q === '' ? [] : (new SearchService())->search($this->userId(), $q);

        if ($this->request->wantsJson()) {
            Response::json(['data' => $results, 'error' => null]);
        }

        $this->view('search/index', $this->shellData() + [
            'nav'     => 'search',
            'screen'  => 'search',
            'q'       => $q,
            'results' => $results,
        ]);
    }
}
