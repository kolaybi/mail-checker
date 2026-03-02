<?php

use Illuminate\Support\Facades\Http;
use KolayBi\Validation\Mail\Exceptions\BlacklistedMailException;
use KolayBi\Validation\Mail\Exceptions\DisposableMailException;
use KolayBi\Validation\Mail\Exceptions\EmptyMailException;
use KolayBi\Validation\Mail\Exceptions\InvalidMailException;
use KolayBi\Validation\Mail\MailChecker;

describe('check', function () {
    it('throws EmptyMailException for empty email', function () {
        MailChecker::check('');
    })->throws(EmptyMailException::class);

    it('throws EmptyMailException for whitespace-only email', function () {
        MailChecker::check('   ');
    })->throws(EmptyMailException::class);

    it('passes whitelisted emails without further checks', function () {
        MailChecker::check('user@example.com', skipExternalControl: true);
    })->throwsNoExceptions();

    it('throws InvalidMailException for invalid format', function () {
        MailChecker::check('not-an-email', skipExternalControl: true);
    })->throws(InvalidMailException::class);

    it('throws BlacklistedMailException for blacklisted domains', function () {
        MailChecker::check('user@spam.com', skipExternalControl: true);
    })->throws(BlacklistedMailException::class);

    it('throws DisposableMailException for disposable domains', function () {
        MailChecker::check('user@tempmail.com', skipExternalControl: true);
    })->throws(DisposableMailException::class);

    it('passes valid non-listed emails with skipExternalControl', function () {
        MailChecker::check('user@gmail.com', skipExternalControl: true);
    })->throwsNoExceptions();

    it('normalizes email to lowercase', function () {
        MailChecker::check('User@EXAMPLE.COM', skipExternalControl: true);
    })->throwsNoExceptions();
});

describe('isValid', function () {
    it('returns true for valid emails', function () {
        expect(MailChecker::isValid('user@example.com', skipExternalControl: true))->toBeTrue();
    });

    it('returns false for invalid emails', function () {
        expect(MailChecker::isValid('', skipExternalControl: true))->toBeFalse();
        expect(MailChecker::isValid('not-an-email', skipExternalControl: true))->toBeFalse();
        expect(MailChecker::isValid('user@spam.com', skipExternalControl: true))->toBeFalse();
    });
});

describe('getValidationResult', function () {
    it('returns valid result for good emails', function () {
        $result = MailChecker::getValidationResult('user@example.com', skipExternalControl: true);

        expect($result)->toHaveKey('valid', true);
    });

    it('returns failure reason for bad emails', function () {
        $result = MailChecker::getValidationResult('user@spam.com', skipExternalControl: true);

        expect($result)
            ->toHaveKey('valid', false)
            ->toHaveKey('reason', 'BlacklistedMailException');
    });
});

describe('validateBatch', function () {
    it('validates multiple emails', function () {
        $results = MailChecker::validateBatch([
            'user@example.com',
            'user@spam.com',
            '',
        ], skipExternalControl: true);

        expect($results)->toHaveCount(3);
        expect($results['user@example.com']['valid'])->toBeTrue();
        expect($results['user@spam.com']['valid'])->toBeFalse();
        expect($results['']['valid'])->toBeFalse();
    });
});

describe('external check', function () {
    it('calls external service when skipExternalControl is false', function () {
        Http::preventStrayRequests();
        config()->set('kolaybi.mail-checker.external.priority', []);
        config()->set('kolaybi.mail-checker.external.cache.enabled', false);

        MailChecker::check('user@gmail.com', skipExternalControl: false);
    })->throwsNoExceptions();
});

describe('convenience methods', function () {
    it('isWhitelisted checks whitelist', function () {
        expect(MailChecker::isWhitelisted('user@example.com'))->toBeTrue();
        expect(MailChecker::isNotWhitelisted('user@example.com'))->toBeFalse();
    });

    it('isBlacklisted checks blacklist', function () {
        expect(MailChecker::isBlacklisted('user@spam.com'))->toBeTrue();
        expect(MailChecker::isNotBlacklisted('user@spam.com'))->toBeFalse();
    });

    it('isDisposable checks disposable list', function () {
        expect(MailChecker::isDisposable('user@tempmail.com'))->toBeTrue();
        expect(MailChecker::isNotDisposable('user@tempmail.com'))->toBeFalse();
    });
});
