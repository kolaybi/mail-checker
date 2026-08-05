<?php

use Illuminate\Support\Facades\Storage;
use KolayBi\Validation\Mail\Enums\SuppressionReason;
use KolayBi\Validation\Mail\Exceptions\SuppressedMailException;
use KolayBi\Validation\Mail\MailChecker;

it('rejects suppressed mail in the validation pipeline', function () {
    MailChecker::suppress('dead@mail.test', SuppressionReason::BOUNCE);

    MailChecker::check('dead@mail.test', skipExternalControl: true);
})->throws(SuppressedMailException::class);

it('checks suppression even when external control is skipped', function () {
    MailChecker::suppress('dead@mail.test', SuppressionReason::BOUNCE);

    expect(MailChecker::isValid('dead@mail.test', skipExternalControl: true))->toBeFalse();
});

it('passes non suppressed mail with external control skipped', function () {
    expect(MailChecker::isValid('alive@mail.test', skipExternalControl: true))->toBeTrue();
});

it('ignores suppression when the feature is disabled', function () {
    MailChecker::suppress('dead@mail.test', SuppressionReason::BOUNCE);
    config()->set('kolaybi.mail-checker.suppression.enabled', false);

    expect(MailChecker::isValid('dead@mail.test', skipExternalControl: true))->toBeTrue();
});

it('lets whitelisted domains bypass suppression at input time', function () {
    // example.org is not present in the shared fixture (tests/fixtures/data/domains/whitelisted_domains.json).
    // Fake the local disk and point the whitelist storage path at a temp fixture for this test only,
    // instead of editing the shared file used by the rest of the suite.
    Storage::fake('local');
    Storage::put('data/domains/whitelisted_domains_test.json', json_encode(['example.org']));
    config()->set('kolaybi.mail-checker.local.whitelist.storage_path', 'data/domains/whitelisted_domains_test.json');

    MailChecker::suppress('boss@example.org', SuppressionReason::BOUNCE);

    expect(MailChecker::isValid('boss@example.org', skipExternalControl: true))->toBeTrue()
        ->and(MailChecker::isSuppressed('boss@example.org'))->toBeTrue();
});

it('exposes suppress and unsuppress statically', function () {
    MailChecker::suppress('dead@example.com', SuppressionReason::MANUAL, 'manual');

    expect(MailChecker::isSuppressed('dead@example.com'))->toBeTrue()
        ->and(MailChecker::isNotSuppressed('alive@example.com'))->toBeTrue()
        ->and(MailChecker::unsuppress('dead@example.com'))->toBeTrue()
        ->and(MailChecker::isSuppressed('dead@example.com'))->toBeFalse();
});
