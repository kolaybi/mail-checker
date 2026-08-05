<?php

use KolayBi\Validation\Mail\Enums\SuppressionReason;
use KolayBi\Validation\Mail\Exceptions\AbstractMailException;
use KolayBi\Validation\Mail\Exceptions\SuppressedMailException;
use KolayBi\Validation\Mail\Models\SuppressedEmail;

it('defaults suppression to disabled', function () {
    expect(config('kolaybi.mail-checker.suppression.enabled'))->toBeFalse()
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
