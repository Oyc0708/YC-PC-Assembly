<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGpusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gpus', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 191)->unique();
            $table->string('manufacturer');
            $table->integer('memory')->nullable();
            $table->string('memory_type')->nullable();
            $table->integer('clock_speed')->nullable();
            $table->integer('tdp')->nullable();
            $table->integer('length_mm')->nullable();
            $table->integer('score')->default(70);
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
        Schema::dropIfExists('gpus');
    }
}
