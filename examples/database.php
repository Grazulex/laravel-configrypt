<?php

/*
|--------------------------------------------------------------------------
| Example Database Configuration with Encrypted Passwords
|--------------------------------------------------------------------------
|
| This demonstrates how to use configrypt_value() in config files to handle
| encrypted values that work properly with Laravel's config:cache command.
|
| Unlike configrypt_env(), configrypt_value() creates a lazy configuration
| value that only decrypts when accessed, and maintains encryption when
| the configuration is cached.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Using configrypt_value() ensures that encrypted passwords remain
    | encrypted in the config cache, only decrypting when actually used.
    |
    */

    'connections' => [

        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            // This will decrypt automatically when accessed, but remain encrypted in cache
            'password' => configrypt_value(env('DB_PASSWORD'), ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', '6379'),
            // Redis password can also be encrypted
            'password' => configrypt_value(env('REDIS_PASSWORD'), null),
            'database' => 0,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    */

    'migrations' => 'migrations',

];
