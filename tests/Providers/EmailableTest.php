<?php

use Illuminate\Support\Facades\Http;
use KolayBi\Validation\Mail\Exceptions\EmailableExternalMailProviderException;
use KolayBi\Validation\Mail\Services\Providers\Emailable;

beforeEach(function () {
    Http::preventStrayRequests();

    $this->provider = new Emailable([
        'endpoint' => 'https://api.emailable.com/v1/verify',
        'api_key'  => 'test-key',
        'timeout'  => 5,
    ]);
});

it('returns true for deliverable email', function () {
    Http::fake([
        'api.emailable.com/*' => Http::response(['state' => 'deliverable']),
    ]);

    expect($this->provider->isReal('user@example.com'))->toBeTrue();
});

it('returns false for undeliverable email', function () {
    Http::fake([
        'api.emailable.com/*' => Http::response(['state' => 'undeliverable']),
    ]);

    expect($this->provider->isReal('user@bad.com'))->toBeFalse();
});

it('throws on HTTP failure', function () {
    Http::fake([
        'api.emailable.com/*' => Http::response([], 500),
    ]);

    $this->provider->isReal('user@example.com');
})->throws(EmailableExternalMailProviderException::class);

it('throws on unknown response format', function () {
    Http::fake([
        'api.emailable.com/*' => Http::response(['error' => 'bad']),
    ]);

    $this->provider->isReal('user@example.com');
})->throws(EmailableExternalMailProviderException::class);
