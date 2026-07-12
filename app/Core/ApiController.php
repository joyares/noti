<?php

declare(strict_types=1);

namespace App\Core;

abstract class ApiController
{
    protected Request $request;
    protected array $user;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->boot();
    }

    protected function boot(): void
    {
        $user = Auth::userApi($this->request);
        if ($user === null) {
            Response::api(null, 'Unauthenticated', 401);
        }
        $this->user = $user;
    }

    protected function userId(): int
    {
        return (int) $this->user['id'];
    }

    /** @return array{page:int, per_page:int, offset:int} */
    protected function pagination(): array
    {
        $page    = max(1, (int) $this->request->query('page', 1));
        $perPage = min(100, max(1, (int) $this->request->query('per_page', 30)));

        return ['page' => $page, 'per_page' => $perPage, 'offset' => ($page - 1) * $perPage];
    }
}
