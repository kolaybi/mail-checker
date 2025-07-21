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

        return collect(Arr::only($allProviders, $priorityOrder))
            ->mapWithKeys(fn($provider) => [
                Arr::get($provider, 'resolver') => Arr::get($provider, 'config', []),
            ])
            ->toArray();
    }

    public function createProvider(string $providerClass, array $providerConfig): ExternalMailProviderInterface
    {
        return new $providerClass($providerConfig);
    }
}
