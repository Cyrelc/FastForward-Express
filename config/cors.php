<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS)
    |--------------------------------------------------------------------------
    |
    | Only the public form endpoints are meant to be called from another site
    | (a marketing site on its own host). Pages served by this app are
    | same-origin and need no entry here. List the marketing site's origins,
    | comma separated, in CORS_ALLOWED_ORIGINS, e.g.
    | CORS_ALLOWED_ORIGINS=https://fastforwardexpress.ca,https://www.fastforwardexpress.ca
    |
    */

    'paths' => ['api/public/*'],

    'allowed_methods' => ['POST'],

    'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', ''))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Content-Type', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
