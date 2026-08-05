<?php

namespace KolayBi\Validation\Mail\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use KolayBi\Validation\Mail\Enums\SuppressionReason;

/**
 * @property string            $id
 * @property Carbon            $created_at
 * @property Carbon            $updated_at
 * @property string            $email
 * @property SuppressionReason $reason
 * @property ?string           $source
 * @property Carbon            $suppressed_at
 * @property ?array            $metadata
 */
class SuppressedEmail extends Model
{
    use HasUlids;

    protected $guarded = [];

    public function getConnectionName(): ?string
    {
        return config('kolaybi.mail-checker.suppression.connection');
    }

    public function getTable(): string
    {
        $table = config('kolaybi.mail-checker.suppression.table', 'mail_suppressions');
        $schema = config('kolaybi.mail-checker.suppression.schema');

        return $schema ? "{$schema}.{$table}" : $table;
    }

    protected function casts(): array
    {
        return [
            'reason'        => SuppressionReason::class,
            'suppressed_at' => 'datetime',
            'metadata'      => 'array',
        ];
    }
}
