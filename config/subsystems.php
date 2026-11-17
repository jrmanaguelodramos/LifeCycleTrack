<?php
return [
    'central' => [
        'base_url' => getenv('CENTRAL_API_BASE_URL') ?: '',
        'token' => getenv('CENTRAL_API_TOKEN') ?: '',
    ],
];
