<?php

namespace KolayBi\Validation\Mail;

use Exception;
use Illuminate\Support\Str;
use KolayBi\Validation\Mail\Exceptions\AbstractMailException;
use KolayBi\Validation\Mail\Exceptions\BlacklistedMailException;
use KolayBi\Validation\Mail\Exceptions\DisposableMailException;
use KolayBi\Validation\Mail\Exceptions\EmptyMailException;
use KolayBi\Validation\Mail\Exceptions\InvalidMailException;
use KolayBi\Validation\Mail\Services\LocalDomainService;

final class MailChecker
{
    /**
     * Perform the required checks for the mail address
     *
     * @throws AbstractMailException
     */
    public static function check(string $mail, bool $skipExternalControl = false): bool
    {
        if (empty($mail)) {
            throw new EmptyMailException();
        }

        $localDomainService = new LocalDomainService();

        if ($localDomainService->isWhitelisted($mail)) {
            return true;
        }

        if (!self::isValidFormat($mail)) {
            throw new InvalidMailException();
        }

        if ($localDomainService->isBlacklisted($mail)) {
            throw new BlacklistedMailException();
        }

        if ($localDomainService->isDisposable($mail)) {
            throw new DisposableMailException();
        }

        if ($skipExternalControl) {
            return true;
        }

        self::checkViaExternalServices($mail);

        return true;
    }

    /**
     * @uses MailChecker::check()
     */
    public static function isValid(string $mail): bool
    {
        try {
            return self::check($mail);
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Check the mail's validity against external services
     */
    private static function checkViaExternalServices(string $mail): void
    {
        // TODO
    }

    private static function isValidFormat(string $mail): bool
    {
        if (Str::contains($mail, '"')) {
            return false;
        }

        return false !== filter_var($mail, FILTER_VALIDATE_EMAIL);
    }
}
