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
        'collection_title' => 'API Explorer',
        'auth_middleware' => 'auth'
    ]
];
