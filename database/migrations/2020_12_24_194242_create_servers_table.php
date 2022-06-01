<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->string('asset_sr')->nullable();
            $table->string('location')->nullable();
            $table->string('covered')->nullable();
            $table->string('asset_tag')->nullable();
            $table->date('purchase_date')->nullable();
            $table->unsignedBigInteger('purchase_cost')->nullable();
            $table->string('description')->nullable();
            $table->date('end_of_sale')->nullable();
            $table->date('end_of_support')->nullable();
            $table->date('contract_expiry')->nullable();
            $table->unsignedInteger('amc_cost')->nullable();
            $table->string('contract_type')->nullable();
            $table->string('contracthash')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('terms')->nullable();
            $table->string('duplicated')->nullable();
            $table->string('timeout')->nullable();
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
        Schema::dropIfExists('servers');
    }
}
