<?php

return [
    'name' => env_value('APP_NAME', 'Novaone Admin'),
    'env' => env_value('APP_ENV', 'local'),
    'debug' => env_value('APP_DEBUG', false),
    'base_url' => rtrim((string) env_value('APP_URL', ''), '/'),
    'demo_user' => [
        'email' => env_value('DEMO_EMAIL', 'admin@novaone.local'),
        'password' => env_value('DEMO_PASSWORD', 'admin123'),
        'name' => env_value('DEMO_NAME', 'Admin Novaone'),
        'role' => env_value('DEMO_ROLE', 'Admin'),
    ],
];
