<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/app'): void
    {
        $content = self::partial($view, $data);
        if ($layout === null) {
            echo $content;

            return;
        }
        extract($data, EXTR_SKIP);
        require BASE_PATH . '/app/Views/' . $layout . '.php';
    }

    public static function partial(string $view, array $data = []): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require BASE_PATH . '/app/Views/' . $view . '.php';

        return (string) ob_get_clean();
    }
}
