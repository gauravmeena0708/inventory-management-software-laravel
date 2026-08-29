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
        if (! Schema::hasTable('acquisitions')) {
            Schema::create('acquisitions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organizational_unit_id')->constrained('organizational_units')->cascadeOnDelete();
                $table->string('acquisition_type', 50)->default('gem');
                $table->string('vendor_name', 191)->nullable();
                $table->foreignId('vendor_id')->nullable()->constrained('developers')->nullOnDelete();

                $table->string('gem_order_number', 100)->nullable();
                $table->string('purchase_order_number', 100)->nullable();
                $table->string('sanction_reference', 100)->nullable();
                $table->string('invoice_number', 100)->nullable();
                $table->date('invoice_date')->nullable();
                $table->string('grn_number', 100)->nullable();
                $table->date('receipt_date')->nullable();

                $table->decimal('total_value', 15, 2)->nullable();
                $table->char('currency', 3)->default('INR');

                $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->index('organizational_unit_id');
                $table->index('gem_order_number');
                $table->index('purchase_order_number');
                $table->index('invoice_number');
                $table->index('grn_number');
            });
        }

        if (! Schema::hasTable('acquisition_assets')) {
            Schema::create('acquisition_assets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('acquisition_id')->constrained('acquisitions')->cascadeOnDelete();
                $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
                $table->decimal('unit_cost', 15, 2)->nullable();
                $table->integer('quantity_component')->default(1);
                $table->timestamps();

                $table->unique(['acquisition_id', 'asset_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acquisition_assets');
        Schema::dropIfExists('acquisitions');
    }
};
