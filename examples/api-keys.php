<?php

/**
 * Example: API Keys and Third-Party Service Management
 *
 * This example demonstrates how to securely manage API keys, tokens, and
 * credentials for third-party services using Laravel Configrypt. It covers
 * various types of API authentication and integration patterns.
 *
 * Usage:
 * - Run: php examples/api-keys.php
 * - Shows encrypted API key configurations
 * - Demonstrates service integration patterns
 *
 * Requirements:
 * - Laravel Configrypt package
 * - CONFIGRYPT_KEY environment variable set
 */

require_once __DIR__ . '/../vendor/autoload.php';

use LaravelConfigrypt\Services\ConfigryptService;

echo "=== Laravel Configrypt API Keys Management Example ===\n\n";

// Setup encryption service
$encryptionKey = $_ENV['CONFIGRYPT_KEY'] ?? $_ENV['APP_KEY'] ?? 'example-key-32-characters-long--';
$service = new ConfigryptService(
    key: $encryptionKey,
    prefix: 'ENC:',
    cipher: 'AES-256-CBC'
);

echo "1. Common API Keys and Secrets:\n";
echo "==============================\n";

// Various types of API credentials commonly used in Laravel applications
$apiCredentials = [
    // Payment processors
    'stripe_secret_key' => 'sk_live_51234567890abcdefghijklmnopqrstuvwxyz',
    'stripe_webhook_secret' => 'whsec_1234567890abcdefghijklmnopqrstuvwxyz',
    'paypal_client_secret' => 'paypal-client-secret-here',

    // Email services
    'mailgun_api_key' => 'key-1234567890abcdef1234567890abcdef',
    'sendgrid_api_key' => 'SG.1234567890abcdef.1234567890abcdefghijklmnopqrstuvwxyz',
    'postmark_token' => '12345678-1234-1234-1234-123456789012',

    // Cloud services
    'aws_secret_key' => 'AWS-SECRET-ACCESS-KEY-1234567890ABCDEF',
    'aws_session_token' => 'temporary-session-token-here',
    'google_client_secret' => 'google-oauth-client-secret-here',
    'azure_client_secret' => 'azure-ad-client-secret-here',

    // Social media APIs
    'twitter_bearer_token' => 'twitter-bearer-token-here',
    'facebook_app_secret' => 'facebook-app-secret-here',
    'linkedin_client_secret' => 'linkedin-oauth-secret-here',

    // Other services
    'slack_webhook_url' => 'https://hooks.slack.com/services/SECRET/PATH/HERE',
    'twilio_auth_token' => 'twilio-auth-token-here',
    'pusher_app_secret' => 'pusher-app-secret-here',
    'algolia_admin_key' => 'algolia-admin-api-key-here',
];

echo "Encrypting API credentials:\n";
$encryptedCredentials = [];
foreach ($apiCredentials as $key => $credential) {
    $encrypted = $service->encrypt($credential);
    $encryptedCredentials[$key] = $encrypted;
    $displayKey = strtoupper($key);
    echo "{$displayKey}={$encrypted}\n";
}

echo "\n2. .env Configuration for API Services:\n";
echo "======================================\n";

echo "# Payment Processing\n";
echo "STRIPE_KEY=pk_live_your_publishable_key_here\n";
echo "STRIPE_SECRET={$encryptedCredentials['stripe_secret_key']}\n";
echo "STRIPE_WEBHOOK_SECRET={$encryptedCredentials['stripe_webhook_secret']}\n\n";

echo "PAYPAL_MODE=live\n";
echo "PAYPAL_CLIENT_ID=your_paypal_client_id\n";
echo "PAYPAL_CLIENT_SECRET={$encryptedCredentials['paypal_client_secret']}\n\n";

echo "# Email Services\n";
echo "MAILGUN_DOMAIN=mg.yourdomain.com\n";
echo "MAILGUN_SECRET={$encryptedCredentials['mailgun_api_key']}\n\n";

