<?php

return [
    'base_url' => env('CONTRACTERA_BASE_URL', 'https://contractor.test'),

    'application_token' => env('CONTRACTERA_APPLICATION_TOKEN'),

    'default_placeholder_pattern' => env('CONTRACTERA_DEFAULT_PLACEHOLDER_PATTERN', '__#__'),

    'timeout' => (int) env('CONTRACTERA_TIMEOUT', 30),

    'retry_times' => (int) env('CONTRACTERA_RETRY_TIMES', 2),

    'retry_sleep_milliseconds' => (int) env('CONTRACTERA_RETRY_SLEEP_MILLISECONDS', 250),
];
