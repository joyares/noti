<?php

declare(strict_types=1);

namespace App\Controllers;

final class SettingsController extends WebController
{
    public function index(): void
    {
        $this->view('settings/index', $this->shellData() + [
            'nav'    => 'settings',
            'screen' => 'settings',
        ]);
    }
}
