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
        if (! Schema::hasTable('asset_disposals')) {
            Schema::create('asset_disposals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
                $table->foreignId('organizational_unit_id')->constrained('organizational_units')->cascadeOnDelete();
                $table->string('disposal_number', 50)->unique();

                $table->string('status', 32)->default('recommended');
                $table->date('recommendation_date')->nullable();
                $table->foreignId('recommended_by')->nullable()->constrained('users')->nullOnDelete();

                $table->string('committee_reference', 100)->nullable();
                $table->string('inspection_reference', 100)->nullable();

                $table->date('approval_date')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('approval_reference', 100)->nullable();

                $table->string('disposal_method', 50)->nullable();
                $table->string('disposal_vendor', 191)->nullable();
                $table->string('auction_reference', 100)->nullable();

                $table->date('disposal_date')->nullable();
                $table->decimal('sale_value', 15, 2)->nullable();

                $table->boolean('data_destruction_required')->default(false);
                $table->timestamp('data_destruction_completed_at')->nullable();
                $table->foreignId('data_destruction_certificate_attachment_id')->nullable()->constrained('attachments')->nullOnDelete();
                $table->foreignId('disposal_certificate_attachment_id')->nullable()->constrained('attachments')->nullOnDelete();

                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->index('asset_id');
                $table->index('organizational_unit_id');
                $table->index('status');
                $table->index('disposal_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_disposals');
    }
};
