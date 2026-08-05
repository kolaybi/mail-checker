<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::connection($this->connectionName())->create($this->tableName(), function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamps();
            $table->string('email')->unique();
            $table->string('reason');
            $table->string('source')->nullable();
            $table->timestamp('suppressed_at');
            $table->json('metadata')->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connectionName())->dropIfExists($this->tableName());
    }

    private function connectionName(): ?string
    {
        return config('kolaybi.mail-checker.suppression.connection');
    }

    private function tableName(): string
    {
        $table = config('kolaybi.mail-checker.suppression.table', 'mail_suppressions');
        $schema = config('kolaybi.mail-checker.suppression.schema');

        return $schema ? "{$schema}.{$table}" : $table;
    }
};
