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
        if (! Schema::hasTable('asset_lifecycle_events')) {
            Schema::create('asset_lifecycle_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
                $table->string('event_type', 50);
                $table->timestamp('occurred_at')->useCurrent();
                $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();

                $table->string('from_status', 32)->nullable();
                $table->string('to_status', 32)->nullable();

                $table->foreignId('from_organizational_unit_id')->nullable()->constrained('organizational_units')->nullOnDelete();
                $table->foreignId('to_organizational_unit_id')->nullable()->constrained('organizational_units')->nullOnDelete();

                $table->foreignId('from_location_id')->nullable()->constrained('locations')->nullOnDelete();
                $table->foreignId('to_location_id')->nullable()->constrained('locations')->nullOnDelete();

                $table->string('reference_type', 100)->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->string('reference_number', 100)->nullable();

                $table->text('remarks')->nullable();
                $table->json('metadata')->nullable();

                $table->timestamps();

                $table->index('asset_id');
                $table->index('event_type');
                $table->index('occurred_at');
                $table->index(['reference_type', 'reference_id']);
                $table->index('actor_user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_lifecycle_events');
    }
};
