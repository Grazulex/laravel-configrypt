<?php

/**
 * Example: Multi-Environment Configuration Management
 *
 * This example demonstrates how to manage different encryption configurations
 * for different environments (development, staging, production) with Laravel Configrypt.
 * Shows best practices for environment separation and key management.
 *
 * Usage:
 * - Run: php examples/multi-environment.php
 * - Shows environment-specific configurations
 * - Demonstrates key rotation between environments
 *
 * Requirements:
 * - Laravel Configrypt package
 * - Different encryption keys per environment
 */

require_once __DIR__ . '/../vendor/autoload.php';

use LaravelConfigrypt\Services\ConfigryptService;

echo "=== Laravel Configrypt Multi-Environment Example ===\n\n";

echo "1. Environment-Specific Encryption Keys:\n";
echo "=======================================\n";

// Different encryption keys for different environments
$environmentKeys = [
    'development' => 'dev-key-32-characters-long-----',
    'staging' => 'staging-key-32-characters-long-',
    'production' => 'prod-key-32-characters-long----',
    'testing' => 'test-key-32-characters-long----',
];

// Common secrets that need to be encrypted differently per environment
$secrets = [
    'database_password' => [
        'development' => 'dev-db-password',
        'staging' => 'staging-db-password',
        'production' => 'super-secure-prod-db-password',
        'testing' => 'test-db-password',
    ],
    'api_key' => [
        'development' => 'dev-api-key-12345',
        'staging' => 'staging-api-key-67890',
        'production' => 'prod-api-key-abcdef',
        'testing' => 'test-api-key-testing',
    ],
    'jwt_secret' => [
        'development' => 'dev-jwt-secret',
        'staging' => 'staging-jwt-secret',
        'production' => 'production-jwt-secret-very-secure',
        'testing' => 'test-jwt-secret',
    ],
];

echo "Environment keys configured:\n";
foreach ($environmentKeys as $env => $key) {
    echo "- {$env}: {$key}\n";
}

echo "\n2. Encrypting Secrets for Each Environment:\n";
echo "==========================================\n";

$encryptedSecrets = [];

foreach ($environmentKeys as $environment => $key) {
    echo "Environment: {$environment}\n";
    echo str_repeat('-', strlen($environment) + 13) . "\n";

    $service = new ConfigryptService(
        key: $key,
        prefix: 'ENC:',
        cipher: 'AES-256-CBC'
    );

    $encryptedSecrets[$environment] = [];

    foreach ($secrets as $secretType => $environmentSecrets) {
        $secretValue = $environmentSecrets[$environment];
        $encrypted = $service->encrypt($secretValue);
        $encryptedSecrets[$environment][$secretType] = $encrypted;

        $varName = strtoupper($secretType);
        echo "{$varName}={$encrypted}\n";
    }
    echo "\n";
}

echo "3. Environment Configuration Files:\n";
echo "=================================\n";

