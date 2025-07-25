<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used to encrypt and decrypt values in your .env file.
    | You can use a dedicated CONFIGRYPT_KEY or fallback to APP_KEY.
    | Make sure this key is 32 characters long for AES-256-CBC.
    |
    */

    'key' => env('CONFIGRYPT_KEY', env('APP_KEY')),

    /*
    |--------------------------------------------------------------------------
    | Encryption Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix is used to identify encrypted values in your .env file.
    | Only values starting with this prefix will be decrypted.
    |
    */

    'prefix' => env('CONFIGRYPT_PREFIX', 'ENC:'),

    /*
    |--------------------------------------------------------------------------
    | Cipher Method
    |--------------------------------------------------------------------------
    |
    | The cipher method used for encryption and decryption.
    | Supported: "AES-256-CBC", "AES-128-CBC"
    |
    */

    'cipher' => env('CONFIGRYPT_CIPHER', 'AES-256-CBC'),

    /*
    |--------------------------------------------------------------------------
    | Auto Decrypt
    |--------------------------------------------------------------------------
    |
    | When enabled, encrypted environment variables will be automatically
    | decrypted during the application bootstrap process. However, due to
    | Laravel's environment caching, env() will not return decrypted values.
    | Use configrypt_env() helper function instead for reliable decryption.
    |
    */

    'auto_decrypt' => env('CONFIGRYPT_AUTO_DECRYPT', false),

];
