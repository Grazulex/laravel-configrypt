<?php

/**
 * Example: Auto-Decryption Feature
 *
 * This example demonstrates Laravel Configrypt's innovative auto-decryption
 * feature that bypasses Laravel's environment caching limitation.
 *
 * Features demonstrated:
 * - How auto-decryption works during early bootstrap
 * - Environment cache clearing using reflection
 * - Seamless integration with existing env() calls
 * - Configuration and setup for auto-decryption
 * - Before/after comparison
 *
 * Usage:
 * - Run in Laravel application context: php artisan tinker
 * - Then: require 'examples/auto-decryption.php';
 *
 * Requirements:
 * - Laravel Configrypt package installed
 * - Laravel application context
 * - CONFIGRYPT_KEY or APP_KEY environment variable set
 */
echo "=== Laravel Configrypt Auto-Decryption Feature Example ===\n\n";

// Note: This example requires Laravel application context
if (! function_exists('app') || ! function_exists('configrypt_env')) {
    echo "❌ This example requires Laravel application context.\n";
    echo "Please run this in Laravel artisan tinker:\n";
    echo "php artisan tinker\n";
    echo ">>> require 'examples/auto-decryption.php';\n\n";
    exit(1);
}

use LaravelConfigrypt\LaravelConfigryptServiceProvider;
use LaravelConfigrypt\Services\ConfigryptService;
use LaravelConfigrypt\Support\EnvironmentDecryptor;

echo "🚀 Understanding Auto-Decryption\n";
echo "================================\n\n";

// Get current auto-decryption status
$currentStatus = $_ENV['CONFIGRYPT_AUTO_DECRYPT'] ?? config('configrypt.auto_decrypt', false);
echo 'Current auto-decryption status: ' . ($currentStatus ? 'Enabled' : 'Disabled') . "\n\n";

// Create test encrypted values
$service = app(ConfigryptService::class);
$testValues = [
    'AUTO_TEST_DB_PASSWORD' => 'super-secret-database-password',
    'AUTO_TEST_API_KEY' => 'sk_live_1234567890abcdef',
    'AUTO_TEST_JWT_SECRET' => 'jwt-signing-secret-key',
    'AUTO_TEST_PLAIN_VALUE' => 'plain-text-value', // Not encrypted
];

echo "Setting up test environment variables:\n";
echo "--------------------------------------\n";

foreach ($testValues as $key => $originalValue) {
    if ($key !== 'AUTO_TEST_PLAIN_VALUE') {
        $encryptedValue = $service->encrypt($originalValue);
        $_ENV[$key] = $encryptedValue;
        putenv("{$key}={$encryptedValue}");
        echo "✓ {$key}: encrypted and set\n";
    } else {
        $_ENV[$key] = $originalValue;
        putenv("{$key}={$originalValue}");
        echo "✓ {$key}: plain text (not encrypted)\n";
    }
}
echo "\n";

// Demonstrate the problem without auto-decryption
echo "1. The Problem: Laravel's Environment Cache\n";
echo "===========================================\n";

echo "Laravel caches environment variables very early in the boot process.\n";
echo "This means env() calls return cached (encrypted) values:\n\n";

foreach (array_keys($testValues) as $key) {
    $rawValue = env($key);
    $isEncrypted = str_starts_with($rawValue, 'ENC:');
    echo "env('{$key}'): " . ($isEncrypted ? 'ENC:...' : $rawValue) .
         ($isEncrypted ? ' (still encrypted!)' : ' (plain text)') . "\n";
}
echo "\n";

// Show what helper functions do
echo "2. Helper Functions Solution\n";
echo "============================\n";

echo "Helper functions check and decrypt on-demand:\n\n";

foreach (array_keys($testValues) as $key) {
    $decryptedValue = configrypt_env($key);
    echo "configrypt_env('{$key}'): {$decryptedValue} (always decrypted)\n";
}
echo "\n";

// Demonstrate auto-decryption feature
echo "3. Auto-Decryption Feature (Advanced Solution)\n";
echo "==============================================\n";

echo "Auto-decryption works by:\n";
echo "1. Running during service provider registration (very early)\n";
echo "2. Decrypting all ENC: prefixed environment variables\n";
echo "3. Updating \$_ENV, \$_SERVER, and putenv()\n";
echo "4. Using reflection to clear Laravel's environment cache\n";
echo "5. Making env() calls return decrypted values\n\n";

