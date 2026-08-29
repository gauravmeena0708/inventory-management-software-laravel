<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDevcatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('devcats', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('salary')->nullable();
            $table->string('exp')->nullable();
            $table->unsignedInteger('dev')->nullable();
            $table->unsignedInteger('collab')->nullable();
            $table->string('qualification')->nullable();
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
        Schema::dropIfExists('devcats');
    }
}
