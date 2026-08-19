<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Frontend SIPBPNT
    |--------------------------------------------------------------------------
    */

    'frontend_url' => env(
        'FRONTEND_URL',
        'http://localhost:5173'
    ),

    /*
    |--------------------------------------------------------------------------
    | Initial Development Users
    |--------------------------------------------------------------------------
    */

    'initial_user_password' => env(
        'INITIAL_USER_PASSWORD'
    ),

    'allow_initial_user_seeding' =>
        filter_var(
            env(
                'SIPBPNT_ALLOW_INITIAL_USER_SEEDING',
                false
            ),
            FILTER_VALIDATE_BOOL
        ),

    /*
    |--------------------------------------------------------------------------
    | Identity Hash Key
    |--------------------------------------------------------------------------
    */

    'identity_hash_key' => env(
        'SIPBPNT_IDENTITY_HASH_KEY',
        env('APP_KEY')
    ),

    /*
    |--------------------------------------------------------------------------
    | BNBA Import
    |--------------------------------------------------------------------------
    */

    'bnba_import' => [
        /*
        |--------------------------------------------------------------------------
        | Technical Limits
        |--------------------------------------------------------------------------
        */

        'max_file_kb' => (int) env(
            'SIPBPNT_BNBA_MAX_FILE_KB',
            10240
        ),

        'max_rows' => (int) env(
            'SIPBPNT_BNBA_MAX_ROWS',
            20000
        ),

        'max_columns' => (int) env(
            'SIPBPNT_BNBA_MAX_COLUMNS',
            64
        ),

        'max_worksheets' => (int) env(
            'SIPBPNT_BNBA_MAX_WORKSHEETS',
            5
        ),

        'chunk_size' => (int) env(
            'SIPBPNT_BNBA_CHUNK_SIZE',
            500
        ),

        /*
        |--------------------------------------------------------------------------
        | Retention
        |--------------------------------------------------------------------------
        |
        | Belum ada kebijakan resmi retensi dari Dinsos/Diskominfo.
        | Karena itu source file dan staging rows tidak dihapus otomatis.
        |
        */

        'retention' => [
            'raw_file'
                => 'retain_until_policy_approved',

            'staging_rows'
                => 'retain_until_policy_approved',
        ],
    ],
];