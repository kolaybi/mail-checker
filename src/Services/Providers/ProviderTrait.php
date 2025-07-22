<?php

namespace KolayBi\Validation\Mail\Services\Providers;

use KolayBi\Validation\Mail\Enums\ServiceType;
use KolayBi\Validation\Mail\Services\CacheService;

trait ProviderTrait
{
    public function __construct(
        private readonly array $config,
        private readonly ?CacheService $cacheService = null,
    ) {}

    public function isReal(string $mail): bool
    {
        $className = class_basename(__CLASS__);
        $serviceType = ServiceType::EXTERNAL->value;

        return $this->cacheService->remember(
            "{$serviceType}:{$className}:{$mail}",
            fn() => $this->performValidation($mail),
        );
    }
}
