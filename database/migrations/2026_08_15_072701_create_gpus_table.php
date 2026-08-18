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
            $table->string('name', 500);
            $table->string('manufacturer');
            $table->integer('vram_gb')->nullable();
            $table->integer('memory')->nullable(); // VRAM
            $table->integer('clock_speed')->nullable(); // Core clock
            $table->integer('tdp')->nullable();
            $table->integer('length_mm')->nullable();
            $table->integer('score')->default(70); // Mock benchmark
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
