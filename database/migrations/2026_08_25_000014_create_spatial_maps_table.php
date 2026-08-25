<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spatial_maps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('attachment_id')->constrained('attachments')->restrictOnDelete();
            $table->string('map_type', 32);
            $table->unsignedInteger('version');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->decimal('scale', 16, 6)->nullable();
            $table->decimal('origin_x', 16, 6)->nullable();
            $table->decimal('origin_y', 16, 6)->nullable();
            $table->decimal('origin_z', 16, 6)->nullable();
            $table->decimal('rotation', 9, 6)->nullable();
            $table->string('coordinate_system', 64)->nullable();
            $table->json('calibration')->nullable();
            $table->boolean('is_current')->default(true);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['location_id', 'map_type', 'version']);
            $table->index(['location_id', 'map_type', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spatial_maps');
    }
};
