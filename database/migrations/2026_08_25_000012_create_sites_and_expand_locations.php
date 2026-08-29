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
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('altitude', 8, 2)->nullable();
            $table->json('geofence_geojson')->nullable();
            $table->string('timezone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('organizational_unit_site', function (Blueprint $table) {
            $table->foreignId('organizational_unit_id')->constrained('organizational_units')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            
            $table->primary(['organizational_unit_id', 'site_id']);
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable()->after('id')->constrained('sites')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->after('site_id')->constrained('locations')->nullOnDelete();
            $table->string('code')->nullable()->after('parent_id');
            $table->string('location_type')->default('OTHER')->after('code');
            $table->string('path')->nullable()->index()->after('location_type');
            $table->string('level_number')->nullable()->after('path');
            $table->json('geometry_geojson')->nullable()->after('level_number');
            $table->decimal('local_x', 10, 2)->nullable()->after('geometry_geojson');
            $table->decimal('local_y', 10, 2)->nullable()->after('local_x');
            $table->decimal('local_z', 10, 2)->nullable()->after('local_y');
            $table->boolean('is_restricted')->default(false)->after('local_z');
            $table->boolean('is_active')->default(true)->after('is_restricted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
            $table->dropForeign(['parent_id']);
            $table->dropColumn([
                'site_id', 'parent_id', 'code', 'location_type', 'path', 'level_number',
                'geometry_geojson', 'local_x', 'local_y', 'local_z', 'is_restricted', 'is_active'
            ]);
        });

        Schema::dropIfExists('organizational_unit_site');
        Schema::dropIfExists('sites');
    }
};
