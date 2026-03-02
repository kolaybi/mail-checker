<?php

use Illuminate\Support\Facades\Http;
use KolayBi\Validation\Mail\Exceptions\ExternalMailProviderException;
use KolayBi\Validation\Mail\Exceptions\InaccessibleMailException;
use KolayBi\Validation\Mail\Services\ExternalMailService;

beforeEach(function () {
    Http::preventStrayRequests();

    config()->set('kolaybi.mail-checker.external.priority', ['abstract_api']);
    config()->set('kolaybi.mail-checker.external.providers.abstract_api.config.api_key', 'test-key');
    config()->set('kolaybi.mail-checker.external.cache.enabled', false);
});

describe('checkDeliverability', function () {
    it('passes when provider says email is deliverable', function () {
        Http::fake([
            'emailvalidation.abstractapi.com/*' => Http::response([
                'deliverability' => 'DELIVERABLE',
            ]),
        ]);

        $service = new ExternalMailService();
        $service->checkDeliverability('user@example.com');
    })->throwsNoExceptions();

    it('throws InaccessibleMailException when provider says undeliverable', function () {
        Http::fake([
            'emailvalidation.abstractapi.com/*' => Http::response([
                'deliverability' => 'UNDELIVERABLE',
            ]),
        ]);

        $service = new ExternalMailService();
        $service->checkDeliverability('user@bad.com');
    })->throws(InaccessibleMailException::class);

    it('throws ExternalMailProviderException when provider HTTP fails', function () {
        Http::fake([
            'emailvalidation.abstractapi.com/*' => Http::response([], 500),
        ]);

        $service = new ExternalMailService();
        $service->checkDeliverability('user@example.com');
    })->throws(ExternalMailProviderException::class);

    it('skips silently when no providers are configured', function () {
        config()->set('kolaybi.mail-checker.external.priority', []);
        config()->set('kolaybi.mail-checker.external.fail_if_no_providers', false);

        $service = new ExternalMailService();
        $service->checkDeliverability('user@example.com');
    })->throwsNoExceptions();

    it('throws when no providers and fail_if_no_providers is true', function () {
        config()->set('kolaybi.mail-checker.external.priority', []);
        config()->set('kolaybi.mail-checker.external.fail_if_no_providers', true);

        $service = new ExternalMailService();
        $service->checkDeliverability('user@example.com');
    })->throws(ExternalMailProviderException::class);

    it('skips providers with missing API keys', function () {
        config()->set('kolaybi.mail-checker.external.providers.abstract_api.config.api_key', null);
        config()->set('kolaybi.mail-checker.external.fail_if_no_providers', false);

        $service = new ExternalMailService();
        $service->checkDeliverability('user@example.com');
    })->throwsNoExceptions();
});

describe('provider priority', function () {
    it('respects provider order from priority config', function () {
        config()->set('kolaybi.mail-checker.external.priority', ['mailgun', 'abstract_api']);
        config()->set('kolaybi.mail-checker.external.providers.mailgun.config.api_key', 'mg-key');

        Http::fake([
            'api.mailgun.net/*' => Http::response([
                'is_valid'               => true,
                'is_disposable_address'  => false,
                'mailbox_verification'   => 'true',
            ]),
        ]);

        $service = new ExternalMailService();
        $service->checkDeliverability('user@example.com');

        Http::assertSentCount(1);
        Http::assertSent(fn($request) => str_contains($request->url(), 'mailgun'));
    });
});
