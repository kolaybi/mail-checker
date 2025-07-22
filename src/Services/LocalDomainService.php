<?php

namespace KolayBi\Validation\Mail\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use KolayBi\Validation\Mail\Enums\ListType;
use KolayBi\Validation\Mail\Enums\ServiceType;

class LocalDomainService
{
    private array $config;

    private CacheService $cacheService;

    public function __construct()
    {
        $this->config = Config::get('mail-checker.local');
        $this->cacheService = new CacheService(
            Arr::get($this->config, 'cache.ttl'),
            Arr::get($this->config, 'cache.enabled'),
            Arr::get($this->config, 'cache.store'),
        );
    }

    public function isWhitelisted(string $mail): bool
    {
        return $this->is($mail, ListType::WHITELIST);
    }

    public function isBlacklisted(string $mail): bool
    {
        return $this->is($mail, ListType::BLACKLIST);
    }

    public function isDisposable(string $mail): bool
    {
        return $this->is($mail, ListType::DISPOSABLE);
    }

    /**
     * Clear domain cache for a specific list type or all lists
     */
    public function clearCache(?ListType $listType = null): bool
    {
        if ($listType) {
            $path = Arr::get($this->config, "{$listType->value}.storage_path");

            return $this->cacheService->forget("domains:{$path}");
        }

        // Clear all domain caches
        $result = true;
        foreach (ListType::cases() as $type) {
            $path = Arr::get($this->config, "{$type->value}.storage_path");
            $result = $result && $this->cacheService->forget("domains:{$path}");
        }

        return $result;
    }

    private function is(string $mail, ListType $listType): bool
    {
        $serviceType = ServiceType::LOCAL->value;

        return $this->cacheService->remember(
            "{$serviceType}:{$listType->value}:{$mail}",
            fn() => $this->checkDomainList($mail, $listType),
        );
    }

    private function checkDomainList(string $mail, ListType $listType): bool
    {
        $domains = $this->getDomains(Arr::get($this->config, "{$listType->value}.storage_path"));

        return $this->contains($mail, $domains);
    }

    private function getDomains(string $filePath): array
    {
        // Cache the domain lists as they don't change frequently
        return $this->cacheService->remember(
            "domains:{$filePath}",
            fn() => Storage::json($filePath) ?? [],
        );
    }

    /**
     * Check whether the given mail address' domain matches one from the given domains
     */
    private function contains(string $mail, array $domains): bool
    {
        // Parse the mail to its top level domain.
        preg_match(
            '/([^.\/]+)(\.[^.\/]+)((\.[^.\/]+)+)?$/',
            explode('@', $mail, 2)[1] ?? '',
            $domain,
        );

        // Just ignore this validator if the value doesn't even resemble a mail or domain.
        if (0 === count($domain)) {
            return false;
        }

        return in_array($domain[0], $domains);
    }
}
