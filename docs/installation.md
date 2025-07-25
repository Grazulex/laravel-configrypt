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

### 4. Verify Installation

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

## Package Auto-Discovery

Laravel Configrypt uses Laravel's package auto-discovery feature, so the service provider will be automatically registered. No manual registration is required.

## Manual Registration (Laravel < 5.5)

If you're using an older version of Laravel that doesn't support package auto-discovery, add the service provider to your `config/app.php`:

```php
'providers' => [
    // Other service providers...
    LaravelConfigrypt\LaravelConfigryptServiceProvider::class,
],
```

And optionally add the facade:

```php
'aliases' => [
    // Other aliases...
    'Configrypt' => LaravelConfigrypt\Facades\Configrypt::class,
],
```

## Next Steps

- [Configuration](configuration.md) - Learn how to configure Laravel Configrypt
- [Basic Usage](basic-usage.md) - Start encrypting your environment variables
- [Examples](../examples/README.md) - See practical examples