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
        // 1. assets table
        if (!Schema::hasTable('assets')) {
            Schema::create('assets', function (Blueprint $table) {
                $table->id();
                $table->string('legacy_source', 32)->nullable();
                $table->unsignedBigInteger('legacy_id')->nullable();
                $table->string('asset_tag', 100)->nullable();
                $table->string('name');
                $table->string('asset_type', 32)->default('other');
                $table->foreignId('manufacturer_id')->nullable()->constrained('manufacturers')->nullOnDelete();
                $table->string('manufacturer_name_legacy')->nullable();
                $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
                $table->string('location_text_legacy')->nullable();
                $table->foreignId('assigned_official_id')->nullable()->constrained('officials')->nullOnDelete();
                $table->string('status', 32)->default('in_stock');
                $table->string('serial_number', 191)->nullable();
                $table->string('model_number', 191)->nullable();
                $table->string('part_code', 191)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('mac_address', 45)->nullable();
                $table->string('operating_system', 100)->nullable();
                $table->text('description')->nullable();
                $table->json('specifications')->nullable();
                $table->date('purchase_date')->nullable();
                $table->decimal('purchase_cost', 15, 2)->nullable();
                $table->char('currency', 3)->default('INR');
                $table->date('warranty_expiry')->nullable();
                $table->date('end_of_sale')->nullable();
                $table->date('end_of_support')->nullable();
                $table->date('amc_start')->nullable();
                $table->date('amc_end')->nullable();
                $table->decimal('amc_cost', 15, 2)->nullable();
                $table->string('contract_type', 100)->nullable();
                $table->string('contract_reference')->nullable();
                $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
                $table->string('legacy_file_reference')->nullable();
                $table->text('remarks')->nullable();
                $table->json('legacy_payload')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['legacy_source', 'legacy_id'], 'assets_legacy_source_legacy_id_unique');
                $table->index(['asset_type', 'status']);
                $table->index('serial_number');
                $table->index('asset_tag');
                $table->index('location_id');
                $table->index('assigned_official_id');
                $table->index('warranty_expiry');
                $table->index('end_of_support');
                $table->index('amc_end');
            });
        }

        // 2. asset_assignments table
        if (!Schema::hasTable('asset_assignments')) {
            Schema::create('asset_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
                $table->foreignId('official_id')->nullable()->constrained('officials')->nullOnDelete();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('assigned_at')->useCurrent();
                $table->timestamp('returned_at')->nullable();
                $table->foreignId('return_recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('source', 32)->default('application');
                $table->text('condition_out')->nullable();
                $table->text('condition_in')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->index('asset_id');
                $table->index('official_id');
                $table->index('assigned_by');
                $table->index('return_recorded_by');
                $table->index('assigned_at');
                $table->index('returned_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_assignments');
        Schema::dropIfExists('assets');
    }
};
