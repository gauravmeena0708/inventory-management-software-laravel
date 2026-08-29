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
        // 1. agreements table
        if (!Schema::hasTable('agreements')) {
            Schema::create('agreements', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('agency');
                $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
                $table->string('type', 100);
                $table->date('expiry')->nullable();
                $table->decimal('annual_cost', 15, 2)->nullable();
                $table->char('currency', 3)->default('INR');
                $table->unsignedTinyInteger('billing_interval_months')->nullable();
                $table->date('billing_anchor_date')->nullable();
                $table->date('paid_till')->nullable();
                $table->text('remarks')->nullable();
                $table->json('legacy_payload')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('expiry');
            });
        } else {
            Schema::table('agreements', function (Blueprint $table) {
                if (!Schema::hasColumn('agreements', 'currency')) {
                    $table->char('currency', 3)->default('INR')->after('annual_cost');
                }
                if (!Schema::hasColumn('agreements', 'billing_interval_months')) {
                    $table->unsignedTinyInteger('billing_interval_months')->nullable()->after('currency');
                }
                if (!Schema::hasColumn('agreements', 'billing_anchor_date')) {
                    $table->date('billing_anchor_date')->nullable()->after('billing_interval_months');
                }
                if (!Schema::hasColumn('agreements', 'legacy_payload')) {
                    $table->json('legacy_payload')->nullable()->after('remarks');
                }
                if (!Schema::hasColumn('agreements', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        // 2. payments table
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agreement_id')->constrained('agreements')->cascadeOnDelete();
                $table->decimal('amount', 15, 2)->nullable();
                $table->char('currency', 3)->default('INR');
                $table->date('due_date');
                $table->date('paid_date')->nullable();
                $table->string('status', 32)->default('pending');
                $table->string('invoice_number')->nullable();
                $table->text('remarks')->nullable();
                $table->string('schedule_key')->unique();
                $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedBigInteger('legacy_id')->nullable()->unique();
                $table->timestamps();

                $table->index(['agreement_id', 'due_date']);
                $table->index(['status', 'due_date']);
            });
        } else {
            Schema::table('payments', function (Blueprint $table) {
                if (Schema::hasColumn('payments', 'name')) {
                    $table->string('name')->nullable()->change();
                }
                if (Schema::hasColumn('payments', 'amount')) {
                    $table->decimal('amount', 15, 2)->nullable()->change();
                }
                if (Schema::hasColumn('payments', 'due_date')) {
                    $table->date('due_date')->nullable(false)->change();
                }
                if (Schema::hasColumn('payments', 'pending')) {
                    $table->boolean('pending')->nullable()->default(true)->change();
                }
                if (!Schema::hasColumn('payments', 'currency')) {
                    $table->char('currency', 3)->default('INR')->after('amount');
                }
                if (!Schema::hasColumn('payments', 'paid_date')) {
                    $table->date('paid_date')->nullable()->after('due_date');
                }
                if (!Schema::hasColumn('payments', 'status')) {
                    $table->string('status', 32)->default('pending')->after('paid_date');
                }
                if (!Schema::hasColumn('payments', 'invoice_number')) {
                    $table->string('invoice_number')->nullable()->after('status');
                }
                if (!Schema::hasColumn('payments', 'remarks')) {
                    $table->text('remarks')->nullable()->after('invoice_number');
                }
                if (!Schema::hasColumn('payments', 'schedule_key')) {
                    $table->string('schedule_key')->nullable()->unique()->after('remarks');
                }
                if (!Schema::hasColumn('payments', 'completed_by')) {
                    $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete()->after('schedule_key');
                }
                if (!Schema::hasColumn('payments', 'legacy_id')) {
                    $table->unsignedBigInteger('legacy_id')->nullable()->unique()->after('completed_by');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('agreements');
    }
};
