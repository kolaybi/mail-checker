<?php

namespace KolayBi\Validation\Mail\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;

readonly class CacheService
{
    private CacheRepository $cache;

    public function __construct(
        private int $ttl,
        private bool $enabled,
        ?string $cacheStore = null,
    ) {
        $this->cache = Cache::store($cacheStore);
    }

    public function remember(string $key, callable $callback): mixed
    {
        if (!$this->enabled) {
            return $callback();
        }

        return $this->cache->remember($this->getCacheKey($key), $this->ttl, $callback);
    }

    public function forget(string $key): bool
    {
        if (!$this->enabled) {
            return true;
        }

        return $this->cache->forget($this->getCacheKey($key));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function flush(?string $cacheKey = null): bool
    {
        if (!$this->enabled) {
            return true;
        }

        // Flush all mail-checker related cache keys
        return $this->cache->deleteMultiple(
            collect($this->cache->getMultiple([]))
                ->keys()
                ->filter(fn($key) => str_starts_with($key, 'mail_checker:' . $cacheKey))
                ->toArray(),
        );
    }

    private function getCacheKey(string $key): string
    {
        return "mail_checker:{$key}";
    }
}
