<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Configure the source and target database connections for data synchronization.
    |
    */
    'source_connection' => env('DOPPELGANGER_SOURCE_CONNECTION', 'source'),
    'target_connection' => env('DOPPELGANGER_TARGET_CONNECTION', 'target'),

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Configure batch size and timeout settings for sync operations.
    |
    */
    'batch_size' => env('DOPPELGANGER_BATCH_SIZE', 100),
    'timeout' => env('DOPPELGANGER_TIMEOUT', 600),

    /*
    |--------------------------------------------------------------------------
    | Safe Mode Settings
    |--------------------------------------------------------------------------
    |
    | Configure the behavior of safe mode operations including seed generation.
    |
    */
    'safe_mode' => [
        'enabled' => env('DOPPELGANGER_SAFE_MODE', false),
        'seed_path' => storage_path('doppelganger/seeds'),
        'cleanup_after' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    |
    | Define which models can be synced and their relationships.
    | Example:
    |
    | 'models' => [
    |     App\Models\Organization::class => [
    |         'relationships' => [
    |             'resources' => [
    |                 'model' => App\Models\Resource::class,
    |                 'type' => 'hasMany'
    |             ]
    |         ]
    |     ]
    | ]
    |
    */
    'models' => [],

    /*
    |--------------------------------------------------------------------------
    | Excluded Columns
    |--------------------------------------------------------------------------
    |
    | Specify columns that should be excluded from synchronization.
    |
    */
    'exclude_columns' => [
        'created_at',
        'updated_at',
        'deleted_at',
    ],
];
