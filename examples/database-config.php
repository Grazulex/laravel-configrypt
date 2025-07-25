<?php
/**
 * Example: Database Configuration with Encrypted Credentials
 * 
 * This example demonstrates how to encrypt database credentials and use them
 * in Laravel's database configuration. It covers multiple database connections,
 * different database types, and best practices for database security.
 * 
 * Usage:
 * - Run: php examples/database-config.php
 * - Shows encrypted database configurations
 * - Demonstrates multiple connection setups
 * 
 * Requirements:
 * - Laravel Configrypt package
 * - CONFIGRYPT_KEY environment variable set
 */

require_once __DIR__ . '/../vendor/autoload.php';

use LaravelConfigrypt\Services\ConfigryptService;

echo "=== Laravel Configrypt Database Configuration Example ===\n\n";

// Setup encryption service
$encryptionKey = $_ENV['CONFIGRYPT_KEY'] ?? $_ENV['APP_KEY'] ?? 'example-key-32-characters-long--';
$service = new ConfigryptService(
    key: $encryptionKey,
    prefix: 'ENC:',
    cipher: 'AES-256-CBC'
);

echo "1. Basic Database Configuration:\n";
echo "===============================\n";

// Database credentials to encrypt
$dbCredentials = [
    'primary_password' => 'super-secret-primary-db-password',
    'readonly_password' => 'readonly-user-password',
    'analytics_password' => 'analytics-db-password',
    'cache_password' => 'redis-cache-password',
    'session_password' => 'redis-session-password',
];

echo "Encrypting database credentials:\n";
$encryptedCredentials = [];
foreach ($dbCredentials as $key => $password) {
    $encrypted = $service->encrypt($password);
    $encryptedCredentials[$key] = $encrypted;
    echo "{$key}: {$encrypted}\n";
}

echo "\n2. .env Configuration for Databases:\n";
echo "===================================\n";

echo "# Primary Database (MySQL)\n";
echo "DB_CONNECTION=mysql\n";
echo "DB_HOST=mysql.example.com\n";
echo "DB_PORT=3306\n";
echo "DB_DATABASE=laravel_production\n";
echo "DB_USERNAME=laravel_user\n";
echo "DB_PASSWORD={$encryptedCredentials['primary_password']}\n\n";

echo "# Read-Only Database Replica\n";
echo "DB_READONLY_HOST=mysql-readonly.example.com\n";
echo "DB_READONLY_PORT=3306\n";
echo "DB_READONLY_DATABASE=laravel_production\n";
echo "DB_READONLY_USERNAME=readonly_user\n";
echo "DB_READONLY_PASSWORD={$encryptedCredentials['readonly_password']}\n\n";

echo "# Analytics Database (PostgreSQL)\n";
echo "DB_ANALYTICS_CONNECTION=pgsql\n";
echo "DB_ANALYTICS_HOST=postgres.example.com\n";
echo "DB_ANALYTICS_PORT=5432\n";
echo "DB_ANALYTICS_DATABASE=analytics\n";
echo "DB_ANALYTICS_USERNAME=analytics_user\n";
echo "DB_ANALYTICS_PASSWORD={$encryptedCredentials['analytics_password']}\n\n";

echo "# Redis Cache\n";
echo "REDIS_CACHE_HOST=redis-cache.example.com\n";
echo "REDIS_CACHE_PORT=6379\n";
echo "REDIS_CACHE_PASSWORD={$encryptedCredentials['cache_password']}\n\n";

echo "# Redis Sessions\n";
echo "REDIS_SESSION_HOST=redis-session.example.com\n";
echo "REDIS_SESSION_PORT=6379\n";
echo "REDIS_SESSION_PASSWORD={$encryptedCredentials['session_password']}\n\n";

echo "3. Laravel Database Configuration (config/database.php):\n";
echo "=======================================================\n";

echo "<?php\n\n";
echo "return [\n\n";
echo "    'default' => env('DB_CONNECTION', 'mysql'),\n\n";
echo "    'connections' => [\n\n";

echo "        // Primary MySQL database\n";
echo "        'mysql' => [\n";
echo "            'driver' => 'mysql',\n";
echo "            'url' => env('DATABASE_URL'),\n";
echo "            'host' => env('DB_HOST', '127.0.0.1'),\n";
echo "            'port' => env('DB_PORT', '3306'),\n";
echo "            'database' => env('DB_DATABASE', 'forge'),\n";
echo "            'username' => env('DB_USERNAME', 'forge'),\n";
echo "            'password' => env('DB_PASSWORD', ''), // Auto-decrypted\n";
echo "            'unix_socket' => env('DB_SOCKET', ''),\n";
echo "            'charset' => 'utf8mb4',\n";
echo "            'collation' => 'utf8mb4_unicode_ci',\n";
echo "            'prefix' => '',\n";
echo "            'prefix_indexes' => true,\n";
echo "            'strict' => true,\n";
echo "            'engine' => null,\n";
echo "            'options' => extension_loaded('pdo_mysql') ? array_filter([\n";
echo "                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),\n";
echo "            ]) : [],\n";
echo "        ],\n\n";

