<?php

use Illuminate\Support\Facades\Http;
use KolayBi\Validation\Mail\Exceptions\MailboxLayerExternalMailProviderException;
use KolayBi\Validation\Mail\Services\Providers\MailboxLayer;

beforeEach(function () {
    Http::preventStrayRequests();

    $this->provider = new MailboxLayer([
        'endpoint'   => 'https://apilayer.net/api/check',
        'access_key' => 'test-key',
        'timeout'    => 5,
    ]);
});

it('returns true for valid, non-disposable email with MX', function () {
    Http::fake([
        'apilayer.net/*' => Http::response([
            'format_valid'  => true,
            'disposable'    => false,
            'mx_found'      => true,
        ]),
    ]);

    expect($this->provider->isReal('user@example.com'))->toBeTrue();
});

it('returns false for disposable email', function () {
    Http::fake([
        'apilayer.net/*' => Http::response([
            'format_valid'  => true,
            'disposable'    => true,
            'mx_found'      => true,
        ]),
    ]);

    expect($this->provider->isReal('user@tempmail.com'))->toBeFalse();
});

it('returns false when no MX record found', function () {
    Http::fake([
        'apilayer.net/*' => Http::response([
            'format_valid'  => true,
            'disposable'    => false,
            'mx_found'      => false,
        ]),
    ]);

    expect($this->provider->isReal('user@nomx.com'))->toBeFalse();
});

it('returns false for invalid format', function () {
    Http::fake([
        'apilayer.net/*' => Http::response([
            'format_valid'  => false,
            'disposable'    => false,
            'mx_found'      => false,
        ]),
    ]);

    expect($this->provider->isReal('invalid'))->toBeFalse();
});

it('throws on HTTP failure', function () {
    Http::fake([
        'apilayer.net/*' => Http::response([], 500),
    ]);

    $this->provider->isReal('user@example.com');
})->throws(MailboxLayerExternalMailProviderException::class);

it('throws on error response', function () {
    Http::fake([
        'apilayer.net/*' => Http::response(['error' => ['type' => 'invalid_access_key']]),
    ]);

    $this->provider->isReal('user@example.com');
})->throws(MailboxLayerExternalMailProviderException::class);
