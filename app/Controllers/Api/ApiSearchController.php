<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiController;
use App\Core\Response;
use App\Services\SearchService;

final class ApiSearchController extends ApiController
{
    public function index(): void
    {
        $q = trim((string) $this->request->query('q', ''));
        if ($q === '') {
            Response::api(null, 'Query parameter q required', 422);
        }
        $p = $this->pagination();
        Response::api([
            'results'  => (new SearchService())->search($this->userId(), $q, $p['per_page'], $p['offset']),
            'page'     => $p['page'],
            'per_page' => $p['per_page'],
        ]);
    }
}
