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
    'login_throttle' => [
        'max_attempts_per_email_and_ip' => (int) env('LOGIN_THROTTLE_EMAIL_IP', 5),
        'max_attempts_per_email' => (int) env('LOGIN_THROTTLE_EMAIL', 10),
        'max_attempts_per_ip' => (int) env('LOGIN_THROTTLE_IP', 30),
        'decay_seconds' => (int) env('LOGIN_THROTTLE_DECAY_SECONDS', 60),
    ],
    'whatsapp' => [
        'base_url' => env('WHATSAPP_API_BASE_URL', 'http://46.102.156.214:3003'),
        'username' => env('WHATSAPP_API_USERNAME'),
        'password' => env('WHATSAPP_API_PASSWORD'),
    ],
];
