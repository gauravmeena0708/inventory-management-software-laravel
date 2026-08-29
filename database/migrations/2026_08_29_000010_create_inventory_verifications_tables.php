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
        if (! Schema::hasTable('inventory_verifications')) {
            Schema::create('inventory_verifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organizational_unit_id')->constrained('organizational_units')->cascadeOnDelete();
                $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
                $table->string('name', 191);
                $table->string('financial_year', 20)->nullable();
                $table->string('verification_type', 50)->default('annual');
                $table->date('planned_from')->nullable();
                $table->date('planned_to')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('certified_at')->nullable();
                $table->string('status', 32)->default('draft');
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('remarks')->nullable();
                $table->json('snapshot_population')->nullable();
                $table->timestamps();

                $table->index('organizational_unit_id');
                $table->index('site_id');
                $table->index('status');
                $table->index('financial_year');
            });
        }

        if (! Schema::hasTable('inventory_verification_items')) {
            Schema::create('inventory_verification_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_verification_id')->constrained('inventory_verifications')->cascadeOnDelete();
                $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();

                $table->foreignId('expected_organizational_unit_id')->nullable()->constrained('organizational_units')->nullOnDelete();
                $table->foreignId('observed_organizational_unit_id')->nullable()->constrained('organizational_units')->nullOnDelete();

                $table->foreignId('expected_location_id')->nullable()->constrained('locations')->nullOnDelete();
                $table->foreignId('observed_location_id')->nullable()->constrained('locations')->nullOnDelete();

                $table->foreignId('expected_official_id')->nullable()->constrained('officials')->nullOnDelete();
                $table->foreignId('observed_official_id')->nullable()->constrained('officials')->nullOnDelete();

                $table->string('result', 50)->default('verified');
                $table->foreignId('verified_by')->constrained('users')->cascadeOnDelete();
                $table->timestamp('verified_at')->useCurrent();
                $table->text('remarks')->nullable();
                $table->foreignId('photo_attachment_id')->nullable()->constrained('attachments')->nullOnDelete();
                $table->boolean('is_reconciled')->default(false);
                $table->timestamp('reconciled_at')->nullable();
                $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('inventory_verification_id');
                $table->index('asset_id');
                $table->index('result');
                $table->index('is_reconciled');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_verification_items');
        Schema::dropIfExists('inventory_verifications');
    }
};
