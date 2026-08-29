<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add transitional, nullable ownership to records that do not inherit it.
     *
     * Payments inherit ownership from their agreement. Attachments inherit it
     * from their attachable model. Null ownership therefore remains possible
     * while legacy data is reconciled, but visibility queries fail closed.
     */
    public function up(): void
    {
        foreach (['files', 'agreements', 'tasks'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'organizational_unit_id')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->foreignId('organizational_unit_id')
                        ->nullable()
                        ->constrained('organizational_units')
                        ->restrictOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['tasks', 'agreements', 'files'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'organizational_unit_id')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->dropConstrainedForeignId('organizational_unit_id');
                });
            }
        }
    }
};
