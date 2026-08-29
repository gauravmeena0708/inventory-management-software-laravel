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
        // 1. consumables table
        if (!Schema::hasTable('consumables')) {
            Schema::create('consumables', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('sku')->nullable();
                $table->string('unit')->default('piece');
                $table->unsignedInteger('in_stock')->default(0);
                $table->unsignedInteger('min_quantity')->nullable();
                $table->unsignedInteger('max_quantity')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            Schema::table('consumables', function (Blueprint $table) {
                if (!Schema::hasColumn('consumables', 'sku')) {
                    $table->string('sku')->nullable()->after('name');
                }
                if (!Schema::hasColumn('consumables', 'unit')) {
                    $table->string('unit')->default('piece')->after('sku');
                }
                if (!Schema::hasColumn('consumables', 'in_stock')) {
                    $table->unsignedInteger('in_stock')->default(0)->after('unit');
                }
                if (!Schema::hasColumn('consumables', 'min_quantity')) {
                    $table->unsignedInteger('min_quantity')->nullable()->after('in_stock');
                }
                if (!Schema::hasColumn('consumables', 'max_quantity')) {
                    $table->unsignedInteger('max_quantity')->nullable()->after('min_quantity');
                }
                if (!Schema::hasColumn('consumables', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        // 2. entries table (immutable stock ledger)
        if (!Schema::hasTable('entries')) {
            Schema::create('entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('consumable_id')->constrained('consumables')->cascadeOnDelete();
                $table->string('type');
                $table->unsignedInteger('quantity');
                $table->unsignedInteger('stock_after');
                $table->foreignId('recipient_official_id')->nullable()->constrained('officials')->nullOnDelete();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('remarks')->nullable();
                $table->string('idempotency_key')->nullable()->unique();
                $table->unsignedBigInteger('legacy_id')->nullable()->unique();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['consumable_id', 'created_at']);
            });
        } else {
            Schema::table('entries', function (Blueprint $table) {
                if (!Schema::hasColumn('entries', 'quantity')) {
                    $table->unsignedInteger('quantity')->default(0)->after('type');
                }
                if (!Schema::hasColumn('entries', 'stock_after')) {
                    $table->unsignedInteger('stock_after')->default(0)->after('quantity');
                }
                if (!Schema::hasColumn('entries', 'recipient_official_id')) {
                    $table->foreignId('recipient_official_id')->nullable()->constrained('officials')->nullOnDelete()->after('stock_after');
                }
                if (!Schema::hasColumn('entries', 'recorded_by')) {
                    $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete()->after('recipient_official_id');
                }
                if (!Schema::hasColumn('entries', 'remarks')) {
                    $table->text('remarks')->nullable()->after('recorded_by');
                }
                if (!Schema::hasColumn('entries', 'idempotency_key')) {
                    $table->string('idempotency_key')->nullable()->unique()->after('remarks');
                }
                if (!Schema::hasColumn('entries', 'legacy_id')) {
                    $table->unsignedBigInteger('legacy_id')->nullable()->unique()->after('idempotency_key');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entries');
        Schema::dropIfExists('consumables');
    }
};