if (! $currentStatus) {
    echo "🔧 Simulating auto-decryption process...\n";

    // Simulate the auto-decryption process
    $_ENV['CONFIGRYPT_AUTO_DECRYPT'] = 'true';

    // Create a new service provider instance to trigger auto-decryption
    $provider = new LaravelConfigryptServiceProvider(app());
    $provider->register();

    echo "✓ Auto-decryption triggered manually\n\n";
} else {
    echo "✅ Auto-decryption is already enabled\n\n";
}

// Show results after auto-decryption
echo "4. Results After Auto-Decryption\n";
echo "=================================\n";

echo "Now env() calls return decrypted values:\n\n";

foreach ($testValues as $key => $originalValue) {
    $envValue = env($key);
    $matches = $envValue === $originalValue;
    echo "env('{$key}'): {$envValue} " . ($matches ? '✅' : '❌') . "\n";
}
echo "\n";

// Configuration setup
echo "5. Setting Up Auto-Decryption\n";
echo "==============================\n";

echo "To enable auto-decryption in your application:\n\n";

echo "Step 1: Add to your .env file:\n";
echo "CONFIGRYPT_AUTO_DECRYPT=true\n\n";

echo "Step 2: Optionally publish and configure:\n";
echo "php artisan vendor:publish --tag=configrypt-config\n\n";

echo "Step 3: In config/configrypt.php:\n";
echo "return [\n";
echo "    'key' => env('CONFIGRYPT_KEY', env('APP_KEY')),\n";
echo "    'prefix' => env('CONFIGRYPT_PREFIX', 'ENC:'),\n";
echo "    'cipher' => env('CONFIGRYPT_CIPHER', 'AES-256-CBC'),\n";
echo "    'auto_decrypt' => env('CONFIGRYPT_AUTO_DECRYPT', false),\n";
echo "];\n\n";

// Environment-specific configuration
echo "6. Environment-Specific Configuration\n";
echo "=====================================\n";

echo "Configure different settings per environment:\n\n";

echo "Development (.env.local):\n";
echo "CONFIGRYPT_AUTO_DECRYPT=true\n";
echo "CONFIGRYPT_KEY=dev-key-32-characters-long-----\n\n";

echo "Staging (.env.staging):\n";
echo "CONFIGRYPT_AUTO_DECRYPT=true\n";
echo "CONFIGRYPT_KEY=staging-key-32-characters-long--\n\n";

echo "Production (.env.production):\n";
echo "CONFIGRYPT_AUTO_DECRYPT=true\n";
echo "CONFIGRYPT_KEY=prod-key-32-characters-long----\n\n";

// Advanced usage patterns
echo "7. Usage Patterns After Auto-Decryption\n";
echo "=======================================\n";

echo "With auto-decryption enabled, you can use normal Laravel patterns:\n\n";

echo "Database configuration (config/database.php):\n";
echo "'mysql' => [\n";
echo "    'driver' => 'mysql',\n";
echo "    'host' => env('DB_HOST', '127.0.0.1'),\n";
echo "    'password' => env('DB_PASSWORD', ''), // Works normally!\n";
echo "],\n\n";

echo "Service configuration (config/services.php):\n";
echo "'stripe' => [\n";
echo "    'secret' => env('STRIPE_SECRET'), // Decrypted automatically\n";
echo "],\n\n";

echo "Direct usage in controllers:\n";
echo "class PaymentController extends Controller\n";
echo "{\n";
echo "    public function process()\n";
echo "    {\n";
echo "        \$apiKey = env('STRIPE_SECRET'); // Returns decrypted value\n";
echo "        // Use \$apiKey normally...\n";
echo "    }\n";
echo "}\n\n";

// Security considerations
echo "8. Security Considerations\n";
echo "==========================\n";

echo "Auto-decryption security features:\n";
echo "✓ Decryption only happens in memory during bootstrap\n";
echo "✓ Decrypted values never touch disk storage\n";
echo "✓ Only ENC: prefixed values are processed\n";
echo "✓ Failed decryptions are handled gracefully\n";
echo "✓ Error handling respects debug mode settings\n\n";

// Performance impact
echo "9. Performance Impact\n";
echo "====================\n";