foreach ($environmentKeys as $environment => $key) {
    echo ".env.{$environment}:\n";
    echo str_repeat('-', strlen($environment) + 5) . "\n";

    echo "# Environment: {$environment}\n";
    echo "APP_ENV={$environment}\n";
    echo 'APP_DEBUG=' . ($environment === 'production' ? 'false' : 'true') . "\n";
    echo "CONFIGRYPT_KEY={$key}\n";
    echo "CONFIGRYPT_PREFIX=ENC:\n";
    echo "CONFIGRYPT_AUTO_DECRYPT=true\n\n";

    echo "# Database configuration\n";
    echo "DB_CONNECTION=mysql\n";
    echo 'DB_HOST=' . ($environment === 'production' ? 'prod-mysql.example.com' : 'dev-mysql.example.com') . "\n";
    echo "DB_PORT=3306\n";
    echo "DB_DATABASE=laravel_{$environment}\n";
    echo "DB_USERNAME=laravel_{$environment}_user\n";
    echo "DATABASE_PASSWORD={$encryptedSecrets[$environment]['database_password']}\n\n";

    echo "# API configuration\n";
    echo "API_KEY={$encryptedSecrets[$environment]['api_key']}\n";
    echo "JWT_SECRET={$encryptedSecrets[$environment]['jwt_secret']}\n\n";

    if ($environment === 'production') {
        echo "# Production-specific settings\n";
        echo "LOG_LEVEL=error\n";
        echo "SESSION_SECURE_COOKIE=true\n";
        echo "FORCE_HTTPS=true\n";
    } elseif ($environment === 'development') {
        echo "# Development-specific settings\n";
        echo "LOG_LEVEL=debug\n";
        echo "TELESCOPE_ENABLED=true\n";
        echo "DEBUGBAR_ENABLED=true\n";
    } elseif ($environment === 'testing') {
        echo "# Testing-specific settings\n";
        echo "DB_CONNECTION=sqlite\n";
        echo "DB_DATABASE=:memory:\n";
        echo "CACHE_DRIVER=array\n";
        echo "SESSION_DRIVER=array\n";
        echo "QUEUE_CONNECTION=sync\n";
    }

    echo "\n" . str_repeat('=', 50) . "\n\n";
}

echo "4. Environment Switching and Validation:\n";
echo "=======================================\n";

