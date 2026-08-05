<?php

use KolayBi\Validation\Mail\Models\SuppressedEmail;

it('defaults suppression to disabled with null connection and schema', function () {
    expect(config('kolaybi.mail-checker.suppression.enabled'))->toBeFalse()
        ->and(config('kolaybi.mail-checker.suppression.connection'))->toBeNull()
        ->and(config('kolaybi.mail-checker.suppression.schema'))->toBeNull()
        ->and(config('kolaybi.mail-checker.suppression.table'))->toBe('mail_suppressions')
        ->and(config('kolaybi.mail-checker.suppression.model'))->toBe(SuppressedEmail::class);
});
