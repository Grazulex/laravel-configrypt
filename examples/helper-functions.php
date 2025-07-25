<?php

/**
 * Example: Helper Functions and Str Macro
 *
 * This example demonstrates the helper functions and Str macro added by
 * Laravel Configrypt for easy migration and seamless integration.
 *
 * Features demonstrated:
 * - configrypt_env() and encrypted_env() helper functions
 * - Str::decryptEnv() macro for easy migration
 * - Auto-decryption capabilities
 * - Error handling and fallback values
 * - Migration patterns from plain env() calls
 *
 * Usage:
 * - Run in Laravel application context: php artisan tinker
 * - Then: require 'examples/helper-functions.php';
 *
 * Requirements:
 * - Laravel Configrypt package installed
 * - Laravel application context (for helpers and Str macro)
 * - CONFIGRYPT_KEY or APP_KEY environment variable set
 */
echo "=== Laravel Configrypt Helper Functions & Str Macro Example ===\n\n";

// Note: This example requires Laravel application context
if (! function_exists('app') || ! function_exists('configrypt_env')) {
    echo "❌ This example requires Laravel application context.\n";
    echo "Please run this in Laravel artisan tinker:\n";
    echo "php artisan tinker\n";
    echo ">>> require 'examples/helper-functions.php';\n\n";
    exit(1);
}

use Illuminate\Support\Str;
use LaravelConfigrypt\Services\ConfigryptService;

echo "🔧 Testing Helper Functions\n";
echo "===========================\n\n";

// Set up test environment variables
$service = app(ConfigryptService::class);
$testValues = [
    'TEST_PLAIN_VALUE' => 'plain-text-password',
    'TEST_ENCRYPTED_VALUE' => $service->encrypt('encrypted-secret-password'),
    'TEST_MISSING_VALUE' => null,
];

foreach ($testValues as $key => $value) {
    if ($value !== null) {
        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }
}

echo "Test environment variables set up:\n";
echo "- TEST_PLAIN_VALUE: plain text value\n";
echo "- TEST_ENCRYPTED_VALUE: encrypted value\n";
echo "- TEST_MISSING_VALUE: not set\n\n";

// Test 1: configrypt_env() helper function
echo "1. Testing configrypt_env() helper function:\n";
echo "--------------------------------------------\n";

$plainValue = configrypt_env('TEST_PLAIN_VALUE');
echo "Plain value: {$plainValue}\n";

$encryptedValue = configrypt_env('TEST_ENCRYPTED_VALUE');
echo "Encrypted value (decrypted): {$encryptedValue}\n";

$missingValue = configrypt_env('TEST_MISSING_VALUE', 'default-fallback');
echo "Missing value (with default): {$missingValue}\n";

$nonExistentValue = configrypt_env('NON_EXISTENT_KEY', 'fallback-value');
echo "Non-existent key (with default): {$nonExistentValue}\n\n";

// Test 2: encrypted_env() alias function
echo "2. Testing encrypted_env() alias function:\n";
echo "-----------------------------------------\n";

$plainAlias = encrypted_env('TEST_PLAIN_VALUE');
echo "Plain value (via alias): {$plainAlias}\n";

$encryptedAlias = encrypted_env('TEST_ENCRYPTED_VALUE');
echo "Encrypted value (via alias): {$encryptedAlias}\n";

$missingAlias = encrypted_env('TEST_MISSING_VALUE', 'alias-default');
echo "Missing value (via alias): {$missingAlias}\n\n";

// Test 3: Str::decryptEnv() macro
echo "3. Testing Str::decryptEnv() macro:\n";
echo "-----------------------------------\n";

try {
    $strPlain = Str::decryptEnv('TEST_PLAIN_VALUE');
    echo "Plain value (via Str macro): {$strPlain}\n";

    $strEncrypted = Str::decryptEnv('TEST_ENCRYPTED_VALUE');
    echo "Encrypted value (via Str macro): {$strEncrypted}\n";

    $strMissing = Str::decryptEnv('TEST_MISSING_VALUE', 'str-default');
    echo "Missing value (via Str macro): {$strMissing}\n\n";
} catch (Exception $e) {
    echo "Str macro error (macro may not be available): {$e->getMessage()}\n\n";
}

// Test 4: Comparison with env() function
echo "4. Comparison with env() function:\n";
echo "----------------------------------\n";

echo "Raw env() results:\n";
echo '- Plain value: ' . env('TEST_PLAIN_VALUE') . "\n";
echo '- Encrypted value: ' . env('TEST_ENCRYPTED_VALUE') . "\n";
echo "  ^ Note: This shows encrypted value unless auto-decrypt is enabled\n\n";

echo "Helper function results:\n";
echo '- Plain value: ' . configrypt_env('TEST_PLAIN_VALUE') . "\n";
echo '- Encrypted value: ' . configrypt_env('TEST_ENCRYPTED_VALUE') . "\n";
echo "  ^ Note: This always returns decrypted value\n\n";

// Test 5: Auto-decryption demonstration
echo "5. Auto-decryption demonstration:\n";
echo "---------------------------------\n";

$autoDecryptEnabled = $_ENV['CONFIGRYPT_AUTO_DECRYPT'] ?? config('configrypt.auto_decrypt', false);
echo 'Auto-decryption status: ' . ($autoDecryptEnabled ? 'Enabled' : 'Disabled') . "\n";

