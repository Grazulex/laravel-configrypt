# Troubleshooting

This guide helps you diagnose and resolve common issues when using Laravel Configrypt.

## Common Issues

### Installation Issues

#### Composer Installation Problems

**Problem**: Package not found or installation fails

```bash
composer require grazulex/laravel-configrypt
# Package grazulex/laravel-configrypt not found
```

**Solutions:**
1. Check the package name is correct
2. Ensure you have access to Packagist
3. Try updating Composer: `composer self-update`
4. Clear Composer cache: `composer clear-cache`

#### Service Provider Not Registered

**Problem**: Commands not available or service not working

**Symptoms:**
- `php artisan configrypt:encrypt` command not found
- Service not injected in controllers

**Solutions:**

1. **Check Laravel Version**: Ensure Laravel 12.19+ is installed
```bash
php artisan --version
```

2. **Manual Registration** (for older Laravel versions):
```php
// config/app.php
'providers' => [
    // ...
    LaravelConfigrypt\LaravelConfigryptServiceProvider::class,
],
```

3. **Clear Caches**:
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### Configuration Issues

#### Missing Encryption Key

**Problem**: "Encryption key cannot be empty" error

**Error Messages:**
```
InvalidArgumentException: Encryption key cannot be empty. Please set CONFIGRYPT_KEY or APP_KEY.
```

**Solutions:**

1. **Set CONFIGRYPT_KEY**:
```env
CONFIGRYPT_KEY=your-32-character-encryption-key
```

2. **Ensure APP_KEY is set**:
```bash
php artisan key:generate
```

3. **Check .env file is loaded**:
```php
// Test in tinker
php artisan tinker
>>> env('CONFIGRYPT_KEY')
>>> env('APP_KEY')
```

#### Wrong Key Length

**Problem**: Encryption/decryption fails with key length errors

**Solutions:**

Laravel Configrypt automatically handles key length, but if you're getting errors:

1. **For AES-256-CBC**: Ensure key is 32 characters or let the package hash it
2. **Check cipher configuration**:
```env
CONFIGRYPT_CIPHER=AES-256-CBC
```

#### Configuration Not Published

**Problem**: Default configuration not working as expected

**Solutions:**

1. **Publish configuration**:
```bash
php artisan vendor:publish --tag=configrypt-config
```

2. **Check config/configrypt.php exists and is correct**

3. **Clear config cache**:
```bash
php artisan config:clear
```

### Encryption/Decryption Issues

#### Decryption Fails

**Problem**: "The payload is invalid" or "The MAC is invalid" errors

**Error Messages:**
```
Illuminate\Contracts\Encryption\DecryptException: The payload is invalid.
Illuminate\Contracts\Encryption\DecryptException: The MAC is invalid.
```

**Common Causes & Solutions:**

1. **Wrong Encryption Key**:
   - Ensure you're using the same key that was used to encrypt the value
   - Check if key was changed between encryption and decryption

2. **Corrupted Encrypted Value**:
   - Check the encrypted value wasn't modified
   - Ensure no extra characters or line breaks

3. **Different Cipher Method**:
   - Ensure the same cipher was used for encryption and decryption
   - Check `CONFIGRYPT_CIPHER` setting

4. **Encoding Issues**:
   - Ensure encrypted values are properly copied
   - Check for invisible characters

**Debugging Steps:**

```php
// Test encryption/decryption cycle
use LaravelConfigrypt\Facades\Configrypt;

$original = 'test-value';
$encrypted = Configrypt::encrypt($original);
echo "Encrypted: " . $encrypted . "\n";

$decrypted = Configrypt::decrypt($encrypted);
echo "Decrypted: " . $decrypted . "\n";

// Should output the original value
```

#### Auto-Decryption Not Working

**Problem**: Environment variables not automatically decrypted

**Symptoms:**
- `env('MY_SECRET')` returns encrypted value instead of plain text
- Config values still encrypted
- Helper functions work but `env()` doesn't

**Solutions:**

1. **Check auto_decrypt setting**:
```env
CONFIGRYPT_AUTO_DECRYPT=true
```

2. **Verify prefix matches**:
```env
CONFIGRYPT_PREFIX=ENC:
# Ensure your encrypted values start with this prefix
MY_SECRET=ENC:your-encrypted-value
```

3. **Check service provider is loading**:
```php
// In AppServiceProvider boot method, add:
public function boot()
{
    if (class_exists(\LaravelConfigrypt\LaravelConfigryptServiceProvider::class)) {
        echo "Configrypt service provider loaded\n";
    }
}
```

