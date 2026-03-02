<?php

namespace KolayBi\Validation\Mail\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use KolayBi\Validation\Mail\Enums\ListType;

class LocalDomainService
{
    private array $config;

    private CacheService $cacheService;

    public function __construct()
    {
        $this->config = Config::get('kolaybi.mail-checker.local', []);
        $this->cacheService = new CacheService(
            (int) Arr::get($this->config, 'cache.ttl', 604800),
            (bool) Arr::get($this->config, 'cache.enabled', true),
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

    public function clearCache(): bool
    {
        return $this->cacheService->flush();
    }

    private function is(string $mail, ListType $listType): bool
    {
        return $this->checkDomainList($mail, $listType);
    }

    private function checkDomainList(string $mail, ListType $listType): bool
    {
        $domains = $this->getDomains(Arr::get($this->config, "{$listType->value}.storage_path"));

        return $this->contains($mail, $domains);
    }

    private function getDomains(string $filePath): array
    {
        if (empty($filePath)) {
            return [];
        }

        // Cache the domain lists as they don't change frequently
        return $this->cacheService->remember(
            "domains:{$filePath}",
            fn() => (array) (Storage::json($filePath) ?? []),
        );
    }

    /**
     * Check whether the given mail address' domain matches one from the given domains.
     * Matches both exact domains and parent domains (e.g. mail.example.com matches example.com).
     */
    private function contains(string $mail, array $domains): bool
    {
        if (empty($domains)) {
            return false;
        }

        $mailDomain = explode('@', $mail, 2)[1] ?? '';

        if (empty($mailDomain)) {
            return false;
        }

        foreach ($domains as $domain) {
            if ($mailDomain === $domain || str_ends_with($mailDomain, '.' . $domain)) {
                return true;
            }
        }

        return false;
    }
}
