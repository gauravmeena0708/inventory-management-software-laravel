<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consumable_id')->references('id')->on('consumables')->onDelete('cascade')->onUpdate('cascade');         
            $table->string('type');
            $table->unsignedInteger('amount')->nullable();
            $table->unsignedInteger('stock')->nullable();
            $table->foreignId('issuer_id')->nullable()->references('id')->on('officials')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('entries');
    }
}
