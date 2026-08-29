<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDevelopersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('developers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('category_id')->nullable()->references('id')->on('devcats')->nullOnDelete()->cascadeOnUpdate();
            $table->string('status', 32)->default('active');
            $table->text('phone')->nullable();
            $table->text('email')->nullable();
            $table->date('joining_date')->nullable(); 
            $table->string('expertise')->nullable();
            $table->foreignId('reporting_id')->nullable()->references('id')->on('users')->nullOnDelete()->cascadeOnUpdate();
            $table->date('exit_date')->nullable();
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('hometown')->nullable();
            $table->string('qualification')->nullable();
            $table->string('subject')->nullable();
            $table->string('education_institute')->nullable();
            $table->string('education_location')->nullable();
            $table->string('aadhaar')->nullable();
            $table->string('remark')->nullable();
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
        Schema::dropIfExists('developers');
    }
}