echo "SENDGRID_API_KEY={$encryptedCredentials['sendgrid_api_key']}\n\n";

echo "POSTMARK_TOKEN={$encryptedCredentials['postmark_token']}\n\n";

echo "# Cloud Services\n";
echo "AWS_ACCESS_KEY_ID=AKIA1234567890ABCDEF\n";
echo "AWS_SECRET_ACCESS_KEY={$encryptedCredentials['aws_secret_key']}\n";
echo "AWS_DEFAULT_REGION=us-east-1\n";
echo "AWS_BUCKET=your-s3-bucket\n\n";

echo "GOOGLE_CLIENT_ID=your-google-client-id.apps.googleusercontent.com\n";
echo "GOOGLE_CLIENT_SECRET={$encryptedCredentials['google_client_secret']}\n\n";

echo "# Social Media APIs\n";
echo "TWITTER_BEARER_TOKEN={$encryptedCredentials['twitter_bearer_token']}\n\n";

echo "FACEBOOK_APP_ID=your_facebook_app_id\n";
echo "FACEBOOK_APP_SECRET={$encryptedCredentials['facebook_app_secret']}\n\n";

echo "# Other Services\n";
echo "SLACK_WEBHOOK_URL={$encryptedCredentials['slack_webhook_url']}\n\n";

echo "TWILIO_SID=your_twilio_account_sid\n";
echo "TWILIO_AUTH_TOKEN={$encryptedCredentials['twilio_auth_token']}\n\n";

echo "PUSHER_APP_ID=your_pusher_app_id\n";
echo "PUSHER_APP_KEY=your_pusher_app_key\n";
echo "PUSHER_APP_SECRET={$encryptedCredentials['pusher_app_secret']}\n";
echo "PUSHER_APP_CLUSTER=mt1\n\n";

echo "3. Laravel Service Configuration (config/services.php):\n";
echo "======================================================\n";

echo "<?php\n\n";
echo "return [\n\n";

echo "    'stripe' => [\n";
echo "        'model' => App\\Models\\User::class,\n";
echo "        'key' => env('STRIPE_KEY'),\n";
echo "        'secret' => env('STRIPE_SECRET'), // Auto-decrypted\n";
echo "        'webhook' => [\n";
echo "            'secret' => env('STRIPE_WEBHOOK_SECRET'), // Auto-decrypted\n";
echo "            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),\n";
echo "        ],\n";
echo "    ],\n\n";

echo "    'paypal' => [\n";
echo "        'mode' => env('PAYPAL_MODE', 'sandbox'),\n";
echo "        'client_id' => env('PAYPAL_CLIENT_ID'),\n";
echo "        'client_secret' => env('PAYPAL_CLIENT_SECRET'), // Auto-decrypted\n";
echo "    ],\n\n";

echo "    'mailgun' => [\n";
echo "        'domain' => env('MAILGUN_DOMAIN'),\n";
echo "        'secret' => env('MAILGUN_SECRET'), // Auto-decrypted\n";
echo "        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),\n";
echo "    ],\n\n";

echo "    'sendgrid' => [\n";
echo "        'api_key' => env('SENDGRID_API_KEY'), // Auto-decrypted\n";
echo "    ],\n\n";

echo "    'postmark' => [\n";
echo "        'token' => env('POSTMARK_TOKEN'), // Auto-decrypted\n";
echo "    ],\n\n";

echo "    'ses' => [\n";
echo "        'key' => env('AWS_ACCESS_KEY_ID'),\n";
echo "        'secret' => env('AWS_SECRET_ACCESS_KEY'), // Auto-decrypted\n";
echo "        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),\n";
echo "    ],\n\n";

echo "    'google' => [\n";
echo "        'client_id' => env('GOOGLE_CLIENT_ID'),\n";
echo "        'client_secret' => env('GOOGLE_CLIENT_SECRET'), // Auto-decrypted\n";
echo "        'redirect' => env('GOOGLE_REDIRECT_URL'),\n";
echo "    ],\n\n";

