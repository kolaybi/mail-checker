<?php

namespace KolayBi\Validation\Mail\Services\Providers;

use KolayBi\Validation\Mail\Exceptions\ExternalMailProviderException;

interface ExternalMailProviderInterface
{
    /**
     * Check whether the given mail address is real or not
     *
     * @throws ExternalMailProviderException
     */
    public function isReal(string $mail): bool;
}