function validateEnvironmentConfiguration($environment, $key, $encryptedSecrets)
{
    echo "Validating {$environment} environment:\n";

    try {
        $service = new ConfigryptService(
            key: $key,
            prefix: 'ENC:',
            cipher: 'AES-256-CBC'
        );

        $results = [];

        foreach ($encryptedSecrets as $secretType => $encryptedValue) {
            try {
                $decrypted = $service->decrypt($encryptedValue);
                $results[$secretType] = [
                    'status' => 'success',
                    'length' => strlen($decrypted),
                    'preview' => substr($decrypted, 0, 10) . '...',
                ];
            } catch (Exception $e) {
                $results[$secretType] = [
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        foreach ($results as $secretType => $result) {
            $status = $result['status'];
            echo "  {$secretType}: {$status}";

            if ($status === 'success') {
                echo " (length: {$result['length']}, preview: {$result['preview']})";
            } else {
                echo " - {$result['error']}";
            }
            echo "\n";
        }

        echo "  ✓ Environment configuration valid\n\n";

        return true;
    } catch (Exception $e) {
        echo '  ✗ Environment configuration failed: ' . $e->getMessage() . "\n\n";

        return false;
    }
}

// Validate each environment
foreach ($environmentKeys as $environment => $key) {
    validateEnvironmentConfiguration($environment, $key, $encryptedSecrets[$environment]);
}

echo "5. Key Rotation Between Environments:\n";
echo "====================================\n";

echo "Demonstrating key rotation from staging to production:\n\n";

// Simulate rotating from staging to production key
$stagingService = new ConfigryptService(
    key: $environmentKeys['staging'],
    prefix: 'ENC:',
    cipher: 'AES-256-CBC'
);

$productionService = new ConfigryptService(
    key: $environmentKeys['production'],
    prefix: 'ENC:',
    cipher: 'AES-256-CBC'
);

echo "Step 1: Decrypt with staging key\n";
$stagingSecret = $encryptedSecrets['staging']['database_password'];
echo "Staging encrypted: {$stagingSecret}\n";

$decryptedSecret = $stagingService->decrypt($stagingSecret);
echo "Decrypted value: {$decryptedSecret}\n\n";

echo "Step 2: Re-encrypt with production key\n";
$productionSecret = $productionService->encrypt($decryptedSecret);
echo "Production encrypted: {$productionSecret}\n\n";

echo "Step 3: Verify with production key\n";
$verifiedSecret = $productionService->decrypt($productionSecret);
echo "Verified decryption: {$verifiedSecret}\n";
echo 'Rotation successful: ' . ($decryptedSecret === $verifiedSecret ? 'Yes' : 'No') . "\n\n";

echo "6. Environment Deployment Strategies:\n";
echo "====================================\n";

echo "Strategy 1: Environment-specific .env files\n";
echo "-------------------------------------------\n";
echo "# Deploy different .env files per environment\n";
echo "cp .env.production .env  # On production servers\n";
echo "cp .env.staging .env     # On staging servers\n";
echo "cp .env.development .env # On development machines\n\n";

echo "Strategy 2: Infrastructure-managed secrets\n";
echo "------------------------------------------\n";
echo "# Use infrastructure tools to inject secrets\n";
echo "# AWS Secrets Manager, HashiCorp Vault, Kubernetes Secrets, etc.\n";
echo "# The encryption keys themselves should come from infrastructure\n\n";

echo "Strategy 3: CI/CD Pipeline Integration\n";
echo "--------------------------------------\n";
echo "# GitHub Actions / GitLab CI example:\n";
echo "- name: Setup environment\n";
echo "  run: |\n";
echo "    echo \"CONFIGRYPT_KEY=\${{ secrets.CONFIGRYPT_KEY_PROD }}\" >> .env\n";
echo "    echo \"DB_PASSWORD=\${{ secrets.DB_PASSWORD_ENCRYPTED }}\" >> .env\n\n";

echo "7. Best Practices for Multi-Environment:\n";
echo "=======================================\n";

echo "✅ DO:\n";
echo "- Use different encryption keys per environment\n";
echo "- Store keys securely in infrastructure/vault systems\n";
echo "- Automate environment-specific deployments\n";
echo "- Test key rotation procedures regularly\n";
echo "- Monitor configuration validation in each environment\n";
echo "- Document which secrets exist in which environments\n";
echo "- Use infrastructure-as-code for key management\n\n";

echo "❌ DON'T:\n";
echo "- Use the same encryption key across environments\n";
echo "- Store environment-specific keys in source control\n";
echo "- Manually copy/paste encrypted values between environments\n";
echo "- Mix production and development secrets\n";
echo "- Deploy wrong environment configurations\n";
echo "- Skip validation after environment deployments\n\n";

echo "8. Environment Configuration Validation Script:\n";
echo "==============================================\n";

echo "#!/bin/bash\n";
echo "# validate-environment.sh\n\n";
echo "ENVIRONMENT=\$1\n";
echo "if [ -z \"\$ENVIRONMENT\" ]; then\n";
echo "    echo \"Usage: \$0 <environment>\"\n";
echo "    exit 1\n";
echo "fi\n\n";
echo "echo \"Validating \$ENVIRONMENT environment...\"\n\n";
echo "# Load environment-specific configuration\n";
echo "if [ -f \".env.\$ENVIRONMENT\" ]; then\n";
echo "    source .env.\$ENVIRONMENT\n";
echo "else\n";
echo "    echo \"Error: .env.\$ENVIRONMENT not found\"\n";
echo "    exit 1\n";
echo "fi\n\n";
echo "# Validate encryption key is set\n";
echo "if [ -z \"\$CONFIGRYPT_KEY\" ]; then\n";
echo "    echo \"Error: CONFIGRYPT_KEY not set\"\n";
echo "    exit 1\n";
echo "fi\n\n";
echo "# Test decryption of critical secrets\n";
echo "php artisan configrypt:decrypt \"\$DATABASE_PASSWORD\" > /dev/null\n";
echo "if [ \$? -eq 0 ]; then\n";
echo "    echo \"✓ Database password decryption successful\"\n";
echo "else\n";
echo "    echo \"✗ Database password decryption failed\"\n";
echo "    exit 1\n";
echo "fi\n\n";
echo "echo \"✓ Environment \$ENVIRONMENT validation successful\"\n\n";

echo "=== Example Complete ===\n";
echo "Next: Try the batch-operations.php example for bulk encryption/decryption operations\n";
