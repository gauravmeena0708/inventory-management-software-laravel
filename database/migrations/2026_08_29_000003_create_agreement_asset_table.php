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
        if (! Schema::hasTable('agreement_asset')) {
            Schema::create('agreement_asset', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agreement_id')->constrained('agreements')->cascadeOnDelete();
                $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
                $table->string('coverage_type', 50)->nullable();
                $table->date('coverage_start')->nullable();
                $table->date('coverage_end')->nullable();
                $table->string('sla_reference', 100)->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->unique(['agreement_id', 'asset_id']);
                $table->index('coverage_start');
                $table->index('coverage_end');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agreement_asset');
    }
};
