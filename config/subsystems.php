<?php
return [
    'utilities' => [
        'base_url' => 'http://192.168.1.11/utilities/api/v1',
        'token' => getenv('UTILITIES_API_TOKEN') ?: '',
    ],
    'maintenance' => [
        'base_url' => 'http://192.168.1.12/maintenance/api/v1',
        'token' => getenv('MAINTENANCE_API_TOKEN') ?: '',
    ],
];