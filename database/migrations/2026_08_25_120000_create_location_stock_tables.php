<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('consumable_id')->constrained('consumables')->restrictOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('min_quantity')->nullable();
            $table->unsignedInteger('max_quantity')->nullable();
            $table->timestamps();

            $table->unique(['consumable_id', 'location_id']);
            $table->index(['location_id', 'quantity']);
        });

        Schema::create('stock_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('consumable_id')->constrained('consumables')->restrictOnDelete();
            $table->foreignId('source_location_id')->nullable()->constrained('locations')->restrictOnDelete();
            $table->foreignId('destination_location_id')->nullable()->constrained('locations')->restrictOnDelete();
            $table->string('transaction_type', 32);
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('source_stock_after')->nullable();
            $table->unsignedInteger('destination_stock_after')->nullable();
            $table->foreignId('recipient_official_id')->nullable()->constrained('officials')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('idempotency_key')->unique();
            $table->foreignId('legacy_entry_id')->nullable()->unique()->constrained('entries')->restrictOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['consumable_id', 'created_at']);
            $table->index(['source_location_id', 'created_at']);
            $table->index(['destination_location_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transactions');
        Schema::dropIfExists('stock_balances');
    }
};
