<?php

use Illuminate\Support\Facades\Http;
use KolayBi\Validation\Mail\Exceptions\AbstractApiExternalMailProviderException;
use KolayBi\Validation\Mail\Services\Providers\AbstractApi;

beforeEach(function () {
    Http::preventStrayRequests();

    $this->provider = new AbstractApi([
        'endpoint' => 'https://emailvalidation.abstractapi.com/v1/',
        'api_key'  => 'test-key',
        'timeout'  => 5,
    ]);
});

it('returns true for deliverable email', function () {
    Http::fake([
        'emailvalidation.abstractapi.com/*' => Http::response(['deliverability' => 'DELIVERABLE']),
    ]);

    expect($this->provider->isReal('user@example.com'))->toBeTrue();
});

it('returns false for undeliverable email', function () {
    Http::fake([
        'emailvalidation.abstractapi.com/*' => Http::response(['deliverability' => 'UNDELIVERABLE']),
    ]);

    expect($this->provider->isReal('user@bad.com'))->toBeFalse();
});

it('returns true for unknown deliverability', function () {
    Http::fake([
        'emailvalidation.abstractapi.com/*' => Http::response(['deliverability' => 'UNKNOWN']),
    ]);

    expect($this->provider->isReal('user@unknown.com'))->toBeTrue();
});

it('throws on HTTP failure', function () {
    Http::fake([
        'emailvalidation.abstractapi.com/*' => Http::response([], 500),
    ]);

    $this->provider->isReal('user@example.com');
})->throws(AbstractApiExternalMailProviderException::class);

it('throws on error response', function () {
    Http::fake([
        'emailvalidation.abstractapi.com/*' => Http::response(['error' => ['message' => 'Invalid API key']]),
    ]);

    $this->provider->isReal('user@example.com');
})->throws(AbstractApiExternalMailProviderException::class);
