<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCpusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cpus', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('name', 500);
        $table->string('manufacturer');
        $table->integer('cores')->default(0);
        $table->string('socket');
        $table->integer('tdp')->nullable();
        $table->decimal('base_clock', 8, 2)->nullable();
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
        Schema::dropIfExists('cpus');
    }
}
