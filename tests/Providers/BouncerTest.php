<?php

use Illuminate\Support\Facades\Http;
use KolayBi\Validation\Mail\Exceptions\BouncerExternalMailProviderException;
use KolayBi\Validation\Mail\Services\Providers\Bouncer;

beforeEach(function () {
    Http::preventStrayRequests();

    $this->provider = new Bouncer([
        'endpoint' => 'https://api.usebouncer.com/v1.1/email/verify',
        'api_key'  => 'test-key',
        'timeout'  => 5,
    ]);
});

it('returns true for deliverable email', function () {
    Http::fake([
        'api.usebouncer.com/*' => Http::response(['status' => 'deliverable']),
    ]);

    expect($this->provider->isReal('user@example.com'))->toBeTrue();
});

it('returns false for undeliverable email', function () {
    Http::fake([
        'api.usebouncer.com/*' => Http::response(['status' => 'undeliverable']),
    ]);

    expect($this->provider->isReal('user@bad.com'))->toBeFalse();
});

it('returns true for risky email', function () {
    Http::fake([
        'api.usebouncer.com/*' => Http::response(['status' => 'risky']),
    ]);

    expect($this->provider->isReal('user@risky.com'))->toBeTrue();
});

it('throws on HTTP failure', function () {
    Http::fake([
        'api.usebouncer.com/*' => Http::response([], 500),
    ]);

    $this->provider->isReal('user@example.com');
})->throws(BouncerExternalMailProviderException::class);

it('throws on unknown response format', function () {
    Http::fake([
        'api.usebouncer.com/*' => Http::response(['error' => 'bad request']),
    ]);

    $this->provider->isReal('user@example.com');
})->throws(BouncerExternalMailProviderException::class);

it('sends API key as x-api-key header', function () {
    Http::fake([
        'api.usebouncer.com/*' => Http::response(['status' => 'deliverable']),
    ]);

    $this->provider->isReal('user@example.com');

    Http::assertSent(fn($request) => 'test-key' === $request->header('x-api-key')[0]);
});