echo "    'facebook' => [\n";
echo "        'client_id' => env('FACEBOOK_APP_ID'),\n";
echo "        'client_secret' => env('FACEBOOK_APP_SECRET'), // Auto-decrypted\n";
echo "        'redirect' => env('FACEBOOK_REDIRECT_URL'),\n";
echo "    ],\n\n";

echo "    'twitter' => [\n";
echo "        'bearer_token' => env('TWITTER_BEARER_TOKEN'), // Auto-decrypted\n";
echo "    ],\n\n";

echo "    'slack' => [\n";
echo "        'webhook_url' => env('SLACK_WEBHOOK_URL'), // Auto-decrypted\n";
echo "    ],\n\n";

echo "    'twilio' => [\n";
echo "        'sid' => env('TWILIO_SID'),\n";
echo "        'token' => env('TWILIO_AUTH_TOKEN'), // Auto-decrypted\n";
echo "        'from' => env('TWILIO_FROM'),\n";
echo "    ],\n\n";

echo "    'pusher' => [\n";
echo "        'app_id' => env('PUSHER_APP_ID'),\n";
echo "        'key' => env('PUSHER_APP_KEY'),\n";
echo "        'secret' => env('PUSHER_APP_SECRET'), // Auto-decrypted\n";
echo "        'cluster' => env('PUSHER_APP_CLUSTER'),\n";
echo "        'encrypted' => true,\n";
echo "    ],\n\n";

echo "    'algolia' => [\n";
echo "        'app_id' => env('ALGOLIA_APP_ID'),\n";
echo "        'secret' => env('ALGOLIA_SECRET'), // Auto-decrypted\n";
echo "    ],\n\n";

echo "];\n\n";

echo "4. Using API Services in Laravel Applications:\n";
echo "==============================================\n";

echo "// Payment processing with Stripe\n";
echo "class PaymentService\n";
echo "{\n";
echo "    private \$stripe;\n\n";
echo "    public function __construct()\n";
echo "    {\n";
echo "        \\Stripe\\Stripe::setApiKey(config('services.stripe.secret'));\n";
echo "        \$this->stripe = new \\Stripe\\StripeClient(config('services.stripe.secret'));\n";
echo "    }\n\n";
echo "    public function createPaymentIntent(\$amount, \$currency = 'usd')\n";
echo "    {\n";
echo "        return \$this->stripe->paymentIntents->create([\n";
echo "            'amount' => \$amount,\n";
echo "            'currency' => \$currency,\n";
echo "        ]);\n";
echo "    }\n";
echo "}\n\n";

echo "// Email service with SendGrid\n";
echo "class EmailService\n";
echo "{\n";
echo "    public function sendViaApi(\$to, \$subject, \$content)\n";
echo "    {\n";
echo "        \$email = new \\SendGrid\\Mail\\Mail();\n";
echo "        \$email->setFrom('sender@example.com', 'Your App');\n";
echo "        \$email->setSubject(\$subject);\n";
echo "        \$email->addTo(\$to);\n";
echo "        \$email->addContent('text/html', \$content);\n\n";
echo "        \$sendgrid = new \\SendGrid(config('services.sendgrid.api_key'));\n";
echo "        return \$sendgrid->send(\$email);\n";
echo "    }\n";
echo "}\n\n";

echo "// Cloud storage with AWS S3\n";
echo "class StorageService\n";
echo "{\n";
echo "    private \$s3Client;\n\n";
echo "    public function __construct()\n";
echo "    {\n";
echo "        \$this->s3Client = new Aws\\S3\\S3Client([\n";
echo "            'version' => 'latest',\n";
echo "            'region'  => config('services.ses.region'),\n";
echo "            'credentials' => [\n";
echo "                'key'    => config('services.ses.key'),\n";
echo "                'secret' => config('services.ses.secret'), // Auto-decrypted\n";
echo "            ],\n";
echo "        ]);\n";
echo "    }\n";
echo "}\n\n";

