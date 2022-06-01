<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDesktopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('desktops', function (Blueprint $table) {
            $table->id();
            $table->string('serial');
            $table->string('brand');
            $table->string('category');
            $table->foreignId('location_id')->references('id')->on('locations')->onDelete('cascade')->onUpdate('cascade')->nullable();         
            $table->boolean('active');
            $table->string('file')->nullable();
            $table->date('purchased')->nullable();
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
        Schema::dropIfExists('desktops');
    }
}
