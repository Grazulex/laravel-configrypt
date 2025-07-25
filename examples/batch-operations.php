<?php

/**
 * Example: Batch Operations and Bulk Management
 *
 * This example demonstrates how to perform batch encryption/decryption operations,
 * bulk environment file processing, and automated secret management with Laravel Configrypt.
 *
 * Usage:
 * - Run: php examples/batch-operations.php
 * - Shows bulk encryption/decryption patterns
 * - Demonstrates automated environment file processing
 *
 * Requirements:
 * - Laravel Configrypt package
 * - CONFIGRYPT_KEY environment variable set
 */

require_once __DIR__ . '/../vendor/autoload.php';

use LaravelConfigrypt\Services\ConfigryptService;

echo "=== Laravel Configrypt Batch Operations Example ===\n\n";

// Setup encryption service
$encryptionKey = $_ENV['CONFIGRYPT_KEY'] ?? $_ENV['APP_KEY'] ?? 'example-key-32-characters-long--';
$service = new ConfigryptService(
    key: $encryptionKey,
    prefix: 'ENC:',
    cipher: 'AES-256-CBC'
);

echo "1. Bulk Encryption of Configuration Values:\n";
echo "==========================================\n";

// Large set of configuration values to encrypt
$configurationValues = [
    // Database configurations
    'DB_PASSWORD' => 'primary-database-password',
    'DB_READONLY_PASSWORD' => 'readonly-database-password',
    'DB_ANALYTICS_PASSWORD' => 'analytics-database-password',

    // API keys and secrets
    'STRIPE_SECRET_KEY' => 'sk_live_1234567890abcdefghijklmnopqrstuvwxyz',
    'STRIPE_WEBHOOK_SECRET' => 'whsec_1234567890abcdefghijklmnopqrstuvwxyz',
    'PAYPAL_CLIENT_SECRET' => 'paypal-client-secret-here',
    'MAILGUN_API_KEY' => 'key-1234567890abcdef1234567890abcdef',
    'SENDGRID_API_KEY' => 'SG.1234567890abcdef.1234567890abcdefghijklmnopqrstuvwxyz',
    'TWILIO_AUTH_TOKEN' => 'twilio-auth-token-here',
    'PUSHER_APP_SECRET' => 'pusher-app-secret-here',

    // Cloud service credentials
    'AWS_SECRET_ACCESS_KEY' => 'AWS-SECRET-ACCESS-KEY-1234567890ABCDEF',
    'GOOGLE_CLIENT_SECRET' => 'google-oauth-client-secret-here',
    'AZURE_CLIENT_SECRET' => 'azure-ad-client-secret-here',

    // Application secrets
    'JWT_SECRET' => 'your-jwt-signing-secret-key-here',
    'SESSION_ENCRYPT_KEY' => 'session-encryption-key-here',
    'WEBHOOK_SECRET' => 'webhook-validation-secret-here',

    // Third-party integrations
    'SLACK_WEBHOOK_URL' => 'https://hooks.slack.com/services/SECRET/PATH/HERE',
    'DISCORD_WEBHOOK_URL' => 'https://discord.com/api/webhooks/SECRET/PATH',
    'ALGOLIA_ADMIN_KEY' => 'algolia-admin-api-key-here',
];

echo 'Processing ' . count($configurationValues) . " configuration values...\n\n";

// Batch encryption function
function batchEncrypt($service, $values)
{
    $encrypted = [];
    $errors = [];
    $startTime = microtime(true);

    foreach ($values as $key => $value) {
        try {
            $encrypted[$key] = $service->encrypt($value);
            echo "✓ Encrypted {$key}\n";
        } catch (Exception $e) {
            $errors[$key] = $e->getMessage();
            echo "✗ Failed to encrypt {$key}: " . $e->getMessage() . "\n";
        }
    }

    $duration = microtime(true) - $startTime;

    return [
        'encrypted' => $encrypted,
        'errors' => $errors,
        'duration' => $duration,
        'processed' => count($values),
        'successful' => count($encrypted),
        'failed' => count($errors),
    ];
}

$encryptionResult = batchEncrypt($service, $configurationValues);

echo "\nBatch encryption completed:\n";
echo "- Processed: {$encryptionResult['processed']} values\n";
echo "- Successful: {$encryptionResult['successful']} values\n";
echo "- Failed: {$encryptionResult['failed']} values\n";
echo '- Duration: ' . round($encryptionResult['duration'] * 1000, 2) . "ms\n";
echo '- Average per value: ' . round(($encryptionResult['duration'] / $encryptionResult['processed']) * 1000, 2) . "ms\n\n";

