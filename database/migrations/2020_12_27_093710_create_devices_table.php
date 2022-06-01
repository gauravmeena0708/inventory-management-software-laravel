<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('oem');
            $table->string('sublocation');
            $table->string('partcode');
            $table->string('serial');
            $table->string('location')->nullable();
            $table->date('purchase_date')->nullable();
            $table->unsignedBigInteger('cost')->nullable();
            $table->date('support_date')->nullable();
            $table->date('amc_start')->nullable();
            $table->date('amc_end')->nullable();
            $table->string('remark')->nullable();
            $table->unsignedBigInteger('amc_cost')->nullable();
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
        Schema::dropIfExists('devices');
    }
}
