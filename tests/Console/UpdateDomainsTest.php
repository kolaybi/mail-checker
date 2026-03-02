<?php

use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Storage::put('data/domains/whitelisted_domains.json', json_encode(['existing.com']));
    Storage::put('data/domains/blacklisted_domains.json', json_encode([]));
});

describe('validation', function () {
    it('fails without --type', function () {
        $this->artisan('mail-checker:update-domains', ['--add' => ['test.com']])
            ->assertFailed();
    });

    it('fails with invalid type', function () {
        $this->artisan('mail-checker:update-domains', ['--type' => 'invalid', '--add' => ['test.com']])
            ->assertFailed();
    });

    it('fails without any operation', function () {
        $this->artisan('mail-checker:update-domains', ['--type' => 'whitelist'])
            ->assertFailed();
    });
});

describe('add', function () {
    it('adds domains to whitelist', function () {
        $this->artisan('mail-checker:update-domains', [
            '--type' => 'whitelist',
            '--add'  => ['newdomain.com'],
        ])->assertSuccessful();

        $domains = Storage::json('data/domains/whitelisted_domains.json');

        expect($domains)->toContain('newdomain.com');
    });

    it('skips duplicate domains', function () {
        $this->artisan('mail-checker:update-domains', [
            '--type' => 'whitelist',
            '--add'  => ['existing.com'],
        ])->assertSuccessful();
    });

    it('skips invalid domains', function () {
        $this->artisan('mail-checker:update-domains', [
            '--type' => 'whitelist',
            '--add'  => ['not a domain'],
        ])->assertSuccessful();
    });
});

describe('remove', function () {
    it('removes domains from whitelist', function () {
        $this->artisan('mail-checker:update-domains', [
            '--type'   => 'whitelist',
            '--remove' => ['existing.com'],
        ])->assertSuccessful();

        $domains = Storage::json('data/domains/whitelisted_domains.json');

        expect($domains)->not->toContain('existing.com');
    });

    it('handles removing non-existent domains', function () {
        $this->artisan('mail-checker:update-domains', [
            '--type'   => 'whitelist',
            '--remove' => ['nonexistent.com'],
        ])->assertSuccessful();
    });

    it('handles removing from empty list', function () {
        $this->artisan('mail-checker:update-domains', [
            '--type'   => 'blacklist',
            '--remove' => ['nonexistent.com'],
        ])->assertSuccessful();
    });
});

describe('combined operations', function () {
    it('lists and adds domains together', function () {
        $this->artisan('mail-checker:update-domains', [
            '--type' => 'whitelist',
            '--list' => true,
            '--add'  => ['newdomain.com'],
        ])->assertSuccessful()
            ->expectsOutputToContain('existing.com')
            ->expectsOutputToContain('All operations completed successfully');

        $domains = Storage::json('data/domains/whitelisted_domains.json');
        expect($domains)->toContain('newdomain.com');
    });

    it('adds and removes domains together', function () {
        $this->artisan('mail-checker:update-domains', [
            '--type'   => 'whitelist',
            '--add'    => ['newdomain.com'],
            '--remove' => ['existing.com'],
        ])->assertSuccessful()
            ->expectsOutputToContain('All operations completed successfully');

        $domains = Storage::json('data/domains/whitelisted_domains.json');
        expect($domains)->toContain('newdomain.com')
            ->not->toContain('existing.com');
    });
});

describe('validation edge cases', function () {
    it('fails when storage path is not configured', function () {
        config()->set('kolaybi.mail-checker.local.whitelist.storage_path', '');

        $this->artisan('mail-checker:update-domains', [
            '--type' => 'whitelist',
            '--add'  => ['test.com'],
        ])->assertFailed()
            ->expectsOutputToContain('storage path not configured');
    });

    it('reports skipped domains when adding mix of new and existing', function () {
        $this->artisan('mail-checker:update-domains', [
            '--type' => 'whitelist',
            '--add'  => ['existing.com', 'newdomain.com'],
        ])->assertSuccessful()
            ->expectsOutputToContain('Skipped 1 domain(s)');
    });

    it('warns when removing domains not in the list', function () {
        Storage::put('data/domains/whitelisted_domains.json', json_encode(['existing.com']));

        $this->artisan('mail-checker:update-domains', [
            '--type'   => 'whitelist',
            '--remove' => ['existing.com', 'nothere.com'],
        ])->assertSuccessful()
            ->expectsOutputToContain('Skipped 1 domain(s)');
    });

    it('warns when all add domains are invalid', function () {
        $this->artisan('mail-checker:update-domains', [
            '--type' => 'whitelist',
            '--add'  => ['not a domain', 'also invalid'],
        ])->assertSuccessful()
            ->expectsOutputToContain('No valid domains to add');
    });

    it('warns when all remove domains are invalid', function () {
        $this->artisan('mail-checker:update-domains', [
            '--type'   => 'whitelist',
            '--remove' => ['not a domain'],
        ])->assertSuccessful()
            ->expectsOutputToContain('No valid domains to remove');
    });
});

