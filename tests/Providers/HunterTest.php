<?php

use Illuminate\Support\Facades\Http;
use KolayBi\Validation\Mail\Exceptions\HunterExternalMailProviderException;
use KolayBi\Validation\Mail\Services\Providers\Hunter;

beforeEach(function () {
    Http::preventStrayRequests();

    $this->provider = new Hunter([
        'endpoint' => 'https://api.hunter.io/v2/email-verifier',
        'api_key'  => 'test-key',
        'timeout'  => 5,
    ]);
});

it('returns true for valid status', function (string $status) {
    Http::fake([
        'api.hunter.io/*' => Http::response(['data' => ['status' => $status]]),
    ]);

    expect($this->provider->isReal('user@example.com'))->toBeTrue();
})->with(['valid', 'accept_all', 'webmail']);

it('returns false for invalid status', function () {
    Http::fake([
        'api.hunter.io/*' => Http::response(['data' => ['status' => 'invalid']]),
    ]);

    expect($this->provider->isReal('user@bad.com'))->toBeFalse();
});

it('throws on HTTP failure', function () {
    Http::fake([
        'api.hunter.io/*' => Http::response([], 500),
    ]);

    $this->provider->isReal('user@example.com');
})->throws(HunterExternalMailProviderException::class);

it('throws on missing data key', function () {
    Http::fake([
        'api.hunter.io/*' => Http::response(['message' => 'Unauthorized']),
    ]);

    $this->provider->isReal('user@example.com');
})->throws(HunterExternalMailProviderException::class);
