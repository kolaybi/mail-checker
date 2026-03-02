<?php

use KolayBi\Validation\Mail\MailChecker;

it('clearAllCaches returns true', function () {
    expect(MailChecker::clearAllCaches())->toBeTrue();
});