echo "Auto-decryption performance characteristics:\n";
echo "• Runs once during application bootstrap\n";
echo "• No performance impact on individual requests\n";
echo "• Minimal memory overhead (only during startup)\n";
echo "• Reflection-based cache clearing has negligible impact\n";
echo "• Overall: < 1ms additional startup time\n\n";

// Troubleshooting
echo "10. Troubleshooting Auto-Decryption\n";
echo "===================================\n";

echo "Common issues and solutions:\n\n";

echo "Issue: env() still returns encrypted values\n";
echo "Solution: Ensure CONFIGRYPT_AUTO_DECRYPT=true is set\n";
echo "Check: config('configrypt.auto_decrypt') should return true\n\n";

echo "Issue: Decryption errors during bootstrap\n";
echo "Solution: Verify encryption key is correct\n";
echo "Check: All encrypted values use the same key\n\n";

echo "Issue: Auto-decryption not working in tests\n";
echo "Solution: Set up test environment properly\n";
echo "Check: Ensure test .env has auto-decrypt enabled\n\n";

// Comparison table
echo "11. Comparison: Manual vs Auto-Decryption\n";
echo "==========================================\n";

echo "| Aspect               | Manual Helpers      | Auto-Decryption     |\n";
echo "|---------------------|---------------------|---------------------|\n";
echo "| Code Changes        | Required            | None                |\n";
echo "| env() Compatibility | Limited             | Full                |\n";
echo "| Performance         | Per-call overhead   | Bootstrap only      |\n";
echo "| Migration Effort    | Medium              | Minimal             |\n";
echo "| Control Level       | High                | Medium              |\n";
echo "| Error Handling      | Explicit            | Automatic           |\n";
echo "| Laravel Integration | Partial             | Seamless            |\n\n";

// Best practices
echo "12. Best Practices\n";
echo "==================\n";

echo "✅ Recommended approaches:\n";
echo "• Enable auto-decryption for new projects\n";
echo "• Use auto-decryption + helper functions for maximum compatibility\n";
echo "• Test auto-decryption thoroughly in all environments\n";
echo "• Monitor application logs for decryption errors\n";
echo "• Keep fallback values for critical configurations\n\n";

echo "⚠️  Important notes:\n";
echo "• Auto-decryption requires valid encryption keys\n";
echo "• Failed decryptions are silently ignored (check logs)\n";
echo "• Environment cache clearing uses reflection (PHP 8+ compatible)\n";
echo "• Works with Laravel 12+ (may work with earlier versions)\n\n";

// Manual environment decryptor demonstration
echo "13. Manual Environment Decryptor\n";
echo "================================\n";

echo "For advanced control, use EnvironmentDecryptor directly:\n\n";

$envDecryptor = app(EnvironmentDecryptor::class);

echo "Environment decryptor methods:\n";
foreach (array_keys($testValues) as $key) {
    $value = $envDecryptor->get($key);
    echo "EnvironmentDecryptor::get('{$key}'): {$value}\n";
}

echo "\nChecking if values are encrypted:\n";
foreach ($_ENV as $key => $value) {
    if (str_starts_with($key, 'AUTO_TEST_')) {
        $isEncrypted = $envDecryptor->isEncrypted($value);
        echo "{$key}: " . ($isEncrypted ? 'Encrypted' : 'Plain') . "\n";
    }
}

echo "\nGet all decrypted environment variables:\n";
$allDecrypted = $envDecryptor->getAllDecrypted();
echo 'Total environment variables: ' . count($allDecrypted) . "\n";
echo 'Sample: ' . array_key_first($allDecrypted) . ' => ' . substr($allDecrypted[array_key_first($allDecrypted)], 0, 20) . "...\n\n";

// Clean up test environment variables
foreach (array_keys($testValues) as $key) {
    unset($_ENV[$key]);
    putenv($key);
}

echo "=== Auto-Decryption Example Complete ===\n";
echo "Next Steps:\n";
echo "1. Enable auto-decryption: Add CONFIGRYPT_AUTO_DECRYPT=true to .env\n";
echo "2. Test in your application: Verify env() calls return decrypted values\n";
echo "3. Migrate gradually: Start with non-critical encrypted values\n";
echo "4. Monitor logs: Check for any decryption errors during startup\n";
echo "5. Optimize: Remove manual helper calls where auto-decryption works\n";