4. **Test manually**:
```php
use LaravelConfigrypt\Facades\Configrypt;

// Test if the value can be decrypted manually
$encrypted = env('MY_SECRET'); // Should be ENC:...
$decrypted = Configrypt::decrypt($encrypted);
echo $decrypted;
```

5. **Use helper functions as alternative**:
```php
// If auto-decryption isn't working, use helper functions
$password = configrypt_env('DB_PASSWORD');
$apiKey = encrypted_env('API_KEY');

// Or use Str macro
use Illuminate\Support\Str;
$secret = Str::decryptEnv('JWT_SECRET');
```

6. **Force auto-decryption manually**:
```php
use LaravelConfigrypt\Support\EnvironmentDecryptor;

// Manually trigger decryption of all environment variables
$envDecryptor = app(EnvironmentDecryptor::class);
$envDecryptor->decryptAll();
```

#### Helper Functions Not Working

**Problem**: `configrypt_env()` or `encrypted_env()` functions not available

**Symptoms:**
- "Call to undefined function configrypt_env()" error
- Helper functions not found

**Solutions:**

1. **Check Laravel context**:
```php
// Helper functions require Laravel application context
if (!function_exists('configrypt_env')) {
    echo "Helper functions not loaded\n";
}
```

2. **Ensure service provider is loaded**:
```bash
php artisan config:clear
php artisan cache:clear
```

3. **Test in artisan tinker**:
```bash
php artisan tinker
>>> configrypt_env('TEST_VAR', 'default');
```

4. **Manual service resolution**:
```php
// If helpers don't work, use service directly
use LaravelConfigrypt\Support\EnvironmentDecryptor;

$envDecryptor = app(EnvironmentDecryptor::class);
$value = $envDecryptor->get('MY_SECRET');
```

#### Str Macro Not Working

**Problem**: `Str::decryptEnv()` method not available

**Symptoms:**
- "Method Illuminate\Support\Str::decryptEnv does not exist" error

**Solutions:**

1. **Check service provider boot**:
```php
// Ensure service provider has booted
use Illuminate\Support\Str;

if (Str::hasMacro('decryptEnv')) {
    echo "Str macro is available\n";
} else {
    echo "Str macro not loaded\n";
}
```

2. **Force service provider boot**:
```php
// In AppServiceProvider
public function boot()
{
    // Force Configrypt service provider to boot
    $this->app->register(\LaravelConfigrypt\LaravelConfigryptServiceProvider::class);
}
```

3. **Use alternative methods**:
```php
// If Str macro isn't available, use helper functions
$value = configrypt_env('MY_SECRET');

// Or use facades directly
use LaravelConfigrypt\Facades\ConfigryptEnv;
$value = ConfigryptEnv::get('MY_SECRET');
```

### Command Issues

#### Commands Not Found

**Problem**: `configrypt:encrypt` or `configrypt:decrypt` commands not available

**Error Messages:**
```bash
php artisan configrypt:encrypt "test"
# Command "configrypt:encrypt" is not defined.
```

**Solutions:**

1. **Check if package is installed**:
```bash
composer show grazulex/laravel-configrypt
```

2. **Clear Artisan cache**:
```bash
php artisan clear-compiled
php artisan cache:clear
```

3. **Check service provider registration**:
```php
// config/app.php (for older Laravel versions)
'providers' => [
    LaravelConfigrypt\LaravelConfigryptServiceProvider::class,
],
```

#### Command Execution Errors

**Problem**: Commands fail with encryption errors

**Solutions:**

1. **Check encryption key is set**:
```bash
php artisan tinker
>>> config('configrypt.key')
```

2. **Test with a simple value**:
```bash
php artisan configrypt:encrypt "test"
```

3. **Check for special characters**:
```bash
# Use quotes for values with special characters
php artisan configrypt:encrypt "password with spaces"
```

### Environment Issues

#### Values Not Loading

**Problem**: Encrypted values in .env not being processed

**Debugging Steps:**

1. **Check .env file syntax**:
```env
# ✅ Correct
DB_PASSWORD=ENC:encrypted-value-here

# ❌ Incorrect (spaces around =)
DB_PASSWORD = ENC:encrypted-value-here
```

2. **Verify environment loading**:
```php
php artisan tinker
>>> $_ENV['DB_PASSWORD']  // Should show encrypted value
>>> env('DB_PASSWORD')    // Should show decrypted value (if auto_decrypt is on)
```

3. **Check for duplicate keys**:
```bash
# Look for duplicate environment variables
grep -n "DB_PASSWORD" .env
```

