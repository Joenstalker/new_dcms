<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {email}';
    protected $description = 'Send a test email to verify SMTP configuration';

    public function handle()
    {
        $email = $this->argument('email');
        $this->info("Attempting to send a test email to: {$email}");

        try {
            \Illuminate\Support\Facades\Mail::raw('This is a test email from DCMS to verify SMTP settings.', function ($message) use ($email) {
                $message->to($email)
                    ->subject('DCMS SMTP Test Email');
            });

            $this->info('Success! The email was sent successfully.');
        } catch (\Exception $e) {
            $this->error('Failed! Error: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('SMTP Test Failed: ' . $e->getMessage());
        }

        return 0;
    }
}
