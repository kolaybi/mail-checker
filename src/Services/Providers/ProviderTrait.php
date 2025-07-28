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
        if (null === $this->cacheService) {
            return $this->performValidation($mail);
        }

        $className = class_basename(get_class($this));
        $serviceType = ServiceType::EXTERNAL->value;

        return $this->cacheService->remember(
            "{$serviceType}:{$className}:{$mail}",
            fn() => $this->performValidation($mail),
        );
    }
}
