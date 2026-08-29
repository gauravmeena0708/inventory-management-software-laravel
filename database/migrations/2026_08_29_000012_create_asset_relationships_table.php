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
        if (! Schema::hasTable('asset_relationships')) {
            Schema::create('asset_relationships', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_asset_id')->constrained('assets')->cascadeOnDelete();
                $table->foreignId('child_asset_id')->constrained('assets')->cascadeOnDelete();
                $table->string('relationship_type', 50)->default('component_of');
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->unique(['parent_asset_id', 'child_asset_id', 'relationship_type'], 'asset_rel_unique');
                $table->index('parent_asset_id');
                $table->index('child_asset_id');
                $table->index('relationship_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_relationships');
    }
};
