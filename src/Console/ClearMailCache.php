<?php

namespace KolayBi\Validation\Mail\Console;

use Illuminate\Console\Command;
use KolayBi\Validation\Mail\Enums\ListType;
use KolayBi\Validation\Mail\Enums\ServiceType;
use KolayBi\Validation\Mail\Services\ExternalMailService;
use KolayBi\Validation\Mail\Services\LocalDomainService;
use Psr\SimpleCache\InvalidArgumentException;

class ClearMailCache extends Command
{
    protected $signature = 'mail-checker:cache-clear 
                           {--type= : Cache type to clear (local, external, or all)}
                           {--domain-type= : Domain cache type to clear (whitelist, blacklist, disposable)}';

    protected $description = 'Clear mail validation cache';

    /**
     * @throws InvalidArgumentException
     */
    public function handle(): int
    {
        $type = ServiceType::tryFrom($this->option('type')) ?? ServiceType::ALL;
        $domainType = ListType::tryFrom($this->option('domain-type'));

        $localDomainService = new LocalDomainService();
        $externalMailService = new ExternalMailService();

        switch ($type) {
            case ServiceType::LOCAL:
                $localDomainService->clearCache($domainType);
                break;
            case ServiceType::EXTERNAL:
                $externalMailService->clearCache();
                break;
            default:
                $localDomainService->clearCache();
                $externalMailService->clearCache();
                break;
        }

        return Command::SUCCESS;
    }
}
