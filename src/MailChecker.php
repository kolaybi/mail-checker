<?php

namespace KolayBi\Validation\Mail;

use Exception;
use Illuminate\Support\Str;
use KolayBi\Validation\Mail\Exceptions\AbstractMailException;
use KolayBi\Validation\Mail\Exceptions\BlacklistedMailException;
use KolayBi\Validation\Mail\Exceptions\DisposableMailException;
use KolayBi\Validation\Mail\Exceptions\EmptyMailException;
use KolayBi\Validation\Mail\Exceptions\ExternalMailProviderException;
use KolayBi\Validation\Mail\Exceptions\InaccessibleMailException;
use KolayBi\Validation\Mail\Exceptions\InvalidMailException;
use KolayBi\Validation\Mail\Services\ExternalMailService;
use KolayBi\Validation\Mail\Services\LocalDomainService;
use Psr\SimpleCache\InvalidArgumentException;

final class MailChecker
{
    private static ?LocalDomainService $localDomainService = null;
    private static ?ExternalMailService $externalMailService = null;

    /**
     * Perform comprehensive email validation checks
     *
     * Validation order:
     * 1. Empty check
     * 2. Whitelist check (if whitelisted, passes immediately)
     * 3. Format validation
     * 4. Blacklist check
     * 5. Disposable email check
     * 6. External deliverability check (if enabled)
     *
     * @param string $mail                The email address to validate
     * @param bool   $skipExternalControl Skip external API validation for faster local-only checks
     *
     * @throws AbstractMailException
     * @throws BlacklistedMailException When email domain is blacklisted
     * @throws DisposableMailException When email is from a disposable domain
     * @throws EmptyMailException When email is empty
     * @throws ExternalMailProviderException When all external providers fail
     * @throws InaccessibleMailException When external validation fails
     * @throws InvalidMailException When email format is invalid
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
     * Validate the provided email address and determine if it meets the required criteria
     *
     * @param string $mail                the email address to be validated
     * @param bool   $skipExternalControl whether to bypass external validation checks
     *
     * @return bool returns true if the email is valid; otherwise, false
     */
    public static function isValid(string $mail, bool $skipExternalControl = false): bool
    {
        try {
            self::check($mail, $skipExternalControl);

            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Get detailed validation result with specific failure reason
     */
    public static function getValidationResult(string $mail, bool $skipExternalControl = false): array
    {
        try {
            self::check($mail, $skipExternalControl);

            return [
                'valid' => true,
            ];
        } catch (AbstractMailException $e) {
            return [
                'valid'   => false,
                'reason'  => class_basename($e),
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate multiple emails at once
     */
    public static function validateBatch(array $emails, bool $skipExternalControl = false): array
    {
        $results = [];
        foreach ($emails as $email) {
            $results[$email] = self::getValidationResult($email, $skipExternalControl);
        }

        return $results;
    }

    /**
     * Clear all caches (useful for testing or cache management)
     *
     * @throws InvalidArgumentException
     */
    public static function clearAllCaches(): bool
    {
        $localResult = self::$localDomainService?->clearCache() ?? true;
        $externalResult = self::$externalMailService?->clearCache() ?? true;

        return $localResult && $externalResult;
    }

    /**
     * Reset singleton instances (useful for testing)
     */
    public static function reset(): void
    {
        self::$localDomainService = null;
        self::$externalMailService = null;
    }

    private static function isValidFormat(string $mail): bool
    {
        if (
            Str::length($mail) > 320
            || Str::length($mail) < 5
            || !str_contains($mail, '@')
            || str_starts_with($mail, '@')
            || str_ends_with($mail, '@')
            || 1 !== substr_count($mail, '@')
            || Str::contains($mail, '"')
        ) {
            return false;
        }

        return false !== filter_var($mail, FILTER_VALIDATE_EMAIL);
    }
}
