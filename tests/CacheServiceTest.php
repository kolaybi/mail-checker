<?php

use KolayBi\Validation\Mail\Services\CacheService;

describe('CacheService', function () {
    it('returns callback result when cache is disabled', function () {
        $service = new CacheService(ttl: 60, enabled: false, cacheStore: 'mail-checker');

        $result = $service->remember('key', fn() => 'value');

        expect($result)->toBe('value');
    });

    it('caches result when enabled', function () {
        $service = new CacheService(ttl: 60, enabled: true, cacheStore: 'mail-checker');

        $callCount = 0;
        $callback = function () use (&$callCount) {
            $callCount++;

            return 'cached-value';
        };

        $first = $service->remember('test-key', $callback);
        $second = $service->remember('test-key', $callback);

        expect($first)->toBe('cached-value');
        expect($second)->toBe('cached-value');
        expect($callCount)->toBe(1);
    });

    it('forget removes cached key', function () {
        $service = new CacheService(ttl: 60, enabled: true, cacheStore: 'mail-checker');

        $service->remember('forget-key', fn() => 'value');
        $result = $service->forget('forget-key');

        expect($result)->toBeTrue();
    });

    it('forget returns true when cache is disabled', function () {
        $service = new CacheService(ttl: 60, enabled: false, cacheStore: 'mail-checker');

        expect($service->forget('any-key'))->toBeTrue();
    });

    it('flush clears all cached data', function () {
        $service = new CacheService(ttl: 60, enabled: true, cacheStore: 'mail-checker');

        $service->remember('flush-key', fn() => 'value');
        $result = $service->flush();

        expect($result)->toBeTrue();
    });

    it('flush returns true when cache is disabled', function () {
        $service = new CacheService(ttl: 60, enabled: false, cacheStore: 'mail-checker');

        expect($service->flush())->toBeTrue();
    });
});
