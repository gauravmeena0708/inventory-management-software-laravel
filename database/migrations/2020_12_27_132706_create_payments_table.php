<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('agreement_id')->references('id')->on('agreements')->onDelete('cascade')->onUpdate('cascade');         
            $table->date('due_date')->nullable();
            $table->unsignedBigInteger('amount')->nullable();
            $table->string('remark')->nullable();
            $table->boolean('pending');
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
        Schema::dropIfExists('payments');
    }
}