echo "2. Generating Environment File from Encrypted Values:\n";
echo "====================================================\n";

function generateEnvironmentFile($encryptedValues, $template = [])
{
    $envContent = [];

    // Add header
    $envContent[] = '# Laravel Application Configuration';
    $envContent[] = '# Generated on ' . date('Y-m-d H:i:s');
    $envContent[] = '';

    // Add template values
    foreach ($template as $section => $values) {
        $envContent[] = "# {$section}";
        foreach ($values as $key => $value) {
            $envContent[] = "{$key}={$value}";
        }
        $envContent[] = '';
    }

    // Add encrypted values in categories
    $categories = [
        'Database Configuration' => ['DB_'],
        'API Keys and Secrets' => ['STRIPE_', 'PAYPAL_', 'MAILGUN_', 'SENDGRID_', 'TWILIO_', 'PUSHER_'],
        'Cloud Services' => ['AWS_', 'GOOGLE_', 'AZURE_'],
        'Application Secrets' => ['JWT_', 'SESSION_', 'WEBHOOK_'],
        'Third-party Integrations' => ['SLACK_', 'DISCORD_', 'ALGOLIA_'],
    ];

    foreach ($categories as $category => $prefixes) {
        $categoryValues = [];

        foreach ($encryptedValues as $key => $value) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($key, $prefix)) {
                    $categoryValues[$key] = $value;
                    break;
                }
            }
        }

        if (! empty($categoryValues)) {
            $envContent[] = "# {$category}";
            foreach ($categoryValues as $key => $value) {
                $envContent[] = "{$key}={$value}";
            }
            $envContent[] = '';
        }
    }

    return implode("\n", $envContent);
}

// Template for non-encrypted values
$envTemplate = [
    'Application Settings' => [
        'APP_NAME' => '"My Laravel App"',
        'APP_ENV' => 'production',
        'APP_KEY' => 'base64:your-laravel-app-key-here',
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://example.com',
    ],
    'Configrypt Settings' => [
        'CONFIGRYPT_KEY' => 'your-32-character-encryption-key--',
        'CONFIGRYPT_PREFIX' => 'ENC:',
        'CONFIGRYPT_AUTO_DECRYPT' => 'true',
    ],
    'Database Connection' => [
        'DB_CONNECTION' => 'mysql',
        'DB_HOST' => '127.0.0.1',
        'DB_PORT' => '3306',
        'DB_DATABASE' => 'laravel',
        'DB_USERNAME' => 'laravel_user',
    ],
];

$envFileContent = generateEnvironmentFile($encryptionResult['encrypted'], $envTemplate);

echo "Generated .env file content:\n";
echo str_repeat('=', 50) . "\n";
echo $envFileContent;
echo str_repeat('=', 50) . "\n\n";

echo "3. Batch Validation of Encrypted Values:\n";
echo "=======================================\n";

function batchValidate($service, $encryptedValues)
{
    $results = [];
    $startTime = microtime(true);

    foreach ($encryptedValues as $key => $encryptedValue) {
        try {
            $decrypted = $service->decrypt($encryptedValue);
            $results[$key] = [
                'status' => 'valid',
                'length' => strlen($decrypted),
                'preview' => substr($decrypted, 0, 10) . '...',
                'is_encrypted' => $service->isEncrypted($encryptedValue),
            ];
        } catch (Exception $e) {
            $results[$key] = [
                'status' => 'invalid',
                'error' => $e->getMessage(),
                'is_encrypted' => $service->isEncrypted($encryptedValue),
            ];
        }
    }

    $duration = microtime(true) - $startTime;

    return [
        'results' => $results,
        'duration' => $duration,
        'total' => count($encryptedValues),
        'valid' => count(array_filter($results, fn ($r) => $r['status'] === 'valid')),
        'invalid' => count(array_filter($results, fn ($r) => $r['status'] === 'invalid')),
    ];
}

$validationResult = batchValidate($service, $encryptionResult['encrypted']);

echo "Validation results:\n";
foreach ($validationResult['results'] as $key => $result) {
    $status = $result['status'];
    echo "{$key}: {$status}";

    if ($status === 'valid') {
        echo " (length: {$result['length']}, preview: {$result['preview']})";
    } else {
        echo " - Error: {$result['error']}";
    }
    echo "\n";
}

echo "\nValidation summary:\n";
echo "- Total: {$validationResult['total']} values\n";
echo "- Valid: {$validationResult['valid']} values\n";
echo "- Invalid: {$validationResult['invalid']} values\n";
echo '- Duration: ' . round($validationResult['duration'] * 1000, 2) . "ms\n\n";

