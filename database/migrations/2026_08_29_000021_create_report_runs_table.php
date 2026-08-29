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
        if (! Schema::hasTable('report_runs')) {
            Schema::create('report_runs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('report_definition_id')->constrained('report_definitions')->cascadeOnDelete();
                $table->foreignId('organizational_unit_id')->constrained('organizational_units')->cascadeOnDelete();
                $table->string('scope_type', 32)->default('local'); // local, descendants, specific_subordinate

                $table->date('reporting_from')->nullable();
                $table->date('reporting_to')->nullable();
                $table->date('as_on_date')->nullable();
                $table->timestamp('data_cutoff_at')->useCurrent();

                $table->json('parameters')->nullable();
                $table->string('status', 32)->default('generated');

                $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
                $table->timestamp('generated_at')->useCurrent();

                $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('prepared_at')->nullable();

                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('verified_at')->nullable();

                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();

                $table->integer('version')->default(1);

                $table->json('snapshot_data');
                $table->foreignId('rendered_file_attachment_id')->nullable()->constrained('attachments')->nullOnDelete();

                $table->char('sha256', 64)->nullable();
                $table->foreignId('supersedes_report_run_id')->nullable()->constrained('report_runs')->nullOnDelete();

                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->index('report_definition_id');
                $table->index('organizational_unit_id');
                $table->index('status');
                $table->index('generated_at');
                $table->index('sha256');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_runs');
    }
};
