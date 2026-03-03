<?php

use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use KolayBi\Validation\Mail\Rules\ValidMailRule;

describe('isValid', function () {
    it('returns true for valid emails', function () {
        $rule = new ValidMailRule();

        expect($rule->isValid('user@example.com'))->toBeTrue();
    });

    it('rejects emails shorter than 5 characters', function (string $email) {
        $rule = new ValidMailRule();

        expect($rule->isValid($email))->toBeFalse();
    })->with([
        'four chars'  => 'a@b.',
        'three chars' => 'a@b',
        'one char'    => 'a',
        'empty'       => '',
    ]);

    it('rejects emails longer than 255 characters', function () {
        $rule = new ValidMailRule();

        $email = str_repeat('a', 244) . '@example.com';

        expect(Str::length($email))->toBeGreaterThan(255);
        expect($rule->isValid($email))->toBeFalse();
    });

    it('accepts emails at exactly 5 characters', function () {
        $rule = new ValidMailRule();

        expect($rule->isValid('a@b.c'))->toBeTrue();
    });

    it('rejects invalid email formats', function () {
        $rule = new ValidMailRule();

        expect($rule->isValid('not-an-email'))->toBeFalse();
    });

    it('rejects blacklisted domains', function () {
        $rule = new ValidMailRule();

        expect($rule->isValid('user@spam.com'))->toBeFalse();
    });

    it('rejects disposable domains', function () {
        $rule = new ValidMailRule();

        expect($rule->isValid('user@tempmail.com'))->toBeFalse();
    });
});

describe('validate', function () {
    it('does not call fail for valid emails', function () {
        $rule = new ValidMailRule();
        $called = false;

        $rule->validate('email', 'user@example.com', function () use (&$called) {
            $called = true;
        });

        expect($called)->toBeFalse();
    });

    it('calls fail for invalid emails', function () {
        $rule = new ValidMailRule();
        $called = false;

        $fail = Mockery::mock(Fluent::class);
        $fail->shouldReceive('translate')->once();

        $rule->validate('email', 'invalid', function () use ($fail, &$called) {
            $called = true;

            return $fail;
        });

        expect($called)->toBeTrue();
    });
});
