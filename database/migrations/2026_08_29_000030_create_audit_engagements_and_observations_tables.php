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
        if (! Schema::hasTable('audit_engagements')) {
            Schema::create('audit_engagements', function (Blueprint $table) {
                $table->id();
                $table->string('audit_number', 50)->unique();
                $table->string('audit_type', 50)->default('internal_audit');

                $table->foreignId('audited_organizational_unit_id')->constrained('organizational_units')->cascadeOnDelete();
                $table->foreignId('auditing_organizational_unit_id')->nullable()->constrained('organizational_units')->nullOnDelete();

                $table->date('audit_from')->nullable();
                $table->date('audit_to')->nullable();
                $table->timestamp('fieldwork_started_at')->nullable();
                $table->timestamp('fieldwork_completed_at')->nullable();

                $table->string('status', 32)->default('planned');
                $table->foreignId('lead_auditor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

                $table->text('scope_text')->nullable();
                $table->date('report_date')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->index('audited_organizational_unit_id');
                $table->index('status');
                $table->index('audit_number');
            });
        }

        if (! Schema::hasTable('audit_observations')) {
            Schema::create('audit_observations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('audit_engagement_id')->constrained('audit_engagements')->cascadeOnDelete();
                $table->string('para_number', 50);
                $table->string('title', 191);
                $table->text('finding');

                $table->string('severity', 20)->default('medium'); // low, medium, high, critical
                $table->string('risk_category', 100)->nullable();
                $table->decimal('financial_implication', 15, 2)->nullable();
                $table->char('currency', 3)->default('INR');

                $table->string('status', 32)->default('draft'); // draft, issued, reply_received, under_examination, partly_settled, outstanding, settled, dropped
                $table->timestamp('issued_at')->nullable();
                $table->date('due_date')->nullable();

                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('closed_at')->nullable();
                $table->text('closure_reason')->nullable();
                $table->timestamps();

                $table->index('audit_engagement_id');
                $table->index('para_number');
                $table->index('status');
                $table->index('severity');
                $table->index('issued_at');
                $table->index('due_date');
            });
        }

        if (! Schema::hasTable('audit_observation_assets')) {
            Schema::create('audit_observation_assets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('audit_observation_id')->constrained('audit_observations')->cascadeOnDelete();
                $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['audit_observation_id', 'asset_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_observation_assets');
        Schema::dropIfExists('audit_observations');
        Schema::dropIfExists('audit_engagements');
    }
};