#### Environment Variable Precedence

**Problem**: Environment variables not being overridden correctly

**Laravel's environment variable precedence:**
1. System environment variables
2. `.env.local` (local environment)
3. `.env.{environment}` (e.g., `.env.production`)
4. `.env`

**Solutions:**

1. **Check which .env files exist**:
```bash
ls -la .env*
```

2. **Use the correct environment file for your environment**

3. **Check system environment variables**:
```bash
echo $DB_PASSWORD
```

### Performance Issues

#### Slow Decryption

**Problem**: Application startup or config loading is slow

**Debugging:**

1. **Check number of encrypted values**:
```bash
grep -c "ENC:" .env
```

2. **Profile auto-decryption**:
```php
// Add to service provider
$start = microtime(true);
$this->autoDecryptEnvironmentVariables();
$duration = microtime(true) - $start;
Log::info("Auto-decryption took: {$duration}s");
```

**Solutions:**

1. **Disable auto-decryption and decrypt manually**:
```env
CONFIGRYPT_AUTO_DECRYPT=false
```

2. **Cache decrypted values**:
```php
use Illuminate\Support\Facades\Cache;

class CachedConfigrypt
{
    public function getDecrypted(string $key): string
    {
        return Cache::remember("decrypted_{$key}", 3600, function () use ($key) {
            return Configrypt::decrypt(env($key));
        });
    }
}
```

### Testing Issues

#### Tests Failing

**Problem**: Tests fail with encryption/decryption errors

**Solutions:**

1. **Use test-specific encryption key**:
```php
// tests/TestCase.php
protected function setUp(): void
{
    parent::setUp();
    
    config(['configrypt.key' => 'test-key-32-characters-long----']);
}
```

2. **Mock the service for unit tests**:
```php
use LaravelConfigrypt\Services\ConfigryptService;

// In test method
$mockService = Mockery::mock(ConfigryptService::class);
$mockService->shouldReceive('decrypt')->andReturn('decrypted-value');
$this->app->instance(ConfigryptService::class, $mockService);
```

3. **Use environment-specific test configuration**:
```php
// phpunit.xml
<env name="CONFIGRYPT_KEY" value="test-key-32-characters-long----"/>
<env name="CONFIGRYPT_AUTO_DECRYPT" value="true"/>
```

## Debugging Tools

### Diagnostic Command

Create a custom command to diagnose issues:

