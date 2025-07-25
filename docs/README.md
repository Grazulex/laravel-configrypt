# Laravel Configrypt Documentation

Welcome to the Laravel Configrypt documentation. This package provides secure encryption and decryption of sensitive values in your Laravel `.env` files.

## Table of Contents

1. [Installation](installation.md)
2. [Configuration](configuration.md)  
3. [Basic Usage](basic-usage.md)
4. [Advanced Usage](advanced-usage.md)
5. [Artisan Commands](artisan-commands.md)
6. [API Reference](api-reference.md)
7. [Security Considerations](security.md)
8. [Troubleshooting](troubleshooting.md)
9. [Examples](../examples/README.md)

## Quick Start

Laravel Configrypt allows you to encrypt sensitive values directly in your `.env` file and automatically decrypt them at runtime.

### Basic Example

```bash
# Encrypt a value
php artisan configrypt:encrypt "my-secret-password"
# Output: ENC:gk9AvRZgx6Jyds7K2uFctw==
```

```env
# In your .env file
DB_PASSWORD=ENC:gk9AvRZgx6Jyds7K2uFctw==
```

```php
// In your Laravel application
$password = env('DB_PASSWORD'); // Automatically decrypted to "my-secret-password"
$password = config('database.connections.mysql.password'); // Also works with config
```

## Features

- 🔐 **Secure Encryption**: Uses AES-256-CBC encryption by default
- 🔓 **Transparent Decryption**: Automatic decryption at runtime
- 🔧 **Laravel Integration**: Seamless integration with Laravel's config system
- 🔑 **Flexible Keys**: Use dedicated encryption key or fallback to APP_KEY
- 📦 **Config Support**: Works with both `env()` and `config()` helpers
- 🛡️ **Secure by Default**: Decryption only happens in memory during runtime
- ⚙️ **Configurable**: Customize prefix, cipher, and auto-decryption behavior

## Support

- [GitHub Issues](https://github.com/Grazulex/laravel-configrypt/issues)
- [Discussions](https://github.com/Grazulex/laravel-configrypt/discussions)