echo "        // Read-only MySQL replica\n";
echo "        'mysql_readonly' => [\n";
echo "            'driver' => 'mysql',\n";
echo "            'host' => env('DB_READONLY_HOST', '127.0.0.1'),\n";
echo "            'port' => env('DB_READONLY_PORT', '3306'),\n";
echo "            'database' => env('DB_READONLY_DATABASE', 'forge'),\n";
echo "            'username' => env('DB_READONLY_USERNAME', 'forge'),\n";
echo "            'password' => env('DB_READONLY_PASSWORD', ''), // Auto-decrypted\n";
echo "            'unix_socket' => env('DB_SOCKET', ''),\n";
echo "            'charset' => 'utf8mb4',\n";
echo "            'collation' => 'utf8mb4_unicode_ci',\n";
echo "            'prefix' => '',\n";
echo "            'prefix_indexes' => true,\n";
echo "            'strict' => true,\n";
echo "            'engine' => null,\n";
echo "        ],\n\n";

echo "        // PostgreSQL for analytics\n";
echo "        'pgsql_analytics' => [\n";
echo "            'driver' => 'pgsql',\n";
echo "            'host' => env('DB_ANALYTICS_HOST', '127.0.0.1'),\n";
echo "            'port' => env('DB_ANALYTICS_PORT', '5432'),\n";
echo "            'database' => env('DB_ANALYTICS_DATABASE', 'forge'),\n";
echo "            'username' => env('DB_ANALYTICS_USERNAME', 'forge'),\n";
echo "            'password' => env('DB_ANALYTICS_PASSWORD', ''), // Auto-decrypted\n";
echo "            'charset' => 'utf8',\n";
echo "            'prefix' => '',\n";
echo "            'prefix_indexes' => true,\n";
echo "            'schema' => 'public',\n";
echo "            'sslmode' => 'prefer',\n";
echo "        ],\n\n";

echo "    ],\n\n";

echo "    // Redis configuration\n";
echo "    'redis' => [\n";
echo "        'client' => env('REDIS_CLIENT', 'phpredis'),\n\n";
echo "        'options' => [\n";
echo "            'cluster' => env('REDIS_CLUSTER', 'redis'),\n";
echo "            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),\n";
echo "        ],\n\n";
echo "        'default' => [\n";
echo "            'url' => env('REDIS_URL'),\n";
echo "            'host' => env('REDIS_HOST', '127.0.0.1'),\n";
echo "            'password' => env('REDIS_PASSWORD'), // Auto-decrypted\n";
echo "            'port' => env('REDIS_PORT', '6379'),\n";
echo "            'database' => env('REDIS_DB', '0'),\n";
echo "        ],\n\n";
echo "        'cache' => [\n";
echo "            'url' => env('REDIS_CACHE_URL'),\n";
echo "            'host' => env('REDIS_CACHE_HOST', '127.0.0.1'),\n";
echo "            'password' => env('REDIS_CACHE_PASSWORD'), // Auto-decrypted\n";
echo "            'port' => env('REDIS_CACHE_PORT', '6379'),\n";
echo "            'database' => env('REDIS_CACHE_DB', '1'),\n";
echo "        ],\n\n";
echo "        'session' => [\n";
echo "            'url' => env('REDIS_SESSION_URL'),\n";
echo "            'host' => env('REDIS_SESSION_HOST', '127.0.0.1'),\n";
echo "            'password' => env('REDIS_SESSION_PASSWORD'), // Auto-decrypted\n";
echo "            'port' => env('REDIS_SESSION_PORT', '6379'),\n";
echo "            'database' => env('REDIS_SESSION_DB', '2'),\n";
echo "        ],\n";
echo "    ],\n";
echo "];\n\n";

echo "4. Using Multiple Database Connections in Models:\n";
echo "================================================\n";

echo "// Primary database model\n";
echo "class User extends Model\n";
echo "{\n";
echo "    protected \$connection = 'mysql'; // Uses primary database\n";
echo "}\n\n";

echo "// Read-only model for reporting\n";
echo "class UserReport extends Model\n";
echo "{\n";
echo "    protected \$connection = 'mysql_readonly';\n";
echo "    protected \$table = 'users';\n";
echo "}\n\n";

echo "// Analytics model\n";
echo "class AnalyticsEvent extends Model\n";
echo "{\n";
echo "    protected \$connection = 'pgsql_analytics';\n";
echo "}\n\n";

echo "5. Database Usage Examples:\n";
echo "==========================\n";

echo "// Using specific database connections\n";
echo "use Illuminate\\Support\\Facades\\DB;\n\n";

echo "// Query primary database\n";
echo "\$users = DB::connection('mysql')->table('users')->get();\n\n";

echo "// Query read-only replica (for reports)\n";
echo "\$userCount = DB::connection('mysql_readonly')\n";
echo "    ->table('users')\n";
echo "    ->where('created_at', '>=', now()->subMonth())\n";
echo "    ->count();\n\n";

