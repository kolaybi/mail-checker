<?php

use KolayBi\Validation\Mail\MailChecker;

describe('isValidFormat', function () {
    it('accepts valid email addresses', function (string $email) {
        expect(MailChecker::isValidFormat($email))->toBeTrue();
    })->with([
        'simple'         => 'user@example.com',
        'with dots'      => 'first.last@example.com',
        'with plus'      => 'user+tag@example.com',
        'with subdomain' => 'user@mail.example.com',
        'numeric local'  => '123@example.com',
        'hyphen domain'  => 'user@my-domain.com',
    ]);

    it('rejects invalid email addresses', function (string $email) {
        expect(MailChecker::isValidFormat($email))->toBeFalse();
    })->with([
        'empty local'       => '@example.com',
        'empty domain'      => 'user@',
        'no at sign'        => 'userexample.com',
        'multiple at signs' => 'user@@example.com',
        'too short'         => 'a@b',
        'with quotes'       => '"user"@example.com',
        'too long'          => str_repeat('a', 310) . '@example.com',
    ]);

    it('isInvalidFormat is inverse of isValidFormat', function () {
        expect(MailChecker::isInvalidFormat('invalid'))->toBeTrue();
        expect(MailChecker::isInvalidFormat('user@example.com'))->toBeFalse();
    });
});
