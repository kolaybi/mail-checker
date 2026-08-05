<?php

namespace KolayBi\Validation\Mail\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use KolayBi\Validation\Mail\Enums\SuppressionReason;
use KolayBi\Validation\Mail\MailChecker;
use Throwable;

class ImportSuppressions extends Command
{
    protected $signature = 'mail-checker:suppression-import
                           {path : CSV file with header email,reason,suppressed_at}
                           {--source=import : Provenance stored with each record}';

    protected $description = 'Bulk-import suppressions from a CSV file';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (!is_readable($path)) {
            $this->error("Cannot read {$path}.");

            return Command::FAILURE;
        }

        $handle = fopen($path, 'rb');
        fgetcsv($handle); // header

        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $email = trim((string) ($row[0] ?? ''));
            $reason = SuppressionReason::tryFrom(strtolower(trim((string) ($row[1] ?? ''))));

            if ('' === $email || null === $reason) {
                $skipped++;

                continue;
            }

            try {
                $suppressedAt = filled($row[2] ?? null) ? Carbon::parse($row[2]) : null;
                MailChecker::suppress($email, $reason, $this->option('source'), $suppressedAt);
                $imported++;
            } catch (Throwable $exception) {
                $this->warn("Skipped {$email}: {$exception->getMessage()}");
                $skipped++;
            }
        }

        fclose($handle);

        $this->info("Imported {$imported} suppressions, skipped {$skipped} rows.");

        return Command::SUCCESS;
    }
}
