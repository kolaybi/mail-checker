<?php

namespace KolayBi\Validation\Mail\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use KolayBi\Validation\Mail\MailChecker;

class ValidMailRule implements ValidationRule
{
    private const int MIN_LENGTH = 5;
    private const int MAX_LENGTH = 255;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->isValid($value)) {
            $fail('validation.email')->translate();
        }
    }

    public function isValid(mixed $mail): bool
    {
        if (Str::length($mail) < self::MIN_LENGTH || Str::length($mail) > self::MAX_LENGTH) {
            return false;
        }

        return MailChecker::isValid($mail);
    }
}
