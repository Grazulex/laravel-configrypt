<?php

/**
 * Example: Basic Encryption and Decryption
 *
 * This example demonstrates the fundamental encryption and decryption operations
 * using Laravel Configrypt. It shows multiple approaches: service class directly,
 * helper functions, Str macro, and facade usage.
 *
 * Usage:
 * - Run: php examples/basic-usage.php
 * - Requires: Laravel Configrypt installed and encryption key configured
 *
 * Requirements:
 * - Laravel Configrypt package
 * - CONFIGRYPT_KEY or APP_KEY environment variable set
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Str;
use LaravelConfigrypt\Services\ConfigryptService;

echo "=== Laravel Configrypt Basic Usage Example ===\n\n";

// Get encryption key from environment or use example key
$encryptionKey = $_ENV['CONFIGRYPT_KEY'] ?? $_ENV['APP_KEY'] ?? 'example-key-32-characters-long--';

echo "🔐 Multiple Ways to Use Laravel Configrypt\n";
echo "==========================================\n\n";

// Method 1: Using the service directly
echo "1. Using ConfigryptService directly:\n";
echo "-----------------------------------\n";

try {
    // Create service instance
    // In a real Laravel app, this would be injected or resolved from container
    $service = new ConfigryptService(
        key: $encryptionKey,
        prefix: 'ENC:',
        cipher: 'AES-256-CBC'
    );

    // Original value to encrypt
    $originalValue = 'my-secret-password';
    echo "Original value: {$originalValue}\n";

    // Encrypt the value
    $encryptedValue = $service->encrypt($originalValue);
    echo "Encrypted value: {$encryptedValue}\n";

    // Check if value is encrypted
    $isEncrypted = $service->isEncrypted($encryptedValue);
    echo 'Is encrypted: ' . ($isEncrypted ? 'Yes' : 'No') . "\n";

    // Decrypt the value
    $decryptedValue = $service->decrypt($encryptedValue);
    echo "Decrypted value: {$decryptedValue}\n";

    // Verify the values match
    $matches = $originalValue === $decryptedValue;
    echo 'Values match: ' . ($matches ? 'Yes' : 'No') . "\n\n";
} catch (Exception $e) {
    echo 'Error using service: ' . $e->getMessage() . "\n\n";
}

// Method 2: Helper Functions (Recommended)
echo "2. Using Helper Functions (Recommended):\n";
echo "---------------------------------------\n";

echo "Note: Helper functions require Laravel application context.\n";
echo "In a Laravel app, you would use:\n\n";

echo "// Primary helper function\n";
echo "\$password = configrypt_env('DB_PASSWORD');\n";
echo "\$apiKey = configrypt_env('API_KEY', 'default-value');\n\n";

echo "// Alias helper function\n";
echo "\$secret = encrypted_env('JWT_SECRET');\n\n";

echo "These helpers automatically:\n";
echo "- Check if the value has ENC: prefix\n";
echo "- Decrypt encrypted values\n";
echo "- Return original value if not encrypted\n";
echo "- Provide fallback values on errors\n\n";

// Method 3: Str Macro (Easy Migration)
echo "3. Using Str Macro (Easy Migration):\n";
echo "-----------------------------------\n";

echo "In a Laravel application context:\n";
echo "use Illuminate\\Support\\Str;\n\n";

echo "// Easy migration from env() calls\n";
echo "\$password = Str::decryptEnv('DB_PASSWORD');\n";
echo "\$apiKey = Str::decryptEnv('STRIPE_SECRET');\n\n";

echo "Perfect for search & replace in codebase:\n";
echo "Before: env('DB_PASSWORD')\n";
echo "After:  Str::decryptEnv('DB_PASSWORD')\n\n";

// Method 4: Auto-Decryption Feature
echo "4. Auto-Decryption Feature (Advanced):\n";
echo "--------------------------------------\n";

echo "Set in .env file:\n";
echo "CONFIGRYPT_AUTO_DECRYPT=true\n\n";

echo "After enabling auto-decryption:\n";
echo "// Your existing env() calls work normally!\n";
echo "\$password = env('DB_PASSWORD');  // Returns decrypted value\n";
echo "\$apiKey = env('STRIPE_SECRET');  // Returns decrypted value\n\n";

echo "How auto-decryption works:\n";
echo "1. Decryption happens during early service provider registration\n";
echo "2. All ENC: prefixed env vars are automatically decrypted\n";
echo "3. Laravel's env cache is cleared using reflection\n";
echo "4. env() calls return decrypted values seamlessly\n\n";

// Method 5: Different data types
echo "5. Encrypting different types of data:\n";
echo "------------------------------------\n";

$testData = [
    'Database password' => 'super-secret-db-password',
    'API key' => 'sk_live_1234567890abcdef',
    'JWT secret' => 'your-jwt-secret-key-here',
    'JSON data' => '{"api_key":"12345","secret":"abcdef"}',
    'Special characters' => 'p@ssw0rd!#$%^&*()',
    'Empty string' => '',
    'Long text' => str_repeat('Lorem ipsum dolor sit amet, ', 10),
    'Unicode text' => 'Héllo Wörld! 🔐🚀',
];

try {
    $service = new ConfigryptService(
        key: $encryptionKey,
        prefix: 'ENC:',
        cipher: 'AES-256-CBC'
    );

    foreach ($testData as $description => $value) {
        echo "{$description}:\n";
        echo '  Original: ' . (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value) . "\n";

        if (empty($value)) {
            echo "  Note: Empty values typically don't need encryption\n";
        } else {
            $encrypted = $service->encrypt($value);
            echo '  Encrypted: ' . substr($encrypted, 0, 30) . "...\n";

            $decrypted = $service->decrypt($encrypted);
            $matches = $value === $decrypted;
            echo '  Decryption: ' . ($matches ? 'Success' : 'Failed') . "\n";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo 'Error in batch testing: ' . $e->getMessage() . "\n\n";
}

// Method 6: Demonstrating prefix usage
echo "6. Custom prefix usage:\n";
echo "----------------------\n";

try {
    $customService = new ConfigryptService(
        key: $encryptionKey,
        prefix: 'CUSTOM_ENC:',
        cipher: 'AES-256-CBC'
    );

    $value = 'secret-with-custom-prefix';
    $encrypted = $customService->encrypt($value);

    echo "Value: {$value}\n";
    echo "Encrypted with custom prefix: {$encrypted}\n";
    echo "Prefix: {$customService->getPrefix()}\n";
    echo 'Is encrypted: ' . ($customService->isEncrypted($encrypted) ? 'Yes' : 'No') . "\n";

    $decrypted = $customService->decrypt($encrypted);
    echo "Decrypted: {$decrypted}\n\n";
} catch (Exception $e) {
    echo 'Error with custom prefix: ' . $e->getMessage() . "\n\n";
}

// Method 7: Error handling examples
echo "7. Error handling:\n";
echo "-----------------\n";

try {
    $service = new ConfigryptService(
        key: $encryptionKey,
        prefix: 'ENC:',
        cipher: 'AES-256-CBC'
    );

    // Test with invalid encrypted data
    echo "Testing with invalid encrypted data:\n";
    try {
        $service->decrypt('ENC:invalid-encrypted-data');
    } catch (Exception $e) {
        echo '  Expected error: ' . $e->getMessage() . "\n";
    }

    // Test with wrong prefix
    echo "Testing prefix detection:\n";
    $nonEncrypted = 'plain-text-value';
    echo "  Is 'plain-text-value' encrypted? " . ($service->isEncrypted($nonEncrypted) ? 'Yes' : 'No') . "\n";

    $encrypted = 'ENC:some-encrypted-value';
    echo "  Is 'ENC:some-encrypted-value' encrypted? " . ($service->isEncrypted($encrypted) ? 'Yes' : 'No') . "\n";
} catch (Exception $e) {
    echo 'Error in error handling demo: ' . $e->getMessage() . "\n";
}

// Method 8: Usage recommendations
echo "\n8. Usage Recommendations:\n";
echo "------------------------\n";

echo "✅ Recommended Approaches:\n\n";

echo "For new Laravel projects:\n";
echo "1. Enable auto-decryption: CONFIGRYPT_AUTO_DECRYPT=true\n";
echo "2. Use existing env() calls normally\n";
echo "3. No code changes needed!\n\n";

echo "For existing projects (migration):\n";
echo "1. Use helper functions: configrypt_env('KEY')\n";
echo "2. Or use Str macro: Str::decryptEnv('KEY')\n";
echo "3. Gradually enable auto-decryption once confident\n\n";

echo "For explicit control:\n";
echo "1. Keep auto-decryption disabled\n";
echo "2. Use helper functions or facades explicitly\n";
echo "3. Better for applications with complex env handling\n\n";

echo "⚠️  Things to remember:\n";
echo "- Set CONFIGRYPT_KEY or APP_KEY in environment\n";
echo "- Use consistent prefixes across environments\n";
echo "- Test encrypted values in all deployment environments\n";
echo "- Monitor decryption errors in production\n";

echo "\n=== Example Complete ===\n";
echo "Next Steps:\n";
echo "- Try environment-variables.php for .env file integration\n";
echo "- See database-config.php for real database configuration\n";
echo "- Check api-keys.php for API key management examples\n";
echo "- Enable auto-decryption in your Laravel app for seamless integration\n";
