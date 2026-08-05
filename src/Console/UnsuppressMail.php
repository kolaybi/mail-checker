<?php

namespace KolayBi\Validation\Mail\Console;

use Illuminate\Console\Command;
use KolayBi\Validation\Mail\MailChecker;

class UnsuppressMail extends Command
{
    protected $signature = 'mail-checker:unsuppress {email : Address to remove from the suppression list}';

    protected $description = 'Remove an email address from the suppression list (and the upstream ESP list when a sync is bound)';

    public function handle(): int
    {
        $removed = MailChecker::unsuppress($this->argument('email'));

        $this->info($removed
            ? "Unsuppressed {$this->argument('email')}."
            : "{$this->argument('email')} was not on the suppression list.");

        return Command::SUCCESS;
    }
}
