<?php

namespace KolayBi\Validation\Mail;

use Exception;
use KolayBi\Validation\Mail\Exceptions\AbstractMailException;
use KolayBi\Validation\Mail\Exceptions\EmptyMailException;

final class MailChecker
{
    /**
     * Perform the required checks for the email address
     *
     * @throws AbstractMailException
     */
    public static function checkMail(string $email): bool
    {
        if (empty($email)) {
            throw new EmptyMailException();
        }

        return true;
    }

    /**
     * Use MailChecker::checkMail() internally, but returns boolean only
     *
     * @see MailChecker::checkMail()
     */
    public static function isValidEmail(string $email): bool
    {
        try {
            return self::checkMail($email);
        } catch (Exception) {
            return false;
        }
    }
}
