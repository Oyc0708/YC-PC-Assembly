<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMotherboardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('motherboards', function (Blueprint $table) {
            // Keep your existing ID and UUID setup...
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('manufacturer')->nullable();
            $table->string('socket')->nullable();
            $table->string('ram_type')->nullable();
            
            $table->integer('max_ram')->nullable();
            $table->integer('ram_slots')->nullable();
            
            $table->string('form_factor')->nullable();
            $table->decimal('price', 10, 2)->nullable();
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
        Schema::dropIfExists('motherboards');
    }
}
