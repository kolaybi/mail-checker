<?php

namespace KolayBi\Validation\Mail;

use Exception;
use Illuminate\Support\Str;
use KolayBi\Validation\Mail\Exceptions\AbstractMailException;
use KolayBi\Validation\Mail\Exceptions\BlacklistedMailException;
use KolayBi\Validation\Mail\Exceptions\DisposableMailException;
use KolayBi\Validation\Mail\Exceptions\EmptyMailException;
use KolayBi\Validation\Mail\Exceptions\ExternalMailProviderException;
use KolayBi\Validation\Mail\Exceptions\InvalidMailException;
use KolayBi\Validation\Mail\Services\ExternalMailProviderInterface;
use KolayBi\Validation\Mail\Services\ExternalMailService;
use KolayBi\Validation\Mail\Services\LocalDomainService;
use Throwable;

final class MailChecker
{
    /**
     * Perform the required checks for the mail address
     *
     * @throws AbstractMailException
     */
    public static function check(string $mail, bool $skipExternalControl = false): void
    {
        if (empty($mail)) {
            throw new EmptyMailException();
        }

        $localDomainService = new LocalDomainService();

        if ($localDomainService->isWhitelisted($mail)) {
            return;
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
            return;
        }

        self::checkViaExternalServices($mail);
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
        if (Str::contains($mail, '"')) {
            return false;
        }

        return false !== filter_var($mail, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Check the mail's validity against external services
     *
     * @throws ExternalMailProviderException
     */
    private static function checkViaExternalServices(string $mail): void
    {
        $externalMailService = new ExternalMailService();

        $providers = $externalMailService->getProviders();
        foreach ($providers as $provider) {
            try {
                /** @var ExternalMailProviderInterface $provider */
                $provider = new $provider();
                if ($provider->isReal($mail)) {
                    break;
                }

                throw new ExternalMailProviderException();
            } catch (ExternalMailProviderException) {
                throw new ExternalMailProviderException();
            } catch (Throwable) {
                continue;
            }
        }
    }
}