if ($autoDecryptEnabled) {
    echo "✅ With auto-decryption enabled:\n";
    echo "- env() calls return decrypted values automatically\n";
    echo "- Helper functions also work (redundant but safe)\n";
    echo "- Str macro provides easy migration path\n";
} else {
    echo "❌ With auto-decryption disabled:\n";
    echo "- env() calls return encrypted values (with ENC: prefix)\n";
    echo "- Helper functions are required for decryption\n";
    echo "- Str macro provides manual decryption\n";
}
echo "\n";

// Test 6: Error handling demonstration
echo "6. Error handling demonstration:\n";
echo "--------------------------------\n";

// Set up invalid encrypted value
$_ENV['TEST_INVALID_ENC'] = 'ENC:invalid-encrypted-data';
putenv('TEST_INVALID_ENC=ENC:invalid-encrypted-data');

echo "Testing with invalid encrypted value...\n";

$invalidResult = configrypt_env('TEST_INVALID_ENC', 'error-fallback');
echo "Invalid encrypted value result: {$invalidResult}\n";
echo "(Should return fallback value on decryption error)\n\n";

// Test 7: Migration patterns
echo "7. Migration patterns:\n";
echo "---------------------\n";

echo "Easy migration approaches:\n\n";

echo "Option A - Enable auto-decryption (recommended):\n";
echo "1. Set CONFIGRYPT_AUTO_DECRYPT=true in .env\n";
echo "2. No code changes needed - env() calls work normally\n";
echo "3. Seamless integration with existing codebase\n\n";

echo "Option B - Use helper functions:\n";
echo "// Before:\n";
echo "\$password = env('DB_PASSWORD');\n";
echo "// After:\n";
echo "\$password = configrypt_env('DB_PASSWORD');\n\n";

echo "Option C - Use Str macro for easy search & replace:\n";
echo "// Before:\n";
echo "\$password = env('DB_PASSWORD');\n";
echo "// After:\n";
echo "\$password = Str::decryptEnv('DB_PASSWORD');\n\n";

echo "Option D - Gradual migration:\n";
echo "// Safe migration helper\n";
echo "function safe_env(\$key, \$default = null) {\n";
echo "    return configrypt_env(\$key, env(\$key, \$default));\n";
echo "}\n\n";

// Test 8: Real-world usage patterns
echo "8. Real-world usage patterns:\n";
echo "-----------------------------\n";

echo "Database configuration example:\n";
echo "// config/database.php\n";
echo "'mysql' => [\n";
echo "    'driver' => 'mysql',\n";
echo "    'host' => env('DB_HOST', '127.0.0.1'),\n";
echo "    'database' => env('DB_DATABASE', 'forge'),\n";
echo "    'username' => env('DB_USERNAME', 'forge'),\n";
if ($autoDecryptEnabled) {
    echo "    'password' => env('DB_PASSWORD', ''), // Works with auto-decrypt\n";
} else {
    echo "    'password' => configrypt_env('DB_PASSWORD', ''), // Use helper\n";
}
echo "],\n\n";

echo "Service configuration example:\n";
echo "// config/services.php\n";
echo "'stripe' => [\n";
echo "    'model' => App\\Models\\User::class,\n";
echo "    'key' => env('STRIPE_KEY'),\n";
if ($autoDecryptEnabled) {
    echo "    'secret' => env('STRIPE_SECRET'), // Works with auto-decrypt\n";
} else {
    echo "    'secret' => configrypt_env('STRIPE_SECRET'), // Use helper\n";
}
echo "],\n\n";

// Test 9: Performance considerations
echo "9. Performance considerations:\n";
echo "------------------------------\n";

$start = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    configrypt_env('TEST_ENCRYPTED_VALUE');
}
$helperTime = microtime(true) - $start;

$start = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    env('TEST_PLAIN_VALUE');
}
$envTime = microtime(true) - $start;

echo "Performance comparison (1000 iterations):\n";
printf("- configrypt_env(): %.4f seconds\n", $helperTime);
printf("- env(): %.4f seconds\n", $envTime);
printf("- Overhead: %.2fx\n", $helperTime / $envTime);
echo "\nNote: Helper functions have minimal overhead and include safety checks\n\n";

// Test 10: Best practices
echo "10. Best practices:\n";
echo "-------------------\n";

echo "✅ Recommended approaches:\n";
echo "1. Enable auto-decryption for new projects\n";
echo "2. Use helper functions for explicit control\n";
echo "3. Use Str macro for easy migration\n";
echo "4. Always provide fallback values for critical configs\n";
echo "5. Test encrypted values in all environments\n\n";

echo "⚠️  Things to avoid:\n";
echo "1. Don't mix auto-decrypt with manual decryption unnecessarily\n";
echo "2. Don't rely on env() for encrypted values without auto-decrypt\n";
echo "3. Don't ignore decryption errors - use fallbacks\n";
echo "4. Don't hardcode encryption keys in application code\n\n";

// Clean up test environment variables
foreach (array_keys($testValues) as $key) {
    unset($_ENV[$key]);
    putenv($key);
}
unset($_ENV['TEST_INVALID_ENC']);
putenv('TEST_INVALID_ENC');

echo "=== Helper Functions Example Complete ===\n";
echo "Next Steps:\n";
echo "- Try enabling auto-decryption: CONFIGRYPT_AUTO_DECRYPT=true\n";
echo "- Migrate existing env() calls using helper functions\n";
echo "- Use Str::decryptEnv() for easy search & replace migration\n";
echo "- Check environment-variables.php for .env file examples\n";
