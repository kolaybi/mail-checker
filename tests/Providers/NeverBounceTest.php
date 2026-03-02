<?php

use Illuminate\Support\Facades\Http;
use KolayBi\Validation\Mail\Exceptions\NeverBounceExternalMailProviderException;
use KolayBi\Validation\Mail\Services\Providers\NeverBounce;

beforeEach(function () {
    Http::preventStrayRequests();

    $this->provider = new NeverBounce([
        'endpoint' => 'https://api.neverbounce.com/v4/single/check',
        'api_key'  => 'test-key',
        'timeout'  => 5,
    ]);
});

it('returns true for valid email', function () {
    Http::fake([
        'api.neverbounce.com/*' => Http::response(['result' => 'valid']),
    ]);

    expect($this->provider->isReal('user@example.com'))->toBeTrue();
});

it('returns true for catchall email', function () {
    Http::fake([
        'api.neverbounce.com/*' => Http::response(['result' => 'catchall']),
    ]);

    expect($this->provider->isReal('user@catchall.com'))->toBeTrue();
});

it('returns false for invalid email', function () {
    Http::fake([
        'api.neverbounce.com/*' => Http::response(['result' => 'invalid']),
    ]);

    expect($this->provider->isReal('user@bad.com'))->toBeFalse();
});

it('throws on HTTP failure', function () {
    Http::fake([
        'api.neverbounce.com/*' => Http::response([], 500),
    ]);

    $this->provider->isReal('user@example.com');
})->throws(NeverBounceExternalMailProviderException::class);

it('throws on missing result key', function () {
    Http::fake([
        'api.neverbounce.com/*' => Http::response(['message' => 'error']),
    ]);

    $this->provider->isReal('user@example.com');
})->throws(NeverBounceExternalMailProviderException::class);
