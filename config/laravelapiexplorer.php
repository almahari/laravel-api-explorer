<?php

return [

    'enabled' => true,

    'route' => 'api-explorer',

    'match' => 'api/*',

    'ignore' => [
        '/'
    ],

    'postman' => [
        'disk' => 'public',
        'collection_title' => 'UFS API v2',
        'auth_middleware' => 'auth.jwt'
    ]
];
