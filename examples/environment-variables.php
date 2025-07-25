<?php
/**
 * Example: Working with Environment Variables
 * 
 * This example demonstrates how to encrypt values for use in .env files
 * and how Laravel Configrypt automatically handles decryption when
 * environment variables are accessed.
 * 
 * Usage:
 * - Run: php examples/environment-variables.php
 * - Creates example .env entries
 * - Shows how auto-decryption works
 * 
 * Requirements:
 * - Laravel Configrypt package
 * - CONFIGRYPT_KEY environment variable set
 */

require_once __DIR__ . '/../vendor/autoload.php';

use LaravelConfigrypt\Services\ConfigryptService;

echo "=== Laravel Configrypt Environment Variables Example ===\n\n";

// Setup encryption service
$encryptionKey = $_ENV['CONFIGRYPT_KEY'] ?? $_ENV['APP_KEY'] ?? 'example-key-32-characters-long--';
$service = new ConfigryptService(
    key: $encryptionKey,
    prefix: 'ENC:',
    cipher: 'AES-256-CBC'
);

echo "1. Generating encrypted values for .env file:\n";
echo "=============================================\n";

// Common secrets that should be encrypted
$secrets = [
    'DB_PASSWORD' => 'super-secret-database-password',
    'MAIL_PASSWORD' => 'smtp-password-here',
    'API_KEY_STRIPE' => 'sk_live_1234567890abcdef',
    'API_KEY_MAILGUN' => 'key-1234567890abcdef',
    'JWT_SECRET' => 'your-jwt-signing-secret-key',
    'OAUTH_CLIENT_SECRET' => 'oauth2-client-secret-value',
    'ENCRYPTION_KEY' => 'another-32-character-key-for-app',
    'WEBHOOK_SECRET' => 'webhook-validation-secret',
];

echo "Secrets to encrypt:\n";
foreach ($secrets as $key => $value) {
    $encrypted = $service->encrypt($value);
    echo "{$key}={$encrypted}\n";
}

echo "\n2. Example .env file content:\n";
echo "=============================\n";

echo "# Laravel Application Configuration\n";
echo "APP_NAME=\"My Laravel App\"\n";
echo "APP_ENV=production\n";
echo "APP_KEY=base64:your-laravel-app-key-here\n";
echo "APP_DEBUG=false\n";
echo "APP_URL=https://example.com\n\n";

echo "# Configrypt Configuration\n";
echo "CONFIGRYPT_KEY=your-dedicated-32-character-key--\n";
echo "CONFIGRYPT_PREFIX=ENC:\n";
echo "CONFIGRYPT_CIPHER=AES-256-CBC\n";
echo "CONFIGRYPT_AUTO_DECRYPT=true\n\n";

echo "# Database Configuration (encrypted password)\n";
echo "DB_CONNECTION=mysql\n";
echo "DB_HOST=127.0.0.1\n";
echo "DB_PORT=3306\n";
echo "DB_DATABASE=laravel\n";
echo "DB_USERNAME=laravel_user\n";
$dbPasswordEncrypted = $service->encrypt('super-secret-database-password');
echo "DB_PASSWORD={$dbPasswordEncrypted}\n\n";

echo "# Mail Configuration (encrypted password)\n";
echo "MAIL_MAILER=smtp\n";
echo "MAIL_HOST=smtp.mailgun.org\n";
echo "MAIL_PORT=587\n";
echo "MAIL_USERNAME=postmaster@mg.example.com\n";
$mailPasswordEncrypted = $service->encrypt('smtp-password-here');
echo "MAIL_PASSWORD={$mailPasswordEncrypted}\n";
echo "MAIL_ENCRYPTION=tls\n\n";

echo "# Third-party API Keys (all encrypted)\n";
$stripeKeyEncrypted = $service->encrypt('sk_live_1234567890abcdef');
echo "STRIPE_SECRET={$stripeKeyEncrypted}\n";
$mailgunKeyEncrypted = $service->encrypt('key-1234567890abcdef');
echo "MAILGUN_SECRET={$mailgunKeyEncrypted}\n\n";

echo "3. How auto-decryption works:\n";
echo "============================\n";

// Simulate environment variables being loaded
$_ENV['DB_PASSWORD'] = $dbPasswordEncrypted;
$_ENV['MAIL_PASSWORD'] = $mailPasswordEncrypted;
$_ENV['STRIPE_SECRET'] = $stripeKeyEncrypted;

echo "Before auto-decryption:\n";
echo "DB_PASSWORD = {$_ENV['DB_PASSWORD']}\n";
echo "MAIL_PASSWORD = {$_ENV['MAIL_PASSWORD']}\n";
echo "STRIPE_SECRET = {$_ENV['STRIPE_SECRET']}\n\n";

// Simulate the auto-decryption process (normally done by service provider)
echo "Simulating auto-decryption process...\n";
$prefix = $service->getPrefix();
foreach ($_ENV as $key => $value) {
    if (is_string($value) && str_starts_with($value, $prefix)) {
        try {
            $decrypted = $service->decrypt($value);
            $_ENV[$key] = $decrypted;
            putenv("{$key}={$decrypted}");
            echo "✓ Decrypted {$key}\n";
        } catch (Exception $e) {
            echo "✗ Failed to decrypt {$key}: " . $e->getMessage() . "\n";
        }
    }
}

