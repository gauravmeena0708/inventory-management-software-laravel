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
            $table->unsignedInteger('type');
            $table->unsignedInteger('amount');
            $table->unsignedInteger('stock');
            $table->foreignId('issuer_id')->references('id')->on('officials')->onDelete('cascade')->onUpdate('cascade');         
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
