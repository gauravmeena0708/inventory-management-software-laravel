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
        // 1. locations table
        if (!Schema::hasTable('locations')) {
            Schema::create('locations', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('sublocation')->nullable();
                $table->string('building')->nullable();
                $table->string('floor')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            Schema::table('locations', function (Blueprint $table) {
                if (!Schema::hasColumn('locations', 'name')) {
                    $table->string('name')->default('')->after('id');
                }
                if (!Schema::hasColumn('locations', 'sublocation')) {
                    $table->string('sublocation')->nullable()->after('name');
                }
                if (!Schema::hasColumn('locations', 'building')) {
                    $table->string('building')->nullable()->after('sublocation');
                }
                if (!Schema::hasColumn('locations', 'floor')) {
                    $table->string('floor')->nullable()->after('building');
                }
                if (!Schema::hasColumn('locations', 'description')) {
                    $table->text('description')->nullable()->after('floor');
                }
                if (!Schema::hasColumn('locations', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        // 2. manufacturers table
        if (!Schema::hasTable('manufacturers')) {
            Schema::create('manufacturers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('support_contact')->nullable();
                $table->string('website')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            Schema::table('manufacturers', function (Blueprint $table) {
                if (!Schema::hasColumn('manufacturers', 'support_contact')) {
                    $table->string('support_contact')->nullable()->after('name');
                }
                if (!Schema::hasColumn('manufacturers', 'website')) {
                    $table->string('website')->nullable()->after('support_contact');
                }
                if (!Schema::hasColumn('manufacturers', 'remarks')) {
                    $table->text('remarks')->nullable()->after('website');
                }
                if (!Schema::hasColumn('manufacturers', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        // 3. files table
        if (!Schema::hasTable('files')) {
            Schema::create('files', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('efile_number')->nullable();
                $table->string('physical_name')->nullable();
                $table->string('physical_number')->nullable();
                $table->string('subject')->nullable();
                $table->string('division')->nullable();
                $table->date('opened_at')->nullable();
                $table->unsignedBigInteger('legacy_id')->nullable()->unique();
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            Schema::table('files', function (Blueprint $table) {
                if (!Schema::hasColumn('files', 'efile_number')) {
                    $table->string('efile_number')->nullable()->after('name');
                }
                if (!Schema::hasColumn('files', 'physical_name')) {
                    $table->string('physical_name')->nullable()->after('efile_number');
                }
                if (!Schema::hasColumn('files', 'physical_number')) {
                    $table->string('physical_number')->nullable()->after('physical_name');
                }
                if (!Schema::hasColumn('files', 'subject')) {
                    $table->string('subject')->nullable()->after('physical_number');
                }
                if (!Schema::hasColumn('files', 'division')) {
                    $table->string('division')->nullable()->after('subject');
                }
                if (!Schema::hasColumn('files', 'opened_at')) {
                    $table->date('opened_at')->nullable()->after('division');
                }
                if (!Schema::hasColumn('files', 'legacy_id')) {
                    $table->unsignedBigInteger('legacy_id')->nullable()->unique()->after('opened_at');
                }
                if (!Schema::hasColumn('files', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        // 4. attachments table
        if (!Schema::hasTable('attachments')) {
            Schema::create('attachments', function (Blueprint $table) {
                $table->id();
                $table->string('attachable_type');
                $table->unsignedBigInteger('attachable_id');
                $table->string('disk')->default('private');
                $table->string('path');
                $table->string('original_name');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('size');
                $table->char('checksum', 64);
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['attachable_type', 'attachable_id']);
                $table->index('uploaded_by');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
