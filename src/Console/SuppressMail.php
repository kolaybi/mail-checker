<?php

namespace KolayBi\Validation\Mail\Console;

use Illuminate\Console\Command;
use KolayBi\Validation\Mail\Enums\SuppressionReason;
use KolayBi\Validation\Mail\MailChecker;

class SuppressMail extends Command
{
    protected $signature = 'mail-checker:suppress
                           {email : Address to suppress}
                           {--reason=manual : bounce, complaint or manual}
                           {--source=manual : Provenance stored with the record}';

    protected $description = 'Add an email address to the suppression list';

    public function handle(): int
    {
        $reason = SuppressionReason::tryFrom(strtolower((string) $this->option('reason')));

        if (null === $reason) {
            $this->error('Invalid reason. Use one of: bounce, complaint, manual.');

            return Command::FAILURE;
        }

        MailChecker::suppress($this->argument('email'), $reason, $this->option('source'));

        $this->info("Suppressed {$this->argument('email')} ({$reason->value}).");

        return Command::SUCCESS;
    }
}
