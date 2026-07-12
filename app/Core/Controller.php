<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
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
        $user = Auth::userWeb();
        if ($user === null) {
            Response::redirect('/login');
        }
        $this->user = $user;

        if (in_array($this->request->method, ['POST', 'PATCH', 'DELETE'], true)
            && !Csrf::validate($this->request)) {
            if ($this->request->wantsJson()) {
                Response::api(null, 'Invalid CSRF token', 419);
            }
            http_response_code(419);
            echo 'Invalid CSRF token';
            exit;
        }
    }

    protected function userId(): int
    {
        return (int) $this->user['id'];
    }

    protected function view(string $view, array $data = []): void
    {
        $data['user'] = $this->user;
        View::render($view, $data);
    }
}
