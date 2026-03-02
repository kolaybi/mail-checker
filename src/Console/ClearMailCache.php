<?php

namespace KolayBi\Validation\Mail\Console;

use Illuminate\Console\Command;
use KolayBi\Validation\Mail\Enums\ServiceType;
use KolayBi\Validation\Mail\Services\ExternalMailService;
use KolayBi\Validation\Mail\Services\LocalDomainService;

class ClearMailCache extends Command
{
    protected $signature = 'mail-checker:cache-clear
                           {--type= : Cache type to clear (local, external, or all)}';

    protected $description = 'Clear mail validation cache';

    public function handle(): int
    {
        $type = ServiceType::tryFrom($this->option('type')) ?? ServiceType::ALL;

        $localDomainService = new LocalDomainService();
        $externalMailService = new ExternalMailService();

        switch ($type) {
            case ServiceType::LOCAL:
                $localDomainService->clearCache();
                break;
            case ServiceType::EXTERNAL:
                $externalMailService->clearCache();
                break;
            default:
                $localDomainService->clearCache();
                $externalMailService->clearCache();
                break;
        }

        $this->info("Mail checker {$type->value} cache cleared.");

        return Command::SUCCESS;
    }
}
