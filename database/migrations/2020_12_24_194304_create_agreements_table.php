<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgreementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agreements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('agency');
            $table->foreignId('file_id')->nullable()->references('id')->on('files')->nullOnDelete()->cascadeOnUpdate();
            $table->string('type');
            $table->date('expiry')->nullable();
            $table->decimal('annual_cost', 15, 2)->nullable();
            $table->unsignedInteger('frequency')->nullable();
            $table->date('paid_till')->nullable();
            $table->string('remarks')->nullable();
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
        Schema::dropIfExists('agreements');
    }
}
