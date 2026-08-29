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
        // 1. officials table
        if (!Schema::hasTable('officials')) {
            Schema::create('officials', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('title')->nullable();
                $table->string('designation')->nullable();
                $table->string('department')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index('email');
                $table->index('department');
            });
        } else {
            Schema::table('officials', function (Blueprint $table) {
                if (!Schema::hasColumn('officials', 'department')) {
                    $table->string('department')->nullable()->after('designation');
                }
                if (!Schema::hasColumn('officials', 'email')) {
                    $table->string('email')->nullable()->after('department');
                }
                if (!Schema::hasColumn('officials', 'phone')) {
                    $table->string('phone')->nullable()->after('email');
                }
                if (!Schema::hasColumn('officials', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        // 2. devcats table
        if (!Schema::hasTable('devcats')) {
            Schema::create('devcats', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('salary')->nullable();
                $table->string('exp')->nullable();
                $table->unsignedInteger('dev')->nullable();
                $table->unsignedInteger('collab')->nullable();
                $table->string('qualification')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            Schema::table('devcats', function (Blueprint $table) {
                if (!Schema::hasColumn('devcats', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        // 3. developers table (text columns for AES ciphertext storage)
        if (!Schema::hasTable('developers')) {
            Schema::create('developers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->foreignId('reporting_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('category_id')->nullable()->constrained('devcats')->nullOnDelete();
                $table->text('phone')->nullable();
                $table->text('email')->nullable();
                $table->text('salary')->nullable();
                $table->string('status', 32)->default('active');
                $table->text('remarks')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('status');
                $table->index('reporting_id');
                $table->index('category_id');
            });
        } else {
            Schema::table('developers', function (Blueprint $table) {
                if (!Schema::hasColumn('developers', 'salary')) {
                    $table->text('salary')->nullable()->after('email');
                }
                if (!Schema::hasColumn('developers', 'remarks')) {
                    $table->text('remarks')->nullable()->after('status');
                }
                if (!Schema::hasColumn('developers', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        // 4. tasks table
        if (!Schema::hasTable('tasks')) {
            Schema::create('tasks', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
                $table->string('priority', 32)->default('normal');
                $table->string('status', 32)->default('pending');
                $table->date('due_date')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('status');
                $table->index('assigned_to');
                $table->index('file_id');
                $table->index('due_date');
            });
        } else {
            Schema::table('tasks', function (Blueprint $table) {
                if (!Schema::hasColumn('tasks', 'title')) {
                    $table->string('title')->nullable()->after('id');
                }
                if (!Schema::hasColumn('tasks', 'description')) {
                    $table->text('description')->nullable()->after('title');
                }
                if (!Schema::hasColumn('tasks', 'assigned_to')) {
                    $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete()->after('description');
                }
                if (!Schema::hasColumn('tasks', 'due_date')) {
                    $table->date('due_date')->nullable()->after('status');
                }
                if (!Schema::hasColumn('tasks', 'remarks')) {
                    $table->text('remarks')->nullable()->after('due_date');
                }
                if (!Schema::hasColumn('tasks', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('developers');
        Schema::dropIfExists('devcats');
        Schema::dropIfExists('officials');
    }
};
