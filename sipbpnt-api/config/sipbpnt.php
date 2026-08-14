<?php

declare(strict_types=1);

return [
    'frontend_url' => env(
        'FRONTEND_URL',
        'http://localhost:5173'
    ),

    'initial_password' => env(
        'INITIAL_ADMIN_PASSWORD'
    ),

    /*
    |--------------------------------------------------------------------------
    | Identity Hash Key
    |--------------------------------------------------------------------------
    |
    | Dipakai untuk HMAC NIK/NKK.
    | Production sebaiknya menggunakan secret
    | terpisah dari APP_KEY.
    |
    */
    'identity_hash_key' => env(
        'SIPBPNT_IDENTITY_HASH_KEY',
        env('APP_KEY')
    ),
];