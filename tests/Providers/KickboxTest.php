<?php

use Illuminate\Support\Facades\Http;
use KolayBi\Validation\Mail\Exceptions\KickboxExternalMailProviderException;
use KolayBi\Validation\Mail\Services\Providers\Kickbox;

beforeEach(function () {
    Http::preventStrayRequests();

    $this->provider = new Kickbox([
        'endpoint' => 'https://api.kickbox.com/v2/verify',
        'api_key'  => 'test-key',
        'timeout'  => 5,
    ]);
});

it('returns true for deliverable email', function () {
    Http::fake([
        'api.kickbox.com/*' => Http::response(['result' => 'deliverable']),
    ]);

    expect($this->provider->isReal('user@example.com'))->toBeTrue();
});

it('returns false for undeliverable email', function () {
    Http::fake([
        'api.kickbox.com/*' => Http::response(['result' => 'undeliverable']),
    ]);

    expect($this->provider->isReal('user@bad.com'))->toBeFalse();
});

it('throws on HTTP failure', function () {
    Http::fake([
        'api.kickbox.com/*' => Http::response([], 500),
    ]);

    $this->provider->isReal('user@example.com');
})->throws(KickboxExternalMailProviderException::class);

it('throws on unknown response format', function () {
    Http::fake([
        'api.kickbox.com/*' => Http::response(['error' => 'bad']),
    ]);

    $this->provider->isReal('user@example.com');
})->throws(KickboxExternalMailProviderException::class);
