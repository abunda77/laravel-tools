<?php

return [
    'settings' => [
        'request_timeout_seconds' => [
            'default' => (int) env('TOOLS_REQUEST_TIMEOUT', 30),
        ],
        'request_retry_times' => [
            'default' => (int) env('TOOLS_REQUEST_RETRY_TIMES', 1),
        ],
        'request_retry_sleep_ms' => [
            'default' => (int) env('TOOLS_REQUEST_RETRY_SLEEP_MS', 500),
        ],
        'queue_connection' => [
            'default' => env('TOOLS_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),
        ],
    ],
    'whatsapp' => [
        'base_url' => env('WHATSAPP_API_BASE_URL', 'http://46.102.156.214:3003'),
        'username' => env('WHATSAPP_API_USERNAME'),
        'password' => env('WHATSAPP_API_PASSWORD'),
    ],
];
