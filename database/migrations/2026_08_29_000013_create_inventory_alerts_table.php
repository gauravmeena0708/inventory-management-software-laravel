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
        if (! Schema::hasTable('inventory_alerts')) {
            Schema::create('inventory_alerts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organizational_unit_id')->constrained('organizational_units')->cascadeOnDelete();
                $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
                $table->foreignId('agreement_id')->nullable()->constrained('agreements')->nullOnDelete();
                $table->string('alert_type', 50);
                $table->string('severity', 20)->default('info');
                $table->string('title', 191);
                $table->text('message');
                $table->date('due_date')->nullable();
                $table->boolean('is_acknowledged')->default(false);
                $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('acknowledged_at')->nullable();
                $table->timestamps();

                $table->index('organizational_unit_id');
                $table->index('alert_type');
                $table->index('severity');
                $table->index('is_acknowledged');
                $table->index('due_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_alerts');
    }
};
