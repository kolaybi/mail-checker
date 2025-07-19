<?php

namespace KolayBi\Validation\Mail\Services;

interface ExternalMailProviderInterface
{
    /**
     * Check whether the given mail address is real or not
     */
    public function isReal(string $mail): bool;
}
