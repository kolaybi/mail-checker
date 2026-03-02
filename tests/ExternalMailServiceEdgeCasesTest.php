<?php

use Illuminate\Support\Facades\Http;
use KolayBi\Validation\Mail\Exceptions\ExternalMailProviderException;
use KolayBi\Validation\Mail\Services\ExternalMailService;

beforeEach(function () {
    Http::preventStrayRequests();
    config()->set('kolaybi.mail-checker.external.cache.enabled', false);
});

it('falls back to next provider when first fails', function () {
    config()->set('kolaybi.mail-checker.external.priority', ['abstract_api', 'mailgun']);
    config()->set('kolaybi.mail-checker.external.providers.abstract_api.config.api_key', 'key1');
    config()->set('kolaybi.mail-checker.external.providers.mailgun.config.api_key', 'key2');

    Http::fake([
        'emailvalidation.abstractapi.com/*' => Http::response([], 500),
        'api.mailgun.net/*'                 => Http::response([
            'is_valid'              => true,
            'is_disposable_address' => false,
            'mailbox_verification'  => 'true',
        ]),
    ]);

    $service = new ExternalMailService();
    $service->checkDeliverability('user@example.com');

    Http::assertSentCount(2);
});

it('throws with all errors when all providers fail', function () {
    config()->set('kolaybi.mail-checker.external.priority', ['abstract_api', 'mailgun']);
    config()->set('kolaybi.mail-checker.external.providers.abstract_api.config.api_key', 'key1');
    config()->set('kolaybi.mail-checker.external.providers.mailgun.config.api_key', 'key2');

    Http::fake([
        'emailvalidation.abstractapi.com/*' => Http::response([], 500),
        'api.mailgun.net/*'                 => Http::response([], 500),
    ]);

    $service = new ExternalMailService();
    $service->checkDeliverability('user@example.com');
})->throws(ExternalMailProviderException::class, 'All external mail providers failed');

it('handles unexpected exceptions from providers', function () {
    config()->set('kolaybi.mail-checker.external.priority', ['abstract_api']);
    config()->set('kolaybi.mail-checker.external.providers.abstract_api.config.api_key', 'key1');
    config()->set('kolaybi.mail-checker.external.providers.abstract_api.config.timeout', 0);

    Http::fake([
        'emailvalidation.abstractapi.com/*' => fn() => throw new RuntimeException('Connection timeout'),
    ]);

    $service = new ExternalMailService();
    $service->checkDeliverability('user@example.com');
})->throws(ExternalMailProviderException::class);
