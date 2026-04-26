<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class CreateTestTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:create-test {name=Test Tenant} {email=test@example.com}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a minimal test tenant and print its assigned version.';

    public function handle()
    {
        $name = $this->argument('name');
        $email = $this->argument('email');

        $this->info("Creating tenant: {$name} ({$email})");

        try {
            $tenant = Tenant::create([
                'name' => $name,
                'email' => $email,
            ]);

            $this->info('Tenant created successfully.');
            $this->line('ID: ' . $tenant->id);
            $this->line('Version: ' . ($tenant->version ?? 'NULL'));
        } catch (\Throwable $e) {
            $this->error('Failed to create tenant: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
