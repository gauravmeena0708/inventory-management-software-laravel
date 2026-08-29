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
        if (! Schema::hasTable('maintenance_tickets')) {
            Schema::create('maintenance_tickets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
                $table->foreignId('organizational_unit_id')->constrained('organizational_units')->cascadeOnDelete();
                $table->string('ticket_number', 50)->unique();
                $table->string('issue_category', 100)->nullable();
                $table->text('issue_description');

                $table->foreignId('reported_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('reported_by_official_id')->nullable()->constrained('officials')->nullOnDelete();
                $table->timestamp('reported_at')->useCurrent();

                $table->string('status', 32)->default('open');
                $table->string('severity', 20)->default('medium');

                $table->foreignId('agreement_id')->nullable()->constrained('agreements')->nullOnDelete();
                $table->foreignId('vendor_id')->nullable()->constrained('developers')->nullOnDelete();
                $table->string('vendor_name', 191)->nullable();
                $table->string('external_reference', 100)->nullable();

                $table->timestamp('sent_for_repair_at')->nullable();
                $table->timestamp('repair_started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('returned_at')->nullable();

                $table->text('diagnosis')->nullable();
                $table->text('resolution')->nullable();

                $table->decimal('cost', 15, 2)->nullable();
                $table->char('currency', 3)->default('INR');
                $table->boolean('covered_under_warranty')->default(false);
                $table->boolean('covered_under_amc')->default(false);

                $table->integer('downtime_minutes')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->index('asset_id');
                $table->index('organizational_unit_id');
                $table->index('status');
                $table->index('severity');
                $table->index('reported_at');
                $table->index('ticket_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_tickets');
    }
};
