<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePcCasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pc_cases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 500);
            $table->string('manufacturer');
            $table->integer('max_gpu_length_mm')->nullable();
            $table->string('form_factor')->nullable();
            $table->decimal('price', 8, 2)->default(0.00);
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
        Schema::dropIfExists('pc_cases');
    }
}
