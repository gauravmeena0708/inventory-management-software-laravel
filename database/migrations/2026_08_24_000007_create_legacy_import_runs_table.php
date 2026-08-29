<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('legacy_import_runs')) {
            Schema::create('legacy_import_runs', function (Blueprint $table) {
                $table->id();
                $table->string('source_fingerprint')->nullable();
                $table->boolean('dry_run')->default(false);
                $table->timestamp('started_at')->useCurrent();
                $table->timestamp('completed_at')->nullable();
                $table->json('counts')->nullable();
                $table->json('anomalies')->nullable();
                $table->string('status', 32)->default('running');
                $table->text('error_summary')->nullable();
                $table->timestamps();

                $table->index('status');
                $table->index('started_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legacy_import_runs');
    }
};
