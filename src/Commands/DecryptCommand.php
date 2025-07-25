<?php

declare(strict_types=1);

namespace LaravelConfigrypt\Commands;

use Exception;
use Illuminate\Console\Command;
use LaravelConfigrypt\Services\ConfigryptService;

class DecryptCommand extends Command
{
    protected $signature = 'configrypt:decrypt {value : The encrypted value to decrypt}';

    protected $description = 'Decrypt an encrypted value';

    public function handle(ConfigryptService $configrypt): int
    {
        $encryptedValue = $this->argument('value');

        if (empty($encryptedValue)) {
            $this->error('Encrypted value cannot be empty.');

            return self::FAILURE;
        }

        try {
            $decrypted = $configrypt->decrypt($encryptedValue);

            $this->info('Decrypted value:');
            $this->line($decrypted);

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error('Decryption failed: ' . $e->getMessage());
            $this->comment('Make sure the value is properly encrypted and you have the correct encryption key.');

            return self::FAILURE;
        }
    }
}
