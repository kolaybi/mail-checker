<?php

use KolayBi\Validation\Mail\Contracts\SuppressionSyncInterface;
use KolayBi\Validation\Mail\Enums\SuppressionReason;
use KolayBi\Validation\Mail\Models\SuppressedEmail;
use KolayBi\Validation\Mail\Services\SuppressionService;

it('reports not suppressed for unknown address', function () {
    expect(new SuppressionService()->isSuppressed('ok@example.com'))->toBeFalse();
});

it('suppresses with normalization and reports suppressed', function () {
    $service = new SuppressionService();
    $row = $service->suppress('  Dead@Example.COM ', SuppressionReason::BOUNCE, 'ses');

    expect($row->email)->toBe('dead@example.com')
        ->and($service->isSuppressed('DEAD@example.com'))->toBeTrue();
});

it('upserts idempotently on repeated suppress', function () {
    $service = new SuppressionService();
    $service->suppress('dup@example.com', SuppressionReason::BOUNCE);
    $service->suppress('dup@example.com', SuppressionReason::COMPLAINT);

    expect(SuppressedEmail::query()->where('email', 'dup@example.com')->count())->toBe(1)
        ->and(SuppressedEmail::query()->where('email', 'dup@example.com')->sole()->reason)
        ->toBe(SuppressionReason::COMPLAINT);
});

it('returns false from isSuppressed when the feature is disabled', function () {
    $service = new SuppressionService();
    $service->suppress('dead@example.com', SuppressionReason::BOUNCE);

    config()->set('kolaybi.mail-checker.suppression.enabled', false);

    expect($service->isSuppressed('dead@example.com'))->toBeFalse();
});

it('throws on suppress when the feature is disabled', function () {
    config()->set('kolaybi.mail-checker.suppression.enabled', false);

    new SuppressionService()->suppress('dead@example.com', SuppressionReason::BOUNCE);
})->throws(RuntimeException::class);

it('unsuppresses and reports whether a row was removed', function () {
    $service = new SuppressionService();
    $service->suppress('dead@example.com', SuppressionReason::BOUNCE);

    expect($service->unsuppress('dead@example.com'))->toBeTrue()
        ->and($service->unsuppress('dead@example.com'))->toBeFalse()
        ->and($service->isSuppressed('dead@example.com'))->toBeFalse();
});

it('calls a bound sync before deleting and fails closed on sync failure', function () {
    $service = new SuppressionService();
    $service->suppress('dead@example.com', SuppressionReason::BOUNCE);

    app()->bind(SuppressionSyncInterface::class, fn() => new class () implements SuppressionSyncInterface {
        public function forget(string $email): void
        {
            throw new RuntimeException('ses is down');
        }
    });

    expect(fn() => $service->unsuppress('dead@example.com'))->toThrow(RuntimeException::class)
        ->and($service->isSuppressed('dead@example.com'))->toBeTrue();
});

it('uses an overridden model class from config', function () {
    $custom = new class () extends SuppressedEmail {};
    config()->set('kolaybi.mail-checker.suppression.model', $custom::class);

    $row = new SuppressionService()->suppress('dead@example.com', SuppressionReason::MANUAL);

    expect($row)->toBeInstanceOf($custom::class);
});
