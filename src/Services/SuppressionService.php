<?php

namespace KolayBi\Validation\Mail\Services;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use KolayBi\Validation\Mail\Contracts\SuppressionSyncInterface;
use KolayBi\Validation\Mail\Enums\SuppressionReason;
use KolayBi\Validation\Mail\Models\SuppressedEmail;
use RuntimeException;

class SuppressionService
{
    public function isEnabled(): bool
    {
        return (bool) config('kolaybi.mail-checker.suppression.enabled', false);
    }

    public function isSuppressed(string $mail): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        return $this->newQuery()->where('email', $this->normalize($mail))->exists();
    }

    public function suppress(
        string $mail,
        SuppressionReason $reason,
        ?string $source = null,
        ?CarbonInterface $suppressedAt = null,
        array $metadata = [],
    ): SuppressedEmail {
        $this->ensureEnabled();

        return $this->newQuery()->updateOrCreate(
            ['email' => $this->normalize($mail)],
            [
                'reason'        => $reason,
                'source'        => $source,
                'suppressed_at' => $suppressedAt ?? Carbon::now(),
                'metadata'      => $metadata ?: null,
            ],
        );
    }

    public function unsuppress(string $mail): bool
    {
        $this->ensureEnabled();

        $mail = $this->normalize($mail);

        if (app()->bound(SuppressionSyncInterface::class)) {
            app(SuppressionSyncInterface::class)->forget($mail);
        }

        return (bool) $this->newQuery()->where('email', $mail)->delete();
    }

    private function ensureEnabled(): void
    {
        if (!$this->isEnabled()) {
            throw new RuntimeException('Mail suppression is disabled; enable kolaybi.mail-checker.suppression to write.');
        }
    }

    private function newQuery(): Builder
    {
        /** @var class-string<SuppressedEmail> $model */
        $model = config('kolaybi.mail-checker.suppression.model', SuppressedEmail::class);

        return $model::query();
    }

    private function normalize(string $mail): string
    {
        return Str::lower(trim($mail));
    }
}