echo "// Query analytics database\n";
echo "\$events = DB::connection('pgsql_analytics')\n";
echo "    ->table('events')\n";
echo "    ->where('event_type', 'page_view')\n";
echo "    ->get();\n\n";

echo "6. Environment-Specific Database Configurations:\n";
echo "===============================================\n";

// Generate different encrypted passwords for different environments
$environments = ['development', 'staging', 'production'];
foreach ($environments as $env) {
    echo "\n{$env} environment (.env.{$env}):\n";
    echo str_repeat('-', strlen($env) + 25) . "\n";
    
    // Different passwords for each environment
    $envPasswords = [
        'development' => 'dev-db-password-' . $env,
        'staging' => 'staging-db-password-' . $env,
        'production' => 'prod-db-password-' . $env,
    ];
    
    $envEncrypted = $service->encrypt($envPasswords[$env]);
    
    echo "DB_HOST={$env}-mysql.example.com\n";
    echo "DB_DATABASE=laravel_{$env}\n";
    echo "DB_USERNAME=laravel_{$env}_user\n";
    echo "DB_PASSWORD={$envEncrypted}\n";
    
    if ($env === 'production') {
        echo "# Additional production security\n";
        echo "DB_SSL_MODE=REQUIRED\n";
        echo "DB_SSL_CA=/path/to/ca-cert.pem\n";
        echo "DB_SSL_CERT=/path/to/client-cert.pem\n";
        echo "DB_SSL_KEY=/path/to/client-key.pem\n";
    }
}

echo "\n7. Database Security Best Practices:\n";
echo "===================================\n";

echo "✅ DO:\n";
echo "- Encrypt all database passwords\n";
echo "- Use different credentials per environment\n";
echo "- Use read-only connections for reporting\n";
echo "- Enable SSL/TLS for database connections\n";
echo "- Use least-privilege database users\n";
echo "- Rotate database passwords regularly\n";
echo "- Monitor database access and queries\n\n";

echo "❌ DON'T:\n";
echo "- Use the same password across environments\n";
echo "- Store database passwords in plain text\n";
echo "- Use root/admin accounts for application connections\n";
echo "- Expose database credentials in logs\n";
echo "- Use weak or default passwords\n\n";

echo "8. Connection Testing:\n";
echo "=====================\n";

// Simulate database connection testing
echo "Testing database connections with encrypted credentials...\n\n";

// Mock connection test function
function testDatabaseConnection($connectionName, $host, $username, $encryptedPassword, $service) {
    try {
        $decryptedPassword = $service->decrypt($encryptedPassword);
        
        // In a real application, this would attempt actual database connection
        echo "✓ {$connectionName}: Connection test successful\n";
        echo "  Host: {$host}\n";
        echo "  Username: {$username}\n";
        echo "  Password: " . str_repeat('*', strlen($decryptedPassword)) . " (decrypted successfully)\n\n";
        
        return true;
    } catch (Exception $e) {
        echo "✗ {$connectionName}: Connection test failed\n";
        echo "  Error: " . $e->getMessage() . "\n\n";
        return false;
    }
}

// Test connections
$connections = [
    'Primary MySQL' => ['mysql.example.com', 'laravel_user', $encryptedCredentials['primary_password']],
    'Readonly MySQL' => ['mysql-readonly.example.com', 'readonly_user', $encryptedCredentials['readonly_password']],
    'Analytics PostgreSQL' => ['postgres.example.com', 'analytics_user', $encryptedCredentials['analytics_password']],
];

foreach ($connections as $name => $config) {
    testDatabaseConnection($name, $config[0], $config[1], $config[2], $service);
}

echo "9. Troubleshooting Database Connections:\n";
echo "=======================================\n";

echo "Common issues and solutions:\n\n";

echo "Issue: Connection refused\n";
echo "- Check if database host is correct\n";
echo "- Verify port is open and accessible\n";
echo "- Ensure database service is running\n\n";

echo "Issue: Access denied for user\n";
echo "- Verify username is correct\n";
echo "- Check if password decrypts correctly\n";
echo "- Ensure user has necessary permissions\n\n";

echo "Issue: SSL connection error\n";
echo "- Verify SSL certificates are valid\n";
echo "- Check SSL configuration in database.php\n";
echo "- Ensure database server supports SSL\n\n";

echo "Issue: Auto-decryption not working\n";
echo "- Verify CONFIGRYPT_AUTO_DECRYPT=true\n";
echo "- Check if password has ENC: prefix\n";
echo "- Ensure encryption key is correct\n\n";

echo "Debug commands:\n";
echo "php artisan configrypt:decrypt \"\$DB_PASSWORD\"\n";
echo "php artisan tinker\n";
echo ">>> config('database.connections.mysql.password')\n";
echo ">>> DB::connection('mysql')->getPdo()\n\n";

echo "=== Example Complete ===\n";
echo "Next: Try the api-keys.php example for managing third-party API credentials\n";