<?php
/**
 * Example: Basic Encryption and Decryption
 * 
 * This example demonstrates the fundamental encryption and decryption operations
 * using Laravel Configrypt. It shows how to use both the service class directly
 * and the Laravel facade.
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

use LaravelConfigrypt\Services\ConfigryptService;
use LaravelConfigrypt\Facades\Configrypt;

echo "=== Laravel Configrypt Basic Usage Example ===\n\n";

// Method 1: Using the service directly
echo "1. Using ConfigryptService directly:\n";
echo "-----------------------------------\n";

try {
    // Create service instance
    // In a real Laravel app, this would be injected or resolved from container
    $encryptionKey = $_ENV['CONFIGRYPT_KEY'] ?? $_ENV['APP_KEY'] ?? 'example-key-32-characters-long--';
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
    echo "Is encrypted: " . ($isEncrypted ? 'Yes' : 'No') . "\n";
    
    // Decrypt the value
    $decryptedValue = $service->decrypt($encryptedValue);
    echo "Decrypted value: {$decryptedValue}\n";
    
    // Verify the values match
    $matches = $originalValue === $decryptedValue;
    echo "Values match: " . ($matches ? 'Yes' : 'No') . "\n\n";
    
} catch (Exception $e) {
    echo "Error using service: " . $e->getMessage() . "\n\n";
}

// Method 2: Using the Laravel Facade (in Laravel context)
echo "2. Using Configrypt Facade (Laravel context required):\n";
echo "-----------------------------------------------------\n";

// Note: This would work in a Laravel application context
// For demo purposes, we'll show the syntax

echo "// In a Laravel application:\n";
echo "use LaravelConfigrypt\\Facades\\Configrypt;\n\n";

echo "\$original = 'api-secret-key';\n";
echo "\$encrypted = Configrypt::encrypt(\$original);\n";
echo "\$decrypted = Configrypt::decrypt(\$encrypted);\n";
echo "\$isEncrypted = Configrypt::isEncrypted(\$encrypted);\n\n";

// Method 3: Different data types
echo "3. Encrypting different types of data:\n";
echo "------------------------------------\n";

$testData = [
    'Database password' => 'super-secret-db-password',
    'API key' => 'sk_live_1234567890abcdef',
    'JWT secret' => 'your-jwt-secret-key-here',
    'JSON data' => '{"api_key":"12345","secret":"abcdef"}',
    'Special characters' => 'p@ssw0rd!#$%^&*()',
    'Empty string' => '',
    'Long text' => str_repeat('Lorem ipsum dolor sit amet, ', 10),
];

try {
    $service = new ConfigryptService(
        key: $encryptionKey,
        prefix: 'ENC:',
        cipher: 'AES-256-CBC'
    );
    
    foreach ($testData as $description => $value) {
        echo "{$description}:\n";
        echo "  Original: " . (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value) . "\n";
        
        if (empty($value)) {
            echo "  Note: Empty values typically don't need encryption\n";
        } else {
            $encrypted = $service->encrypt($value);
            echo "  Encrypted: " . substr($encrypted, 0, 30) . "...\n";
            
            $decrypted = $service->decrypt($encrypted);
            $matches = $value === $decrypted;
            echo "  Decryption: " . ($matches ? 'Success' : 'Failed') . "\n";
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "Error in batch testing: " . $e->getMessage() . "\n\n";
}

// Method 4: Demonstrating prefix usage
echo "4. Custom prefix usage:\n";
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
    echo "Is encrypted: " . ($customService->isEncrypted($encrypted) ? 'Yes' : 'No') . "\n";
    
    $decrypted = $customService->decrypt($encrypted);
    echo "Decrypted: {$decrypted}\n\n";
    
} catch (Exception $e) {
    echo "Error with custom prefix: " . $e->getMessage() . "\n\n";
}

// Method 5: Error handling examples
echo "5. Error handling:\n";
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
        echo "  Expected error: " . $e->getMessage() . "\n";
    }
    
    // Test with wrong prefix
    echo "Testing prefix detection:\n";
    $nonEncrypted = 'plain-text-value';
    echo "  Is 'plain-text-value' encrypted? " . ($service->isEncrypted($nonEncrypted) ? 'Yes' : 'No') . "\n";
    
    $encrypted = 'ENC:some-encrypted-value';
    echo "  Is 'ENC:some-encrypted-value' encrypted? " . ($service->isEncrypted($encrypted) ? 'Yes' : 'No') . "\n";
    
} catch (Exception $e) {
    echo "Error in error handling demo: " . $e->getMessage() . "\n";
}

echo "\n=== Example Complete ===\n";
echo "Next: Try the environment-variables.php example to see how to use encrypted values in .env files\n";