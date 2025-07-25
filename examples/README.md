# Laravel Configrypt Examples

This directory contains practical examples demonstrating various use cases for Laravel Configrypt.

## Available Examples

### Basic Usage
- [Basic Encryption/Decryption](basic-usage.php) - Simple encrypt/decrypt operations with multiple approaches
- [Helper Functions & Str Macro](helper-functions.php) - Comprehensive guide to helper functions and Str macro
- [Auto-Decryption Feature](auto-decryption.php) - Advanced auto-decryption that bypasses Laravel's env cache
- [Environment Variables](environment-variables.php) - Working with .env files
- [Database Configuration](database-config.php) - Encrypting database credentials

### Advanced Usage  
- [API Keys Management](api-keys.php) - Managing third-party API keys
- [Multi-Environment Setup](multi-environment.php) - Different keys per environment
- [Batch Operations](batch-operations.php) - Bulk encryption/decryption
- [CI/CD Pipeline Integration](ci-cd-example.md) - GitHub Actions and GitLab CI/CD

### Real-World Scenarios
- [Laravel Application Setup](laravel-app/) - Complete Laravel app example
- [CI/CD Pipeline](ci-cd-example.yml) - GitHub Actions integration
- [Docker Setup](docker/) - Docker containerization with encrypted secrets
- [Testing Examples](testing/) - Test configurations and examples

### Utilities
- [Migration Script](migration-script.php) - Migrate from plain to encrypted values
- [Key Rotation](key-rotation.php) - Key rotation example
- [Validation Script](validation-script.php) - Validate encrypted environment variables

## Running Examples

### Prerequisites

1. **Install Laravel Configrypt**:
```bash
composer require grazulex/laravel-configrypt
```

2. **Set up encryption key**:
```bash
# Generate a key
php -r "echo base64_encode(random_bytes(32)) . \"\n\";"

# Add to .env
CONFIGRYPT_KEY=your-generated-key-here
```

3. **Publish configuration (optional)**:
```bash
php artisan vendor:publish --tag=configrypt-config
```

### Running Individual Examples

Each PHP example can be run directly:

```bash
# Basic usage example
php examples/basic-usage.php

# Helper functions example (requires Laravel context)
php artisan tinker
>>> require 'examples/helper-functions.php';

# Auto-decryption example (requires Laravel context)  
php artisan tinker
>>> require 'examples/auto-decryption.php';

# Database configuration example  
php examples/database-config.php

# API keys example
php examples/api-keys.php
```

### Laravel Application Example

The complete Laravel application example is in the `laravel-app/` directory:

```bash
cd examples/laravel-app
composer install
cp .env.example .env
php artisan key:generate
php artisan configrypt:encrypt "your-secret-value"
# Add encrypted value to .env
php artisan serve
```

## Example Categories

### 🔰 Beginner Examples
Perfect for getting started with Laravel Configrypt:
- **Basic encryption/decryption** - Understanding core concepts
- **Helper functions** - Using `configrypt_env()` and `encrypted_env()`
- **Str macro usage** - Easy migration with `Str::decryptEnv()`
- **Simple .env usage** - Basic environment variable encryption
- **Database configuration** - Practical database credential encryption

### 🔧 Intermediate Examples  
For developers implementing real-world scenarios:
- **Auto-decryption feature** - Advanced automatic decryption
- **API key management** - Managing third-party service credentials
- **Multi-environment setup** - Different configurations per environment
- **Custom service integration** - Integrating with existing services
- **Migration strategies** - Moving from plain to encrypted values

### 🚀 Advanced Examples
For complex implementations and enterprise usage:
- **Batch operations** - Bulk encryption and decryption
- **CI/CD integration** - Automated pipeline setup
- **Key rotation strategies** - Secure key management
- **Performance optimization** - Efficient usage patterns
- **Custom encryption contexts** - Multiple encryption schemes

### 🧪 Testing Examples
For testing encrypted configurations:
- Unit test examples
- Integration test setup
- Mock configurations

## Best Practices Demonstrated

These examples demonstrate:

1. **Security Best Practices**
   - Proper key management
   - Environment separation
   - Error handling

2. **Performance Optimization**
   - Caching strategies
   - Lazy loading
   - Batch operations

3. **Development Workflow**
   - Local development setup
   - Testing configurations
   - Deployment strategies

4. **Integration Patterns**
   - Service container usage
   - Facade patterns
   - Dependency injection

## Contributing Examples

To contribute a new example:

1. Create a new PHP file in the appropriate directory
2. Include comprehensive comments explaining the example
3. Add error handling and best practices
4. Update this README with the new example
5. Test the example thoroughly

### Example Template

```php
<?php
/**
 * Example: [Brief Description]
 * 
 * This example demonstrates [detailed description of what it shows].
 * 
 * Usage:
 * - [Step 1]
 * - [Step 2]
 * - [Step 3]
 * 
 * Requirements:
 * - Laravel Configrypt installed
 * - Encryption key configured
 * - [Any other requirements]
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Example code here...
```

## Common Issues

### Running Examples Outside Laravel

Some examples require Laravel application context. For those examples:

**Method 1: Use Laravel's artisan tinker (recommended)**
```bash
php artisan tinker
>>> require 'examples/helper-functions.php';
>>> require 'examples/auto-decryption.php';
```

**Method 2: Create a test route (for web testing)**
```php
// routes/web.php
Route::get('/test-configrypt', function () {
    require base_path('examples/helper-functions.php');
});
```

**Method 3: Create an artisan command**
```bash
php artisan make:command TestConfigrypt
# Then add require statements in the handle() method
php artisan test:configrypt
```

### Missing Dependencies

If you get class not found errors:

```bash
# Ensure autoloader is included
composer dump-autoload
```

### Configuration Issues

Make sure your encryption key is set:

```bash
# Check if key is configured
php artisan tinker
>>> config('configrypt.key')
```

## Support

- [Documentation](../docs/README.md) - Full documentation
- [Troubleshooting](../docs/troubleshooting.md) - Common issues
- [Security Guide](../docs/security.md) - Security best practices
- [GitHub Issues](https://github.com/Grazulex/laravel-configrypt/issues) - Report bugs or request features