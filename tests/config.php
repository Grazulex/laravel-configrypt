<?php

// Base test configuration
return [
    'app' => [
        'key' => 'base64:' . base64_encode('test-app-key-1234567890123456'),
        'debug' => true,
        'env' => 'testing',
    ],
    'configrypt' => [
        'key' => 'test-key-1234567890123456789012',
        'prefix' => 'ENC:',
        'cipher' => 'AES-256-CBC',
        'auto_decrypt' => true,
    ],
];
