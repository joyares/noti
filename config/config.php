<?php

return [
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'name'     => 'noti',
        'user'     => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],
    // Used to sign short-lived unlock tokens for the API. Change in production.
    'app_secret'  => 'change-me-to-a-long-random-string',
    'upload_dir'  => dirname(__DIR__) . '/storage/uploads',
    'max_upload'  => 20 * 1024 * 1024, // 20 MB
    'trash_days'  => 30,
    'unlock_ttl'  => 600, // Passwords notebook unlock window, seconds
];
