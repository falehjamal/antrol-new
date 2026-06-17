<?php

return [
    'aplicares' => [
        'cons_id'      => env('BPJS_CONS_ID'),
        'secret_key'   => env('BPJS_SECRET_KEY'),
        'base_url'     => env('BPJS_BASE_URL'),
        'service_name' => env('BPJS_APLICARES_SERVICE_NAME'),
    ],
    'vclaim' => [
        'base_url'     => env('BPJS_BASE_URL'),
        'cons_id'      => env('BPJS_CONS_ID'),
        'secret_key'   => env('BPJS_SECRET_KEY'),
        'user_key'     => env('BPJS_VCLAIM_USER_KEY'),
        'service_name' => env('BPJS_VCLAIM_SERVICE_NAME'),
    ],
    'antrean' => [
        'base_url'     => env('BPJS_BASE_URL'),
        'cons_id'      => env('BPJS_CONS_ID'),
        'secret_key'   => env('BPJS_SECRET_KEY'),
        'user_key'     => env('BPJS_ANTREAN_USER_KEY'),
        'service_name' => env('BPJS_ANTREAN_SERVICE_NAME'),
    ],
];
