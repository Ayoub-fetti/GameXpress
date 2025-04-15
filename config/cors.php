<?php

    return [
        'paths' => ['api/*', 'sanctum/csrf-cookie'],
        'allowed_origins' => ['http://127.0.0.1:3000'], 
        'allowed_methods' => ['*'],
        'allowed_headers' => ['*'],
        'exposed_headers' => [],
        'max_age' => 0,
        'supports_credentials' => true, 
    ];
    //comment out the above line to disable CORS