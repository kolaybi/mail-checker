<?php

namespace KolayBi\Validation\Mail\Contracts;

interface SuppressionSyncInterface
{
    /**
     * Remove the address from the upstream (ESP-side) suppression state.
     * Implementations MUST throw on failure so unsuppress fails closed.
     */
    public function forget(string $email): void;
}