describe('domain validation', function () {
    it('rejects empty domain', function () {
        $this->artisan('mail-checker:update-domains', [
            '--type' => 'whitelist',
            '--add'  => [''],
        ])->assertSuccessful()
            ->expectsOutputToContain('No valid domains to add');
    });

    it('rejects domain exceeding 253 characters', function () {
        $longDomain = str_repeat('a', 250) . '.com';

        $this->artisan('mail-checker:update-domains', [
            '--type' => 'whitelist',
            '--add'  => [$longDomain],
        ])->assertSuccessful()
            ->expectsOutputToContain('Invalid domains skipped');
    });

    it('rejects domain without TLD', function () {
        $this->artisan('mail-checker:update-domains', [
            '--type' => 'whitelist',
            '--add'  => ['localhost'],
        ])->assertSuccessful()
            ->expectsOutputToContain('Invalid domains skipped');
    });
});

describe('save failures', function () {
    it('handles save failure when adding domains', function () {
        Storage::shouldReceive('json')
            ->andReturn(['existing.com']);
        Storage::shouldReceive('put')
            ->andReturn(false);
        Storage::shouldReceive('exists')
            ->andReturn(true);

        $this->artisan('mail-checker:update-domains', [
            '--type' => 'whitelist',
            '--add'  => ['newdomain.com'],
        ])->assertSuccessful()
            ->expectsOutputToContain('Could not save domains');
    });

    it('handles save failure when removing domains', function () {
        Storage::shouldReceive('json')
            ->andReturn(['existing.com']);
        Storage::shouldReceive('put')
            ->andReturn(false);
        Storage::shouldReceive('exists')
            ->andReturn(true);

        $this->artisan('mail-checker:update-domains', [
            '--type'   => 'whitelist',
            '--remove' => ['existing.com'],
        ])->assertSuccessful()
            ->expectsOutputToContain('Could not save domains');
    });

    it('handles storage exception when saving', function () {
        Storage::shouldReceive('json')
            ->andReturn(['existing.com']);
        Storage::shouldReceive('exists')
            ->andReturn(true);
        Storage::shouldReceive('put')
            ->andThrow(new Exception('Disk full'));

        $this->artisan('mail-checker:update-domains', [
            '--type' => 'whitelist',
            '--add'  => ['newdomain.com'],
        ])->assertSuccessful()
            ->expectsOutputToContain('Storage error');
    });

    it('creates storage directory if it does not exist', function () {
        Storage::shouldReceive('json')
            ->andReturn([]);
        Storage::shouldReceive('exists')
            ->with('data/domains')
            ->andReturn(false);
        Storage::shouldReceive('makeDirectory')
            ->with('data/domains')
            ->once();
        Storage::shouldReceive('put')
            ->andReturn(true);

        $this->artisan('mail-checker:update-domains', [
            '--type' => 'whitelist',
            '--add'  => ['newdomain.com'],
        ])->assertSuccessful();
    });
});

describe('list', function () {
    it('lists domains', function () {
        $this->artisan('mail-checker:update-domains', [
            '--type' => 'whitelist',
            '--list' => true,
        ])->assertSuccessful()
            ->expectsOutputToContain('existing.com');
    });

    it('shows message for empty list', function () {
        $this->artisan('mail-checker:update-domains', [
            '--type' => 'blacklist',
            '--list' => true,
        ])->assertSuccessful();
    });
});
