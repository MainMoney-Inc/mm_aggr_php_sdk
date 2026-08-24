<?php

$origins = array_filter(array_map('trim', explode(',', (string) env(
    'CORS_ORIGINS',
    'http://127.0.0.1:5173,http://127.0.0.1:5174,http://127.0.0.1:5175,http://127.0.0.1:4200',
))));

return [
    'paths' => ['*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => $origins,
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
