# Installation

This guide will help you install and set up Laravel Configrypt in your Laravel application.

## Requirements

- PHP 8.3 or higher
- Laravel 12.19 or higher
- Composer

## Installation

### 1. Install via Composer

```bash
composer require grazulex/laravel-configrypt
```

### 2. Publish Configuration (Optional)

Laravel Configrypt works out of the box with sensible defaults, but you can publish the configuration file to customize the settings:

```bash
php artisan vendor:publish --tag=configrypt-config
```

This will create a `config/configrypt.php` file in your application.

### 3. Set Up Encryption Key

Laravel Configrypt can use your existing `APP_KEY` or a dedicated `CONFIGRYPT_KEY`. Add to your `.env` file:

```env
# Option 1: Use a dedicated key (recommended)
CONFIGRYPT_KEY=your-32-character-encryption-key-here

# Option 2: Will fallback to APP_KEY if CONFIGRYPT_KEY is not set
APP_KEY=base64:your-laravel-app-key-here
```

### 4. Enable Auto-Decryption (Recommended)

For seamless integration with existing Laravel code, enable auto-decryption:

```env
# Add to your .env file
CONFIGRYPT_AUTO_DECRYPT=true
```

With auto-decryption enabled:
- Encrypted environment variables are automatically decrypted during bootstrap
- Your existing `env()` and `config()` calls work normally
- No code changes needed for basic usage

### 5. Verify Installation

Test that the installation is working:

```bash
php artisan configrypt:encrypt "test-value"
```

You should see output like:
```
Encrypted value:
ENC:AbCdEfGhIjKlMnOpQrStUvWxYz==

You can now use this encrypted value in your .env file:
SOME_SECRET=ENC:AbCdEfGhIjKlMnOpQrStUvWxYz==
```

### 6. Test Auto-Decryption (If Enabled)

Add the encrypted value to your `.env` file and test it:

```env
TEST_SECRET=ENC:AbCdEfGhIjKlMnOpQrStUvWxYz==
```

Test in artisan tinker:
```bash
php artisan tinker
>>> env('TEST_SECRET')  // Should return "test-value"
>>> configrypt_env('TEST_SECRET')  // Also returns "test-value"
```

## Package Auto-Discovery

Laravel Configrypt uses Laravel's package auto-discovery feature, so the service provider will be automatically registered. The package provides:

- **ConfigryptService**: Main encryption/decryption service
- **EnvironmentDecryptor**: Environment variable handling service  
- **Helper functions**: `configrypt_env()` and `encrypted_env()`
- **Str macro**: `Str::decryptEnv()` for easy migration
- **Artisan commands**: `configrypt:encrypt` and `configrypt:decrypt`
- **Auto-decryption**: Optional automatic environment decryption

No manual registration is required for Laravel 12.19+.

## Manual Registration (Laravel < 12.19)

If you're using an older version of Laravel that doesn't support package auto-discovery, add the service provider to your `config/app.php`:

```php
'providers' => [
    // Other service providers...
    LaravelConfigrypt\LaravelConfigryptServiceProvider::class,
],
```

And optionally add the facades:

```php
'aliases' => [
    // Other aliases...
    'Configrypt' => LaravelConfigrypt\Facades\Configrypt::class,
    'ConfigryptEnv' => LaravelConfigrypt\Facades\ConfigryptEnv::class,
],
```

## Configuration Options

After installation, you can customize behavior via environment variables:

```env
# Enable/disable auto-decryption
CONFIGRYPT_AUTO_DECRYPT=true

# Custom encryption prefix
CONFIGRYPT_PREFIX=ENC:

# Encryption cipher method
CONFIGRYPT_CIPHER=AES-256-CBC

# Dedicated encryption key
CONFIGRYPT_KEY=your-32-character-encryption-key-here
```

## Multiple Usage Patterns

Laravel Configrypt supports multiple usage patterns depending on your needs:

### Pattern 1: Auto-Decryption (Easiest)
```env
CONFIGRYPT_AUTO_DECRYPT=true
DB_PASSWORD=ENC:encrypted-password
```
```php
$password = env('DB_PASSWORD'); // Returns decrypted value automatically
```

### Pattern 2: Helper Functions (Explicit)
```php
$password = configrypt_env('DB_PASSWORD');  // Always works
$secret = encrypted_env('API_SECRET');      // Alias for configrypt_env()
```

### Pattern 3: Str Macro (Migration-Friendly)
```php
use Illuminate\Support\Str;
$password = Str::decryptEnv('DB_PASSWORD'); // Easy search & replace
```

### Pattern 4: Facades (Advanced)
```php
use LaravelConfigrypt\Facades\ConfigryptEnv;
$password = ConfigryptEnv::get('DB_PASSWORD');
```

## Next Steps

- [Configuration](configuration.md) - Learn how to configure Laravel Configrypt
- [Basic Usage](basic-usage.md) - Start encrypting your environment variables
- [Examples](../examples/README.md) - See practical examples