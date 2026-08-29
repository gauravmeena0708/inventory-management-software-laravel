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
        if (! Schema::hasTable('asset_transfers')) {
            Schema::create('asset_transfers', function (Blueprint $table) {
                $table->id();
                $table->string('transfer_number', 50)->unique();
                $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();

                $table->foreignId('from_organizational_unit_id')->constrained('organizational_units')->cascadeOnDelete();
                $table->foreignId('to_organizational_unit_id')->constrained('organizational_units')->cascadeOnDelete();

                $table->foreignId('from_location_id')->nullable()->constrained('locations')->nullOnDelete();
                $table->foreignId('to_location_id')->nullable()->constrained('locations')->nullOnDelete();

                $table->foreignId('initiated_by')->constrained('users')->cascadeOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

                $table->string('status', 32)->default('draft');

                $table->timestamp('requested_at')->useCurrent();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('dispatched_at')->nullable();
                $table->timestamp('received_at')->nullable();

                $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();

                $table->text('condition_at_dispatch')->nullable();
                $table->text('condition_at_receipt')->nullable();

                $table->string('reference_number', 100)->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->index('asset_id');
                $table->index('from_organizational_unit_id');
                $table->index('to_organizational_unit_id');
                $table->index('status');
                $table->index('transfer_number');
                $table->index('requested_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_transfers');
    }
};
