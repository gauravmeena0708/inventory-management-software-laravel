<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaptopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('laptops', function (Blueprint $table) {
            $table->id();
            $table->string('serial');
            $table->string('brand');
            $table->string('category');
            $table->foreignId('official_id')->references('id')->on('officials')->onDelete('cascade')->onUpdate('cascade')->nullable();         
            $table->boolean('active');
            $table->string('file')->nullable();
            $table->date('purchased')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('laptops');
    }
}
