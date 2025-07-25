<?php

declare(strict_types=1);

namespace LaravelConfigrypt\Commands;

use Illuminate\Console\Command;
use LaravelConfigrypt\Services\ConfigryptService;

class EncryptCommand extends Command
{
    protected $signature = 'configrypt:encrypt {value : The value to encrypt}';
    protected $description = 'Encrypt a value for use in .env files';

    public function handle(ConfigryptService $configrypt): int
    {
        $value = $this->argument('value');

        if (empty($value)) {
            $this->error('Value cannot be empty.');
            return self::FAILURE;
        }

        try {
            $encrypted = $configrypt->encrypt($value);
            
            $this->info('Encrypted value:');
            $this->line($encrypted);
            $this->newLine();
            $this->comment('You can now use this encrypted value in your .env file:');
            $this->line("SOME_SECRET={$encrypted}");
            
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Encryption failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
