<?php
return [
    'paths' => [
        'api/*',
        'login',
        'logout',
        'sanctum/csrf-cookie',
        'register', // if you're using registration
        // add any other auth routes here if needed
    ],

    'allowed_origins' => ['http://localhost:5173'], // Your React app

    'allowed_methods' => ['*'],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true, // needed for cookies like XSRF-TOKEN
];
