<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use KolayBi\Validation\Mail\Enums\SuppressionReason;
use KolayBi\Validation\Mail\Models\SuppressedEmail;

uses(RefreshDatabase::class)->beforeEach(function () {
    $this->app['config']->set('kolaybi.mail-checker.suppression.enabled', true);
    $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
});

it('persists a suppression with ulid key and casts', function () {
    $row = SuppressedEmail::query()->create([
        'email'         => 'dead@example.com',
        'reason'        => SuppressionReason::BOUNCE,
        'source'        => 'ses',
        'suppressed_at' => now(),
        'metadata'      => ['bounce_sub_type' => 'General'],
    ]);

    expect(strlen($row->id))->toBe(26)
        ->and($row->reason)->toBe(SuppressionReason::BOUNCE)
        ->and($row->metadata)->toBe(['bounce_sub_type' => 'General']);
});

it('reads table name from config with optional schema prefix', function () {
    expect(new SuppressedEmail()->getTable())->toBe('mail_suppressions');

    config()->set('kolaybi.mail-checker.suppression.schema', 'system');
    expect(new SuppressedEmail()->getTable())->toBe('system.mail_suppressions');
});

it('enforces unique email', function () {
    SuppressedEmail::query()->create(['email' => 'x@example.com', 'reason' => SuppressionReason::MANUAL, 'suppressed_at' => now()]);

    SuppressedEmail::query()->create(['email' => 'x@example.com', 'reason' => SuppressionReason::MANUAL, 'suppressed_at' => now()]);
})->throws(QueryException::class);
