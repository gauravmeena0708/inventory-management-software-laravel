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
        Schema::create('organizational_unit_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizational_unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('read_scope', ['local', 'descendants'])->default('local');
            $table->enum('write_scope', ['none', 'local', 'descendants'])->default('none');
            $table->dateTime('valid_from')->nullable();
            $table->dateTime('valid_until')->nullable();
            $table->timestamps();

            $table->unique(['organizational_unit_id', 'user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('default_organizational_unit_id')->nullable()->constrained('organizational_units')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['default_organizational_unit_id']);
            $table->dropColumn('default_organizational_unit_id');
        });

        Schema::dropIfExists('organizational_unit_user');
    }
};