echo "4. Environment File Migration (Plain to Encrypted):\n";
echo "==================================================\n";

// Simulate an existing .env file with plain text secrets
$existingEnvContent = <<<'ENV'
# Existing .env file with plain text secrets
APP_NAME="My Laravel App"
APP_ENV=production
APP_DEBUG=false

# Database (plain text password)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel_user
DB_PASSWORD=plain-text-password

# API Keys (plain text)
STRIPE_SECRET=sk_live_plain_text_key
MAILGUN_SECRET=key-plain-text-secret

# Non-sensitive values (keep as-is)
LOG_CHANNEL=stack
LOG_LEVEL=error
ENV;

echo "Original .env file:\n";
echo str_repeat('-', 30) . "\n";
echo $existingEnvContent . "\n";
echo str_repeat('-', 30) . "\n\n";

function migrateEnvironmentFile($content, $service, $secretPatterns = [])
{
    if (empty($secretPatterns)) {
        $secretPatterns = [
            '/.*PASSWORD.*/',
            '/.*SECRET.*/',
            '/.*KEY.*/',
            '/.*TOKEN.*/',
            '/.*CREDENTIAL.*/',
        ];
    }

    $lines = explode("\n", $content);
    $migratedLines = [];
    $encrypted = [];

    foreach ($lines as $line) {
        $trimmedLine = trim($line);

        // Skip comments and empty lines
        if (empty($trimmedLine) || str_starts_with($trimmedLine, '#')) {
            $migratedLines[] = $line;

            continue;
        }

        // Check if it's an environment variable
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, '"\'');

            // Check if this key should be encrypted
            $shouldEncrypt = false;
            foreach ($secretPatterns as $pattern) {
                if (preg_match($pattern, strtoupper($key))) {
                    $shouldEncrypt = true;
                    break;
                }
            }

            if ($shouldEncrypt && ! $service->isEncrypted($value)) {
                $encryptedValue = $service->encrypt($value);
                $migratedLines[] = "{$key}={$encryptedValue}";
                $encrypted[] = $key;
            } else {
                $migratedLines[] = $line;
            }
        } else {
            $migratedLines[] = $line;
        }
    }

    return [
        'content' => implode("\n", $migratedLines),
        'encrypted_keys' => $encrypted,
    ];
}

$migrationResult = migrateEnvironmentFile($existingEnvContent, $service);

echo "Migrated .env file:\n";
echo str_repeat('-', 30) . "\n";
echo $migrationResult['content'] . "\n";
echo str_repeat('-', 30) . "\n\n";

echo "Migration summary:\n";
echo '- Encrypted keys: ' . implode(', ', $migrationResult['encrypted_keys']) . "\n\n";

echo "5. Bulk Key Rotation:\n";
echo "====================\n";

// Simulate key rotation scenario
$oldKey = 'old-key-32-characters-long-----';
$newKey = 'new-key-32-characters-long-----';

$oldService = new ConfigryptService($oldKey, 'ENC:', 'AES-256-CBC');
$newService = new ConfigryptService($newKey, 'ENC:', 'AES-256-CBC');

function rotateKeys($oldService, $newService, $encryptedValues)
{
    $rotated = [];
    $errors = [];
    $startTime = microtime(true);

    foreach ($encryptedValues as $key => $oldEncryptedValue) {
        try {
            // Decrypt with old key
            $plainText = $oldService->decrypt($oldEncryptedValue);

            // Encrypt with new key
            $newEncryptedValue = $newService->encrypt($plainText);

            $rotated[$key] = $newEncryptedValue;
            echo "✓ Rotated {$key}\n";
        } catch (Exception $e) {
            $errors[$key] = $e->getMessage();
            echo "✗ Failed to rotate {$key}: " . $e->getMessage() . "\n";
        }
    }

    $duration = microtime(true) - $startTime;

    return [
        'rotated' => $rotated,
        'errors' => $errors,
        'duration' => $duration,
        'total' => count($encryptedValues),
        'successful' => count($rotated),
        'failed' => count($errors),
    ];
}

// Create some test encrypted values with the old key
$testValues = [
    'DB_PASSWORD' => $oldService->encrypt('test-db-password'),
    'API_SECRET' => $oldService->encrypt('test-api-secret'),
    'JWT_SECRET' => $oldService->encrypt('test-jwt-secret'),
];

echo 'Rotating keys for ' . count($testValues) . " values...\n";
$rotationResult = rotateKeys($oldService, $newService, $testValues);

