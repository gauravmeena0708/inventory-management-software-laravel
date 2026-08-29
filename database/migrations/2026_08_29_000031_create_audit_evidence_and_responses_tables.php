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
        if (! Schema::hasTable('audit_evidence')) {
            Schema::create('audit_evidence', function (Blueprint $table) {
                $table->id();
                $table->foreignId('audit_observation_id')->constrained('audit_observations')->cascadeOnDelete();
                $table->foreignId('attachment_id')->nullable()->constrained('attachments')->nullOnDelete();
                $table->string('evidence_type', 50)->default('document');
                $table->text('description')->nullable();
                $table->string('source_model_type', 100)->nullable();
                $table->unsignedBigInteger('source_model_id')->nullable();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->index('audit_observation_id');
                $table->index('evidence_type');
                $table->index(['source_model_type', 'source_model_id']);
            });
        }

        if (! Schema::hasTable('audit_responses')) {
            Schema::create('audit_responses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('audit_observation_id')->constrained('audit_observations')->cascadeOnDelete();
                $table->string('response_type', 50); // office_reply, auditor_remark, supplementary_reply, higher_office_remark, final_decision
                $table->foreignId('organizational_unit_id')->constrained('organizational_units')->cascadeOnDelete();
                $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
                $table->text('body');
                $table->timestamp('submitted_at')->useCurrent();
                $table->foreignId('attachment_id')->nullable()->constrained('attachments')->nullOnDelete();
                $table->timestamps();

                $table->index('audit_observation_id');
                $table->index('response_type');
                $table->index('submitted_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_responses');
        Schema::dropIfExists('audit_evidence');
    }
};