echo "\nAfter auto-decryption:\n";
echo "DB_PASSWORD = {$_ENV['DB_PASSWORD']}\n";
echo "MAIL_PASSWORD = {$_ENV['MAIL_PASSWORD']}\n";
echo "STRIPE_SECRET = {$_ENV['STRIPE_SECRET']}\n\n";

echo "4. Usage in Laravel configuration files:\n";
echo "========================================\n";

echo "// config/database.php\n";
echo "'mysql' => [\n";
echo "    'driver' => 'mysql',\n";
echo "    'host' => env('DB_HOST', '127.0.0.1'),\n";
echo "    'port' => env('DB_PORT', '3306'),\n";
echo "    'database' => env('DB_DATABASE', 'forge'),\n";
echo "    'username' => env('DB_USERNAME', 'forge'),\n";
echo "    'password' => env('DB_PASSWORD', ''), // Automatically decrypted!\n";
echo "],\n\n";

echo "// config/mail.php\n";
echo "'smtp' => [\n";
echo "    'transport' => 'smtp',\n";
echo "    'host' => env('MAIL_HOST'),\n";
echo "    'port' => env('MAIL_PORT', 587),\n";
echo "    'username' => env('MAIL_USERNAME'),\n";
echo "    'password' => env('MAIL_PASSWORD'), // Automatically decrypted!\n";
echo "],\n\n";

echo "// config/services.php\n";
echo "'stripe' => [\n";
echo "    'model' => App\\Models\\User::class,\n";
echo "    'key' => env('STRIPE_KEY'),\n";
echo "    'secret' => env('STRIPE_SECRET'), // Automatically decrypted!\n";
echo "],\n\n";

echo "5. Different environment configurations:\n";
echo "=======================================\n";

echo "Development (.env.local):\n";
echo "CONFIGRYPT_KEY=dev-key-32-characters-long-----\n";
echo "DB_PASSWORD=ENC:dev-encrypted-password\n";
echo "STRIPE_SECRET=ENC:dev-stripe-test-key\n\n";

echo "Staging (.env.staging):\n";
echo "CONFIGRYPT_KEY=staging-key-32-characters-long-\n";
echo "DB_PASSWORD=ENC:staging-encrypted-password\n";
echo "STRIPE_SECRET=ENC:staging-stripe-test-key\n\n";

echo "Production (.env.production):\n";
echo "CONFIGRYPT_KEY=prod-key-32-characters-long----\n";
echo "DB_PASSWORD=ENC:prod-encrypted-password\n";
echo "STRIPE_SECRET=ENC:prod-stripe-live-key\n\n";

echo "6. Validation and troubleshooting:\n";
echo "=================================\n";

// Function to validate encrypted environment variables
function validateEncryptedEnvVars($service, $envVars) {
    $results = [];
    $prefix = $service->getPrefix();
    
    foreach ($envVars as $key => $value) {
        if (is_string($value) && str_starts_with($value, $prefix)) {
            try {
                $decrypted = $service->decrypt($value);
                $results[$key] = [
                    'status' => 'valid',
                    'decrypted_length' => strlen($decrypted),
                    'error' => null
                ];
            } catch (Exception $e) {
                $results[$key] = [
                    'status' => 'invalid',
                    'decrypted_length' => 0,
                    'error' => $e->getMessage()
                ];
            }
        } else {
            $results[$key] = [
                'status' => 'not_encrypted',
                'decrypted_length' => strlen($value),
                'error' => null
            ];
        }
    }
    
    return $results;
}

// Test validation with our example environment variables
$testEnvVars = [
    'DB_PASSWORD' => $dbPasswordEncrypted,
    'MAIL_PASSWORD' => $mailPasswordEncrypted,
    'STRIPE_SECRET' => $stripeKeyEncrypted,
    'PLAIN_VALUE' => 'not-encrypted',
    'INVALID_ENC' => 'ENC:invalid-encrypted-data',
];

echo "Validating environment variables:\n";
$validation = validateEncryptedEnvVars($service, $testEnvVars);

foreach ($validation as $key => $result) {
    $status = $result['status'];
    echo "{$key}: {$status}";
    
    if ($status === 'valid') {
        echo " (length: {$result['decrypted_length']})";
    } elseif ($status === 'invalid') {
        echo " - Error: {$result['error']}";
    } elseif ($status === 'not_encrypted') {
        echo " (plain text, length: {$result['decrypted_length']})";
    }
    
    echo "\n";
}

echo "\n7. Best practices for .env files:\n";
echo "=================================\n";

echo "✅ DO:\n";
echo "- Use CONFIGRYPT_KEY separate from APP_KEY\n";
echo "- Encrypt all passwords, API keys, and secrets\n";
echo "- Use different encryption keys per environment\n";
echo "- Keep .env files out of version control\n";
echo "- Use clear, descriptive variable names\n";
echo "- Document which variables are encrypted\n\n";

echo "❌ DON'T:\n";
echo "- Commit .env files to git\n";
echo "- Use the same encryption key across environments\n";
echo "- Encrypt non-sensitive configuration values\n";
echo "- Share encryption keys in plain text\n";
echo "- Use weak or predictable encryption keys\n\n";

echo "=== Example Complete ===\n";
echo "Next: Try the database-config.php example for database-specific encryption patterns\n";