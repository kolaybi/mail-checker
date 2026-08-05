<?php

use KolayBi\Validation\Mail\Enums\SuppressionReason;
use KolayBi\Validation\Mail\Exceptions\AbstractMailException;
use KolayBi\Validation\Mail\Exceptions\SuppressedMailException;
use KolayBi\Validation\Mail\Models\SuppressedEmail;

it('configures suppression defaults', function () {
    // Note: suppression.enabled is true in this test case because SuppressionTestCase
    // overrides it in defineEnvironment(); see tests/SuppressionTestCase.php
    expect(config('kolaybi.mail-checker.suppression.enabled'))->toBeTrue()
        ->and(config('kolaybi.mail-checker.suppression.table'))->toBe('mail_suppressions')
        ->and(config('kolaybi.mail-checker.suppression.model'))->toBe(SuppressedEmail::class);
});

it('defines the suppression reasons', function () {
    expect(array_column(SuppressionReason::cases(), 'value'))
        ->toBe(['bounce', 'complaint', 'manual']);
});

it('extends the mail exception family', function () {
    expect(new SuppressedMailException())->toBeInstanceOf(AbstractMailException::class);
});
