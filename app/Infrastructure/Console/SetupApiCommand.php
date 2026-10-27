<?php

namespace App\Infrastructure\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Laravel\Passport\ClientRepository;
use RuntimeException;

#[Signature('api:setup')]
#[Description('Prepare OAuth 2.0 prerequisites: Passport encryption keys and the personal access client (idempotent)')]
class SetupApiCommand extends Command
{
    public function handle(ClientRepository $clients): int
    {
        if (! file_exists(storage_path('oauth-private.key')) || ! file_exists(storage_path('oauth-public.key'))) {
            $this->call('passport:keys', ['--force' => true]);
        }

        try {
            $clients->personalAccessClient('users');
            $this->components->info('Personal access client already exists.');
        } catch (RuntimeException) {
            $clients->createPersonalAccessGrantClient('Personal Access Client', 'users');
            $this->components->info('Personal access client created.');
        }

        return self::SUCCESS;
    }
}
