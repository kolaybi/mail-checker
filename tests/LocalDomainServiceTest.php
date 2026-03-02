<?php

use Illuminate\Support\Facades\Storage;
use KolayBi\Validation\Mail\Services\LocalDomainService;

beforeEach(function () {
    $this->service = new LocalDomainService();
});

describe('whitelist', function () {
    it('detects whitelisted domains', function () {
        expect($this->service->isWhitelisted('user@example.com'))->toBeTrue();
    });

    it('detects whitelisted subdomains', function () {
        expect($this->service->isWhitelisted('user@mail.example.com'))->toBeTrue();
    });

    it('rejects non-whitelisted domains', function () {
        expect($this->service->isWhitelisted('user@other.com'))->toBeFalse();
    });
});

describe('blacklist', function () {
    it('detects blacklisted domains', function () {
        expect($this->service->isBlacklisted('user@spam.com'))->toBeTrue();
    });

    it('detects blacklisted subdomains', function () {
        expect($this->service->isBlacklisted('user@mail.spam.com'))->toBeTrue();
    });

    it('allows non-blacklisted domains', function () {
        expect($this->service->isBlacklisted('user@clean.com'))->toBeFalse();
    });
});

describe('disposable', function () {
    it('detects disposable domains', function () {
        expect($this->service->isDisposable('user@tempmail.com'))->toBeTrue();
    });

    it('detects disposable subdomains', function () {
        expect($this->service->isDisposable('user@sub.tempmail.com'))->toBeTrue();
    });

    it('allows non-disposable domains', function () {
        expect($this->service->isDisposable('user@gmail.com'))->toBeFalse();
    });
});

describe('edge cases', function () {
    it('handles emails without domain gracefully', function () {
        expect($this->service->isWhitelisted('nodomain'))->toBeFalse();
    });

    it('handles empty string', function () {
        expect($this->service->isWhitelisted(''))->toBeFalse();
    });

    it('returns false when storage_path is empty', function () {
        config()->set('kolaybi.mail-checker.local.whitelist.storage_path', '');

        $service = new LocalDomainService();

        expect($service->isWhitelisted('user@example.com'))->toBeFalse();
    });

    it('returns false when domain list file is empty', function () {
        Storage::put('data/domains/empty_domains.json', json_encode([]));
        config()->set('kolaybi.mail-checker.local.whitelist.storage_path', 'data/domains/empty_domains.json');

        $service = new LocalDomainService();

        expect($service->isWhitelisted('user@example.com'))->toBeFalse();
    });
});
