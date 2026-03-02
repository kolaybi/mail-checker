<?php

use Illuminate\Support\Facades\Http;
use KolayBi\Validation\Mail\Exceptions\MailgunExternalMailProviderException;
use KolayBi\Validation\Mail\Services\Providers\Mailgun;

beforeEach(function () {
    Http::preventStrayRequests();

    $this->provider = new Mailgun([
        'endpoint' => 'https://api.mailgun.net/v4/address/validate',
        'api_key'  => 'test-key',
        'timeout'  => 5,
    ]);
});

it('returns true for valid non-disposable email', function () {
    Http::fake([
        'api.mailgun.net/*' => Http::response([
            'is_valid'              => true,
            'is_disposable_address' => false,
            'mailbox_verification'  => 'true',
        ]),
    ]);

    expect($this->provider->isReal('user@example.com'))->toBeTrue();
});

it('returns false for invalid email', function () {
    Http::fake([
        'api.mailgun.net/*' => Http::response([
            'is_valid'              => false,
            'is_disposable_address' => false,
            'mailbox_verification'  => 'true',
        ]),
    ]);

    expect($this->provider->isReal('user@bad.com'))->toBeFalse();
});

it('returns false for disposable email', function () {
    Http::fake([
        'api.mailgun.net/*' => Http::response([
            'is_valid'              => true,
            'is_disposable_address' => true,
            'mailbox_verification'  => 'true',
        ]),
    ]);

    expect($this->provider->isReal('user@tempmail.com'))->toBeFalse();
});

it('returns false when mailbox verification fails', function () {
    Http::fake([
        'api.mailgun.net/*' => Http::response([
            'is_valid'              => true,
            'is_disposable_address' => false,
            'mailbox_verification'  => 'false',
        ]),
    ]);

    expect($this->provider->isReal('user@nombox.com'))->toBeFalse();
});

it('throws on error message in response', function () {
    Http::fake([
        'api.mailgun.net/*' => Http::response(['message' => 'Forbidden']),
    ]);

    $this->provider->isReal('user@example.com');
})->throws(MailgunExternalMailProviderException::class);

it('throws on HTTP failure', function () {
    Http::fake([
        'api.mailgun.net/*' => Http::response([], 500),
    ]);

    $this->provider->isReal('user@example.com');
})->throws(MailgunExternalMailProviderException::class);

it('uses HTTP Basic Auth', function () {
    Http::fake([
        'api.mailgun.net/*' => Http::response([
            'is_valid'              => true,
            'is_disposable_address' => false,
            'mailbox_verification'  => 'true',
        ]),
    ]);

    $this->provider->isReal('user@example.com');

    Http::assertSent(function ($request) {
        $auth = $request->header('Authorization')[0] ?? '';

        return str_starts_with($auth, 'Basic ');
    });
});
