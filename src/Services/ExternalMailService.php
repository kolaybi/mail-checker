<?php

namespace KolayBi\Validation\Mail\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use KolayBi\Validation\Mail\Exceptions\AbstractMailException;
use KolayBi\Validation\Mail\Exceptions\ExternalMailProviderException;
use KolayBi\Validation\Mail\Exceptions\InaccessibleMailException;
use KolayBi\Validation\Mail\Services\Providers\ExternalMailProviderInterface;
use Throwable;

class ExternalMailService
{
    private array $config;
    private CacheService $cacheService;
    private array $providers;

    public function __construct()
    {
        $this->config = Config::get('kolaybi.mail-checker.external', []);
        $this->cacheService = new CacheService(
            Arr::get($this->config, 'cache.ttl'),
            Arr::get($this->config, 'cache.enabled'),
            Arr::get($this->config, 'cache.store'),
        );
        $this->providers = $this->getProviders();
    }

    /**
     * Check the mail's deliverability against external services
     *
     * @throws AbstractMailException
     */
    public function checkDeliverability(string $mail): void
    {
        $errors = [];

        foreach ($this->providers as $providerClass => $providerConfig) {
            $providerName = class_basename($providerClass);

            try {
                $provider = $this->createProvider($providerClass, $providerConfig);
                if ($provider->isReal($mail)) {
                    return; // Mail is valid, exit successfully
                }

                // Mail is not real according to this provider
                throw new InaccessibleMailException(
                    sprintf('Email [%s] is not deliverable according to provider [%s]', $mail, $providerName),
                );
            } catch (ExternalMailProviderException $e) {
                // Provider-specific exceptions (like API key errors) should skip to next provider
                $errors[] = sprintf('[%s]: %s', $providerName, $e->getMessage());

                continue;
            } catch (InaccessibleMailException $e) {
                // Stop the chain
                throw $e;
            } catch (Throwable $e) {
                // Other unexpected errors should also skip to next provider
                $errors[] = sprintf('[%s]: Unexpected error: %s', $providerName, $e->getMessage());

                continue;
            }
        }

        // If we've exhausted all providers without getting a result, throw exception with details
        if (!empty($errors)) {
            throw new ExternalMailProviderException(
                sprintf(
                    'All external mail providers failed to validate email [%s]. Errors: %s',
                    $mail,
                    implode('; ', $errors),
                ),
            );
        }

        // Check if we should fail when no providers are configured
        $failIfNoProviders = Arr::get($this->config, 'fail_if_no_providers', false);

        if ($failIfNoProviders) {
            // If no providers were configured or all skipped without errors
            throw new ExternalMailProviderException(
                sprintf('No external mail providers were able to validate email [%s]', $mail),
            );
        }
    }

    public function clearCache(): bool
    {
        return $this->cacheService->flush();
    }

    private function getProviders(): array
    {
        $priorityOrder = Arr::get($this->config, 'priority', []);
        $allProviders = Arr::get($this->config, 'providers', []);

        return collect($priorityOrder)
            ->intersect(array_keys($allProviders))
            ->mapWithKeys(fn(string $key) => [
                Arr::get($allProviders, "{$key}.resolver") => Arr::get($allProviders, "{$key}.config", []),
            ])
            ->filter(fn(array $config) => !empty($config['api_key'] ?? $config['access_key'] ?? null))
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
