<?php

namespace KolayBi\Validation\Mail\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

class ExternalMailService
{
    private array $config;

    public function __construct()
    {
        $this->config = Config::get('mail-checker.external');
    }

    public function getProviders(): array
    {
        $priorityOrder = Arr::get($this->config, 'priority', []);
        $allProviders = Arr::get($this->config, 'providers', []);

        // Return resolver class names in priority order
        $providers = [];
        foreach ($priorityOrder as $providerName) {
            if (isset($allProviders[$providerName]['resolver']) && !empty($allProviders[$providerName]['resolver'])) {
                $providers[] = $allProviders[$providerName]['resolver'];
            }
        }

        return $providers;
    }
}
