<?php

namespace KolayBi\Validation\Mail;

use Exception;
use Illuminate\Support\Str;
use KolayBi\Validation\Mail\Exceptions\AbstractMailException;
use KolayBi\Validation\Mail\Exceptions\BlacklistedMailException;
use KolayBi\Validation\Mail\Exceptions\DisposableMailException;
use KolayBi\Validation\Mail\Exceptions\EmptyMailException;
use KolayBi\Validation\Mail\Exceptions\InvalidMailException;
use KolayBi\Validation\Mail\Services\ExternalMailService;
use KolayBi\Validation\Mail\Services\LocalDomainService;

final class MailChecker
{
    private static ?LocalDomainService $localDomainService = null;
    private static ?ExternalMailService $externalMailService = null;

    /**
     * Perform the required checks for the mail address
     *
     * @throws AbstractMailException
     */
    public static function check(string $mail, bool $skipExternalControl = false): void
    {
        $mail = trim(strtolower($mail));

        if (empty($mail)) {
            throw new EmptyMailException();
        }

        // Use singleton pattern to avoid creating new instances on each call
        self::$localDomainService ??= new LocalDomainService();

        if (self::$localDomainService->isWhitelisted($mail)) {
            return;
        }

        if (!self::isValidFormat($mail)) {
            throw new InvalidMailException();
        }

        if (self::$localDomainService->isBlacklisted($mail)) {
            throw new BlacklistedMailException();
        }

        if (self::$localDomainService->isDisposable($mail)) {
            throw new DisposableMailException();
        }

        if ($skipExternalControl) {
            return;
        }

        // Only initialize external service when actually needed
        self::$externalMailService ??= new ExternalMailService();
        self::$externalMailService->checkDeliverability($mail);
    }

    /**
     * @uses MailChecker::check()
     */
    public static function isValid(string $mail): bool
    {
        try {
            self::check($mail);

            return true;
        } catch (Exception) {
            return false;
        }
    }

    private static function isValidFormat(string $mail): bool
    {
        if (Str::length($mail) > 320 || Str::contains($mail, '"')) {
            return false;
        }

        return false !== filter_var($mail, FILTER_VALIDATE_EMAIL);
    }
}
