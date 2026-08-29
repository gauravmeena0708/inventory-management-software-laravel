<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tasks') && Schema::hasColumn('tasks', 'priority')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->string('priority', 32)->default('normal')->change();
            });
        }
    }

    public function down(): void
    {
        // The legacy integer priority cannot safely represent the modern labels.
    }
};
