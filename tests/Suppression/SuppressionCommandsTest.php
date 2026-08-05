<?php

use KolayBi\Validation\Mail\Enums\SuppressionReason;
use KolayBi\Validation\Mail\MailChecker;
use KolayBi\Validation\Mail\Models\SuppressedEmail;

it('suppresses via command', function () {
    $this->artisan('mail-checker:suppress', ['email' => 'dead@example.com', '--reason' => 'bounce'])
        ->assertSuccessful();

    expect(MailChecker::isSuppressed('dead@example.com'))->toBeTrue();
});

it('rejects an unknown reason', function () {
    $this->artisan('mail-checker:suppress', ['email' => 'dead@example.com', '--reason' => 'nonsense'])
        ->assertFailed();
});

it('unsuppresses via command', function () {
    MailChecker::suppress('dead@example.com', SuppressionReason::BOUNCE);

    $this->artisan('mail-checker:unsuppress', ['email' => 'dead@example.com'])->assertSuccessful();

    expect(MailChecker::isSuppressed('dead@example.com'))->toBeFalse();
});

it('imports a csv with header and mixed-case reasons', function () {
    $path = tempnam(sys_get_temp_dir(), 'supp') . '.csv';
    file_put_contents($path, implode("\n", [
        'email,reason,suppressed_at',
        'a@example.com,BOUNCE,2026-03-11T10:00:00Z',
        'b@example.com,complaint,',
        'broken-line-without-commas',
    ]));

    $this->artisan('mail-checker:suppression-import', ['path' => $path])->assertSuccessful();

    expect(SuppressedEmail::query()->count())->toBe(2)
        ->and(SuppressedEmail::query()->where('email', 'a@example.com')->sole()->reason)->toBe(SuppressionReason::BOUNCE)
        ->and(SuppressedEmail::query()->where('email', 'a@example.com')->sole()->source)->toBe('import');

    unlink($path);
});
