<?php

namespace KolayBi\Validation\Mail\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use KolayBi\Validation\Mail\Enums\ServiceType;
use KolayBi\Validation\Mail\Exceptions\AbstractMailException;
use KolayBi\Validation\Mail\Exceptions\ExternalMailProviderException;
use KolayBi\Validation\Mail\Exceptions\InaccessibleMailException;
use KolayBi\Validation\Mail\Services\Providers\ExternalMailProviderInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;

class ExternalMailService
{
    private array $config;

    private CacheService $cacheService;

    public function __construct()
    {
        $this->config = Config::get('mail-checker.external');
        $this->cacheService = new CacheService(
            Arr::get($this->config, 'cache.ttl'),
            Arr::get($this->config, 'cache.enabled'),
            Arr::get($this->config, 'cache.store'),
        );
    }

    /**
     * Check the mail's deliverability against external services
     *
     * @throws AbstractMailException
     */
    public function checkDeliverability(string $mail): void
    {
        $providers = $this->getProviders();

        foreach ($providers as $providerClass => $providerConfig) {
            try {
                $provider = $this->createProvider($providerClass, $providerConfig);
                if ($provider->isReal($mail)) {
                    return; // Mail is valid, exit successfully
                }

                // Mail is not real according to this provider
                throw new InaccessibleMailException();
            } catch (ExternalMailProviderException) {
                // Provider-specific exceptions (like API key errors) should skip to next provider
                continue;
            } catch (InaccessibleMailException) {
                // Stop the chain
                throw new InaccessibleMailException();
            } catch (Throwable) {
                // Other unexpected errors should also skip to next provider
                continue;
            }
        }

        // If we've exhausted all providers without getting a result, throw exception
        throw new ExternalMailProviderException();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function clearCache(): bool
    {
        return $this->cacheService->flush(ServiceType::EXTERNAL->value);
    }

    private function getProviders(): array
    {
        $priorityOrder = Arr::get($this->config, 'priority', []);
        $allProviders = Arr::get($this->config, 'providers', []);

        return collect(Arr::only($allProviders, $priorityOrder))
            ->mapWithKeys(fn($provider) => [
                Arr::get($provider, 'resolver') => Arr::get($provider, 'config', []),
            ])
            ->toArray();
    }

    /**
     * @param class-string $providerClass
     */
    private function createProvider(string $providerClass, array $providerConfig): ExternalMailProviderInterface
    {
        return new $providerClass($providerConfig, $this->cacheService);
    }
}
