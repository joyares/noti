<?php

// Dev router for `php -S localhost:8080 server.php` (Apache uses .htaccess instead).
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . '/public' . $path;
if ($path !== '/' && is_file($file)) {
    return false; // let the built-in server stream static assets
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/public/index.php';
