<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

require BASE_PATH . '/app/Helpers/helpers.php';
require BASE_PATH . '/app/Helpers/icons.php';

$config = require BASE_PATH . '/config/config.php';

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;

Database::init($config['db']);
App\Core\App::init($config);

$request = new Request();

if (!$request->isApi()) {
    session_name('noti_session');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'path'     => '/',
    ]);
    session_start();
}

$router = new Router();
require BASE_PATH . '/app/routes.php';

try {
    $router->dispatch($request);
} catch (\Throwable $e) {
    error_log((string) $e);
    if ($request->isApi()) {
        Response::json(['data' => null, 'error' => 'Server error'], 500);
    } else {
        http_response_code(500);
        echo 'Server error';
    }
}