echo "\nKey rotation summary:\n";
echo "- Total: {$rotationResult['total']} values\n";
echo "- Successful: {$rotationResult['successful']} values\n";
echo "- Failed: {$rotationResult['failed']} values\n";
echo '- Duration: ' . round($rotationResult['duration'] * 1000, 2) . "ms\n\n";

// Verify rotation worked
echo "Verifying rotation:\n";
foreach ($rotationResult['rotated'] as $key => $newEncryptedValue) {
    try {
        $decrypted = $newService->decrypt($newEncryptedValue);
        echo "✓ {$key}: Successfully decrypts with new key\n";
    } catch (Exception $e) {
        echo "✗ {$key}: Failed to decrypt with new key: " . $e->getMessage() . "\n";
    }
}

echo "\n6. Performance Analysis:\n";
echo "=======================\n";

function performanceTest($service, $iterations = 100)
{
    $testValue = 'performance-test-value-' . str_repeat('x', 50);

    // Encryption performance
    $encryptStart = microtime(true);
    $encrypted = [];
    for ($i = 0; $i < $iterations; $i++) {
        $encrypted[] = $service->encrypt($testValue . $i);
    }
    $encryptDuration = microtime(true) - $encryptStart;

    // Decryption performance
    $decryptStart = microtime(true);
    $decrypted = [];
    foreach ($encrypted as $encValue) {
        $decrypted[] = $service->decrypt($encValue);
    }
    $decryptDuration = microtime(true) - $decryptStart;

    return [
        'iterations' => $iterations,
        'encrypt_total' => $encryptDuration,
        'encrypt_avg' => $encryptDuration / $iterations,
        'decrypt_total' => $decryptDuration,
        'decrypt_avg' => $decryptDuration / $iterations,
        'total_time' => $encryptDuration + $decryptDuration,
    ];
}

$perfResult = performanceTest($service, 100);

echo "Performance test results (100 iterations):\n";
echo '- Encryption total: ' . round($perfResult['encrypt_total'] * 1000, 2) . "ms\n";
echo '- Encryption average: ' . round($perfResult['encrypt_avg'] * 1000, 4) . "ms per operation\n";
echo '- Decryption total: ' . round($perfResult['decrypt_total'] * 1000, 2) . "ms\n";
echo '- Decryption average: ' . round($perfResult['decrypt_avg'] * 1000, 4) . "ms per operation\n";
echo '- Total time: ' . round($perfResult['total_time'] * 1000, 2) . "ms\n";
echo '- Operations per second: ' . round($perfResult['iterations'] / $perfResult['total_time']) . "\n\n";

echo "7. Automation Scripts:\n";
echo "=====================\n";

echo "Bash script for batch operations:\n";
echo str_repeat('-', 35) . "\n";
echo "#!/bin/bash\n";
echo "# batch-encrypt.sh - Encrypt multiple values at once\n\n";
echo "if [ \$# -eq 0 ]; then\n";
echo "    echo \"Usage: \$0 <value1> <value2> ...\"\n";
echo "    exit 1\n";
echo "fi\n\n";
echo "echo \"Encrypting \$# values...\"\n";
echo "for value in \"\$@\"; do\n";
echo "    encrypted=\$(php artisan configrypt:encrypt \"\$value\" | grep 'ENC:' | head -1)\n";
echo "    echo \"Value: \$value\"\n";
echo "    echo \"Encrypted: \$encrypted\"\n";
echo "    echo\n";
echo "done\n\n";

echo "PHP script for environment migration:\n";
echo str_repeat('-', 40) . "\n";
echo "<?php\n";
echo "// migrate-env.php - Migrate .env file from plain to encrypted\n\n";
echo "require_once 'vendor/autoload.php';\n\n";
echo "use LaravelConfigrypt\\Services\\ConfigryptService;\n\n";
echo "\$service = app(ConfigryptService::class);\n";
echo "\$envFile = '.env';\n\n";
echo "if (!file_exists(\$envFile)) {\n";
echo "    echo \"Error: .env file not found\\n\";\n";
echo "    exit(1);\n";
echo "}\n\n";
echo "\$content = file_get_contents(\$envFile);\n";
echo "\$migrationResult = migrateEnvironmentFile(\$content, \$service);\n\n";
echo "// Backup original\n";
echo "copy(\$envFile, \$envFile . '.backup.' . date('Y-m-d-H-i-s'));\n\n";
echo "// Write migrated content\n";
echo "file_put_contents(\$envFile, \$migrationResult['content']);\n\n";
echo "echo \"Migration complete. Encrypted keys: \" . implode(', ', \$migrationResult['encrypted_keys']) . \"\\n\";\n\n";

echo "=== Example Complete ===\n";
echo "Next: Check out the testing/ examples for test configurations and patterns\n";