echo "5. Webhook Security and Verification:\n";
echo "====================================\n";

echo "// Stripe webhook verification\n";
echo "class StripeWebhookController extends Controller\n";
echo "{\n";
echo "    public function handleWebhook(Request \$request)\n";
echo "    {\n";
echo "        \$payload = \$request->getContent();\n";
echo "        \$sig_header = \$request->header('Stripe-Signature');\n";
echo "        \$endpoint_secret = config('services.stripe.webhook.secret');\n\n";
echo "        try {\n";
echo "            \$event = \\Stripe\\Webhook::constructEvent(\n";
echo "                \$payload, \$sig_header, \$endpoint_secret\n";
echo "            );\n";
echo "        } catch (\\UnexpectedValueException \$e) {\n";
echo "            return response('Invalid payload', 400);\n";
echo "        } catch (\\Stripe\\Exception\\SignatureVerificationException \$e) {\n";
echo "            return response('Invalid signature', 400);\n";
echo "        }\n\n";
echo "        // Handle the event\n";
echo "        return response('Webhook handled', 200);\n";
echo "    }\n";
echo "}\n\n";

echo "6. API Key Rotation Strategy:\n";
echo "============================\n";

echo "// Service for managing API key rotation\n";
echo "class ApiKeyRotationService\n";
echo "{\n";
echo "    public function rotateStripeKeys(\$newSecretKey, \$newWebhookSecret)\n";
echo "    {\n";
echo "        // Encrypt new keys\n";
echo "        \$encryptedSecret = app(ConfigryptService::class)->encrypt(\$newSecretKey);\n";
echo "        \$encryptedWebhook = app(ConfigryptService::class)->encrypt(\$newWebhookSecret);\n\n";
echo "        // Update environment file\n";
echo "        \$this->updateEnvironmentFile([\n";
echo "            'STRIPE_SECRET' => \$encryptedSecret,\n";
echo "            'STRIPE_WEBHOOK_SECRET' => \$encryptedWebhook,\n";
echo "        ]);\n\n";
echo "        // Clear config cache\n";
echo "        Artisan::call('config:clear');\n\n";
echo "        // Test new keys\n";
echo "        return \$this->testStripeConnection();\n";
echo "    }\n\n";
echo "    private function updateEnvironmentFile(array \$updates)\n";
echo "    {\n";
echo "        \$envFile = base_path('.env');\n";
echo "        \$content = file_get_contents(\$envFile);\n\n";
echo "        foreach (\$updates as \$key => \$value) {\n";
echo "            \$pattern = \"/^{\$key}=.*/m\";\n";
echo "            \$replacement = \"{\$key}={\$value}\";\n";
echo "            \$content = preg_replace(\$pattern, \$replacement, \$content);\n";
echo "        }\n\n";
echo "        file_put_contents(\$envFile, \$content);\n";
echo "    }\n";
echo "}\n\n";

echo "7. Environment-Specific API Configurations:\n";
echo "==========================================\n";

$environments = ['development', 'staging', 'production'];
foreach ($environments as $env) {
    echo "\n{$env} environment:\n";
    echo str_repeat('-', strlen($env) + 15) . "\n";

    // Different API keys for different environments
    $envKeys = [
        'development' => [
            'stripe' => 'sk_test_dev_key_here',
            'sendgrid' => 'SG.dev_key_here',
        ],
        'staging' => [
            'stripe' => 'sk_test_staging_key_here',
            'sendgrid' => 'SG.staging_key_here',
        ],
        'production' => [
            'stripe' => 'sk_live_production_key_here',
            'sendgrid' => 'SG.production_key_here',
        ],
    ];

    $stripeEncrypted = $service->encrypt($envKeys[$env]['stripe']);
    $sendgridEncrypted = $service->encrypt($envKeys[$env]['sendgrid']);

    echo "STRIPE_SECRET={$stripeEncrypted}\n";
    echo "SENDGRID_API_KEY={$sendgridEncrypted}\n";

    if ($env === 'production') {
        echo "# Production-specific security\n";
        echo "STRIPE_WEBHOOK_TOLERANCE=300\n";
        echo "API_RATE_LIMIT=1000\n";
    }
}

