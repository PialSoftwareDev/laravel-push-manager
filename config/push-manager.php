
<?php

return [
    'project_id' => env('FCM_PROJECT_ID'),

    'credentials' => [
        'file' => env('FCM_CREDENTIALS_FILE'),
    ],

    'http' => [
        'timeout' => (int) env('FCM_HTTP_TIMEOUT', 10),
    ],

    'queue' => [
        'enabled' => (bool) env('PUSH_MANAGER_QUEUE_ENABLED', true),
        'connection' => env('PUSH_MANAGER_QUEUE_CONNECTION'),
        'name' => env('PUSH_MANAGER_QUEUE', 'push-notifications'),
    ],
];
