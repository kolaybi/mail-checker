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

final class MailChecker
{
    private ?LocalDomainService $localDomainService = null;
    private ?ExternalMailService $externalMailService = null;

    /**
     * Perform the actual validation check
     *
     * @throws AbstractMailException
     */
    private function performCheck(string $mail, bool $skipExternalControl): void
    {
        $mail = trim(strtolower($mail));

        if (empty($mail)) {
            throw new EmptyMailException();
        }

        $localService = $this->getLocalDomainService();

        if ($localService->isWhitelisted($mail)) {
            return;
        }

        if (!self::isValidFormat($mail)) {
            throw new InvalidMailException();
        }

        if ($localService->isBlacklisted($mail)) {
            throw new BlacklistedMailException();
        }

        if ($localService->isDisposable($mail)) {
            throw new DisposableMailException();
        }

        if ($skipExternalControl) {
            return;
        }

        $this->getExternalMailService()->checkDeliverability($mail);
    }

    private function getLocalDomainService(): LocalDomainService
    {
        return $this->localDomainService ??= new LocalDomainService();
    }

    private function getExternalMailService(): ExternalMailService
    {
        return $this->externalMailService ??= new ExternalMailService();
    }

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
        self::getInstance()->performCheck($mail, $skipExternalControl);
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
     * Check if an email address is whitelisted
     *
     * Whitelisted emails are considered trusted and bypass all validation checks.
     * This method uses lazy initialization to create the LocalDomainService instance
     * only when needed, improving performance by avoiding unnecessary object creation.
     *
     * @param string $mail The email address to check
     *
     * @return bool True if the email domain is in the whitelist, false otherwise
     */
    public static function isWhitelisted(string $mail): bool
    {
        return self::getInstance()->getLocalDomainService()->isWhitelisted($mail);
    }

    /**
     * Check if an email address is blacklisted
     *
     * Blacklisted emails are explicitly blocked domains that should never be allowed.
     *
     * @param string $mail The email address to check
     *
     * @return bool True if the email domain is in the blacklist, false otherwise
     */
    public static function isBlacklisted(string $mail): bool
    {
        return self::getInstance()->getLocalDomainService()->isBlacklisted($mail);
    }

    /**
     * Check if an email address is from a disposable email provider
     *
     * Disposable emails are temporary email addresses from services like 10minutemail,
     * guerrillamail, etc. These are often used to bypass registration requirements
     * and should typically be blocked for legitimate user accounts.
     *
     * @param string $mail The email address to check
     *
     * @return bool True if the email domain is a known disposable email provider, false otherwise
     */
    public static function isDisposable(string $mail): bool
    {
        return self::getInstance()->getLocalDomainService()->isDisposable($mail);
    }

    /**
     * Validate email format using comprehensive checks
     *
     * This method performs multiple validation layers before using PHP's built-in
     * email validation to catch common issues and improve performance by failing
     * fast on obviously invalid emails.
     *
     * @param string $mail The email address to validate
     *
     * @return bool True if the email format is valid, false otherwise
     */
    public static function isValidFormat(string $mail): bool
    {
        // Perform fast preliminary checks to reject obviously invalid emails
        // This avoids the overhead of filter_var() for clearly invalid input
        if (
            // RFC 5321 specifies maximum email length of 320 characters
            Str::length($mail) > 320

            // Minimum realistic email length (a@b.c = 5 characters)
            || Str::length($mail) < 5

            // Must contain exactly one @ symbol
            || !str_contains($mail, '@')

            // Cannot start with @ symbol (invalid format)
            || str_starts_with($mail, '@')

            // Cannot end with @ symbol (missing domain)
            || str_ends_with($mail, '@')

            // Must contain exactly one @ symbol (not zero, not multiple)
            || 1 !== substr_count($mail, '@')

            // Reject emails containing quotes (often used in complex/problematic formats)
            || Str::contains($mail, '"')
        ) {
            return false;
        }

        // Use PHP's built-in email validation as the final check
        // filter_var with FILTER_VALIDATE_EMAIL follows RFC 5322 standards
        // Returns false on invalid email, so we check that it's NOT false
        return false !== filter_var($mail, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Check if an email address is NOT whitelisted
     *
     * This is the inverse of isWhitelisted() - returns true if the email domain
     * is NOT in the whitelist, meaning it requires validation checks.
     *
     * @param string $mail The email address to check
     *
     * @return bool True if the email domain is NOT in the whitelist, false otherwise
     */
    public static function isNotWhitelisted(string $mail): bool
    {
        return !self::isWhitelisted($mail);
    }

    /**
     * Check if an email address is NOT blacklisted
     *
     * This is the inverse of isBlacklisted() - returns true if the email domain
     * is NOT in the blacklist, meaning it's potentially allowed.
     *
     * @param string $mail The email address to check
     *
     * @return bool True if the email domain is NOT in the blacklist, false otherwise
     */
    public static function isNotBlacklisted(string $mail): bool
    {
        return !self::isBlacklisted($mail);
    }

    /**
     * Check if an email address is NOT from a disposable email provider
     *
     * This is the inverse of isDisposable() - returns true if the email domain
     * is NOT a known disposable email provider, meaning it's from a permanent
     * email service.
     *
     * @param string $mail The email address to check
     *
     * @return bool True if the email domain is NOT a disposable email provider, false otherwise
     */
    public static function isNotDisposable(string $mail): bool
    {
        return !self::isDisposable($mail);
    }

    /**
     * Check if an email format is NOT valid
     *
     * This is the inverse of isValidFormat() - returns true if the email format
     * fails validation checks, useful for quickly identifying invalid emails.
     *
     * @param string $mail The email address to validate
     *
     * @return bool True if the email format is NOT valid, false otherwise
     */
    public static function isInvalidFormat(string $mail): bool
    {
        return !self::isValidFormat($mail);
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
        } catch (Exception $e) {
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
     */
    public static function clearAllCaches(): bool
    {
        $instance = self::getInstance();
        $localResult = $instance->getLocalDomainService()->clearCache();
        $externalResult = $instance->getExternalMailService()->clearCache();

        return $localResult && $externalResult;
    }

    /**
     * Get a fresh MailChecker instance
     * This ensures services are created with current configuration
     */
    private static function getInstance(): self
    {
        return new self();
    }
}