```php
// app/Console/Commands/ConfigryptDiagnose.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use LaravelConfigrypt\Services\ConfigryptService;
use LaravelConfigrypt\Facades\Configrypt;

class ConfigryptDiagnose extends Command
{
    protected $signature = 'configrypt:diagnose';
    protected $description = 'Diagnose Configrypt configuration issues';

    public function handle()
    {
        $this->info('Laravel Configrypt Diagnostics');
        $this->line('================================');

        // Check configuration
        $this->checkConfiguration();
        
        // Check service
        $this->checkService();
        
        // Check environment variables
        $this->checkEnvironmentVariables();
        
        // Test encryption/decryption
        $this->testEncryptionDecryption();
    }
    
    private function checkConfiguration()
    {
        $this->info('Configuration Check:');
        
        $key = config('configrypt.key');
        $this->line("Key: " . ($key ? 'Set (' . strlen($key) . ' chars)' : 'Not set'));
        
        $prefix = config('configrypt.prefix');
        $this->line("Prefix: {$prefix}");
        
        $cipher = config('configrypt.cipher');
        $this->line("Cipher: {$cipher}");
        
        $autoDecrypt = config('configrypt.auto_decrypt');
        $this->line("Auto-decrypt: " . ($autoDecrypt ? 'Enabled' : 'Disabled'));
        
        $this->newLine();
    }
    
    private function checkService()
    {
        $this->info('Service Check:');
        
        try {
            $service = app(ConfigryptService::class);
            $this->line('✅ ConfigryptService can be resolved');
            
            $prefix = $service->getPrefix();
            $this->line("Service prefix: {$prefix}");
            
            // Check EnvironmentDecryptor
            $envDecryptor = app(\LaravelConfigrypt\Support\EnvironmentDecryptor::class);
            $this->line('✅ EnvironmentDecryptor can be resolved');
            
            // Check helper functions
            if (function_exists('configrypt_env')) {
                $this->line('✅ Helper functions available');
            } else {
                $this->error('❌ Helper functions not available');
            }
            
            // Check Str macro
            if (\Illuminate\Support\Str::hasMacro('decryptEnv')) {
                $this->line('✅ Str macro available');
            } else {
                $this->error('❌ Str macro not available');
            }
            
        } catch (Exception $e) {
            $this->error('❌ Service resolution failed: ' . $e->getMessage());
        }
        
        $this->newLine();
    }
    
    private function checkEnvironmentVariables()
    {
        $this->info('Environment Variables Check:');
        
        $prefix = config('configrypt.prefix', 'ENC:');
        $encryptedVars = [];
        
        foreach ($_ENV as $key => $value) {
            if (is_string($value) && str_starts_with($value, $prefix)) {
                $encryptedVars[$key] = $value;
            }
        }
        
        $this->line("Found " . count($encryptedVars) . " encrypted variables:");
        
        foreach ($encryptedVars as $key => $value) {
            $this->line("  {$key}: " . substr($value, 0, 20) . '...');
        }
        
        $this->newLine();
    }
    
    private function testEncryptionDecryption()
    {
        $this->info('Encryption/Decryption Test:');
        
        try {
            $testValue = 'test-value-' . time();
            $encrypted = Configrypt::encrypt($testValue);
            $this->line("✅ Encryption successful: " . substr($encrypted, 0, 30) . '...');
            
            $decrypted = Configrypt::decrypt($encrypted);
            
            if ($decrypted === $testValue) {
                $this->line("✅ Decryption successful");
            } else {
                $this->error("❌ Decryption failed: values don't match");
            }
            
            // Test helper functions
            $this->info('Helper Functions Test:');
            if (function_exists('configrypt_env')) {
                // Set test environment variable
                $_ENV['CONFIGRYPT_TEST_VAR'] = $encrypted;
                putenv("CONFIGRYPT_TEST_VAR={$encrypted}");
                
                $helperResult = configrypt_env('CONFIGRYPT_TEST_VAR');
                if ($helperResult === $testValue) {
                    $this->line("✅ configrypt_env() working");
                } else {
                    $this->error("❌ configrypt_env() failed");
                }
                
                $aliasResult = encrypted_env('CONFIGRYPT_TEST_VAR');
                if ($aliasResult === $testValue) {
                    $this->line("✅ encrypted_env() working");
                } else {
                    $this->error("❌ encrypted_env() failed");
                }
                
                // Clean up
                unset($_ENV['CONFIGRYPT_TEST_VAR']);
                putenv('CONFIGRYPT_TEST_VAR');
            } else {
                $this->error("❌ Helper functions not available");
            }
            
            // Test Str macro
            if (\Illuminate\Support\Str::hasMacro('decryptEnv')) {
                $this->line("✅ Str::decryptEnv() macro available");
            } else {
                $this->error("❌ Str::decryptEnv() macro not available");
            }
            
        } catch (Exception $e) {
            $this->error("❌ Test failed: " . $e->getMessage());
        }
    }
}
```

### Environment Variable Inspector

```php
// Create a helper to inspect environment variables
class EnvInspector
{
    public static function inspectEncryptedVars(): array
    {
        $results = [];
        $prefix = config('configrypt.prefix', 'ENC:');
        
        foreach ($_ENV as $key => $value) {
            if (is_string($value) && str_starts_with($value, $prefix)) {
                $results[$key] = [
                    'encrypted_value' => $value,
                    'can_decrypt' => false,
                    'decrypted_value' => null,
                    'error' => null,
                ];
                
                try {
                    $decrypted = Configrypt::decrypt($value);
                    $results[$key]['can_decrypt'] = true;
                    $results[$key]['decrypted_value'] = $decrypted;
                } catch (Exception $e) {
                    $results[$key]['error'] = $e->getMessage();
                }
            }
        }
        
        return $results;
    }
}
```

## Getting Help

### Information to Include

When reporting issues, include:

1. **Laravel version**: `php artisan --version`
2. **PHP version**: `php --version`
3. **Package version**: `composer show grazulex/laravel-configrypt`
4. **Configuration**: Content of `config/configrypt.php` (without keys)
5. **Error messages**: Full error messages and stack traces
6. **Environment**: Development, staging, production
7. **Steps to reproduce**: Exact steps that cause the issue

### Debug Mode

Enable debug mode for more detailed error information:

```env
APP_DEBUG=true
LOG_LEVEL=debug
```

### Logs

Check Laravel logs for additional information:

```bash
tail -f storage/logs/laravel.log
```

## Next Steps

- [Security Considerations](security.md) - Security-related troubleshooting
- [API Reference](api-reference.md) - Complete API documentation  
- [Examples](../examples/README.md) - Working examples to compare against