<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database Optimization Settings for Large-Scale Operations
    |--------------------------------------------------------------------------
    |
    | These settings are optimized for large-scale data operations like
    | seeding millions of records. They should be used carefully in production.
    |
    */

    'mysql' => [
        /*
        |--------------------------------------------------------------------------
        | MySQL Performance Settings
        |--------------------------------------------------------------------------
        */
        'performance' => [
            // Increase buffer sizes for better performance
            'innodb_buffer_pool_size' => '1G',
            'innodb_log_file_size' => '256M',
            'innodb_log_buffer_size' => '64M',
            'innodb_flush_log_at_trx_commit' => 2,
            'innodb_flush_method' => 'O_DIRECT',
            
            // Query cache and optimization
            'query_cache_size' => '128M',
            'query_cache_type' => 1,
            'tmp_table_size' => '256M',
            'max_heap_table_size' => '256M',
            
            // Connection settings
            'max_connections' => 200,
            'wait_timeout' => 600,
            'interactive_timeout' => 600,
        ],

        /*
        |--------------------------------------------------------------------------
        | Laravel Database Configuration Overrides
        |--------------------------------------------------------------------------
        */
        'laravel_overrides' => [
            // Disable query logging for performance
            'logging' => false,
            
            // Increase timeout for long operations
            'options' => [
                PDO::ATTR_TIMEOUT => 300,
                PDO::MYSQL_ATTR_LOCAL_INFILE => true,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
            ],
            
            // Connection pooling
            'pool' => [
                'min' => 5,
                'max' => 20,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Seeder Performance Settings
    |--------------------------------------------------------------------------
    */
    'seeder' => [
        // Default batch sizes for different scenarios
        'batch_sizes' => [
            'small' => 100,      // For memory-constrained environments
            'medium' => 1000,    // Default balanced approach
            'large' => 5000,     // For high-performance systems
            'extreme' => 10000,  // For very high-performance systems
        ],

        // Memory management settings
        'memory' => [
            'gc_frequency' => 50,        // Garbage collection every N batches
            'memory_warning_threshold' => 0.8, // Warn when memory usage exceeds 80%
            'max_memory_usage' => '2G',  // Maximum memory usage before warning
        ],

        // Progress reporting settings
        'progress' => [
            'report_frequency' => 10,    // Report progress every N batches
            'show_memory_usage' => true,
            'show_performance_metrics' => true,
        ],

        // Error handling settings
        'error_handling' => [
            'continue_on_error' => true, // Continue seeding even if some batches fail
            'log_errors' => true,        // Log errors to Laravel log
            'max_errors' => 100,         // Maximum number of errors before stopping
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Integration Settings
    |--------------------------------------------------------------------------
    */
    'elasticsearch' => [
        // Scout settings for large datasets
        'scout' => [
            'batch_size' => 1000,        // Scout import batch size
            'queue_imports' => true,     // Use queues for imports
            'chunk_size' => 1000,        // Chunk size for Scout operations
        ],

        // Index settings for large datasets
        'index' => [
            'number_of_shards' => 1,     // Single shard for small datasets
            'number_of_replicas' => 0,   // No replicas for development
            'refresh_interval' => '30s',  // Refresh interval for better performance
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring Settings
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'enabled' => true,
        'metrics' => [
            'records_per_second' => true,
            'memory_usage' => true,
            'database_queries' => true,
            'elasticsearch_operations' => true,
        ],
        'alerts' => [
            'memory_threshold' => 0.9,   // Alert when memory usage exceeds 90%
            'time_threshold' => 3600,    // Alert if operation takes more than 1 hour
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup and Recovery Settings
    |--------------------------------------------------------------------------
    */
    'backup' => [
        'enabled' => true,
        'before_seeding' => true,        // Create backup before seeding
        'after_seeding' => false,        // Create backup after seeding
        'location' => storage_path('backups'),
        'retention' => 7,                // Keep backups for 7 days
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Settings
    |--------------------------------------------------------------------------
    */
    'validation' => [
        'enabled' => true,
        'check_data_integrity' => true,  // Verify data after seeding
        'sample_size' => 1000,           // Number of records to validate
        'timeout' => 300,                // Validation timeout in seconds
    ],
]; 