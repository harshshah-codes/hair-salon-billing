<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'Nirav Hair Storm',
        'env' => getenv('APP_ENV') ?: 'production',
        'debug' => (getenv('APP_DEBUG') ?: 'false') === 'true',
        'url' => getenv('APP_URL') ?: 'http://localhost/salon',
        'timezone' => 'Asia/Kolkata',
        'currency' => 'INR',
        'currency_symbol' => '₹',
        'version' => '1.0.0',
    ],
    'session' => [
        'name' => 'nhs_session',
        'lifetime' => 7200,
        'cookie_secure' => false,
        'cookie_httponly' => true,
    ],
    'uploads' => [
        'dir' => dirname(__DIR__) . '/public/uploads',
        'url' => 'uploads',
        'max_size' => 2 * 1024 * 1024,
        'allowed' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
    ],
];