echo "\n8. API Security Best Practices:\n";
echo "==============================\n";

echo "✅ DO:\n";
echo "- Encrypt all API keys and secrets\n";
echo "- Use different keys for different environments\n";
echo "- Implement webhook signature verification\n";
echo "- Rotate API keys regularly\n";
echo "- Use least-privilege API permissions\n";
echo "- Monitor API usage and rate limits\n";
echo "- Log API errors (without exposing keys)\n";
echo "- Use HTTPS for all API communications\n\n";

echo "❌ DON'T:\n";
echo "- Store API keys in plain text\n";
echo "- Use production keys in development\n";
echo "- Expose API keys in client-side code\n";
echo "- Log API keys or responses with sensitive data\n";
echo "- Use the same keys across multiple applications\n";
echo "- Ignore API rate limits and quotas\n\n";

echo "9. Testing API Integrations:\n";
echo "===========================\n";

echo "// Test configuration for APIs\n";
echo "// tests/Feature/ApiIntegrationTest.php\n";
echo "class ApiIntegrationTest extends TestCase\n";
echo "{\n";
echo "    protected function setUp(): void\n";
echo "    {\n";
echo "        parent::setUp();\n\n";
echo "        // Use test API keys\n";
echo "        config([\n";
echo "            'services.stripe.secret' => 'sk_test_test_key',\n";
echo "            'services.sendgrid.api_key' => 'SG.test_key',\n";
echo "        ]);\n";
echo "    }\n\n";
echo "    public function test_stripe_payment_creation()\n";
echo "    {\n";
echo "        // Mock Stripe API calls\n";
echo "        Http::fake([\n";
echo "            'api.stripe.com/*' => Http::response([\n";
echo "                'id' => 'pi_test_payment_intent',\n";
echo "                'status' => 'requires_payment_method',\n";
echo "            ]),\n";
echo "        ]);\n\n";
echo "        // Test payment creation\n";
echo "        \$paymentService = new PaymentService();\n";
echo "        \$result = \$paymentService->createPaymentIntent(1000);\n\n";
echo "        \$this->assertEquals('pi_test_payment_intent', \$result['id']);\n";
echo "    }\n";
echo "}\n\n";

echo "10. Monitoring and Alerting:\n";
echo "===========================\n";

echo "// Monitor API key usage and failures\n";
echo "class ApiMonitoringService\n";
echo "{\n";
echo "    public function logApiCall(\$service, \$endpoint, \$response)\n";
echo "    {\n";
echo "        Log::info('API call', [\n";
echo "            'service' => \$service,\n";
echo "            'endpoint' => \$endpoint,\n";
echo "            'status' => \$response->status(),\n";
echo "            'duration' => \$response->transferStats->getTransferTime(),\n";
echo "        ]);\n\n";
echo "        if (\$response->failed()) {\n";
echo "            \$this->alertOnApiFailure(\$service, \$endpoint, \$response);\n";
echo "        }\n";
echo "    }\n\n";
echo "    private function alertOnApiFailure(\$service, \$endpoint, \$response)\n";
echo "    {\n";
echo "        // Send alert via Slack, email, etc.\n";
echo "        // Don't include API keys in alerts!\n";
echo "    }\n";
echo "}\n\n";

echo "=== Example Complete ===\n";
echo "Next: Try the multi-environment.php example for managing different configurations per environment\n";
