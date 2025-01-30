<?php

return [
    'models' => [
        // Define syncable models and their relationships
    ],
    
    // Global settings
    'exclude_columns' => [
        'created_at',
        'updated_at',
        'deleted_at'
    ],
    
    'batch_size' => 100,
    'timeout' => 600,
    
    // Default connections
    'source_connection' => env('SYNC_SOURCE_CONNECTION', 'staging'),
    'target_connection' => env('SYNC_TARGET_CONNECTION', 'mysql'),
];
