<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBuildsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('builds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->string('name')->default('My Custom Build');
            $table->text('description')->nullable();
            
            // Component Foreign Keys (Stored as strings to handle UUIDs from OpenDB)
            $table->string('cpu_id')->nullable();
            $table->string('cooler_id')->nullable();
            $table->string('mobo_id')->nullable();
            $table->string('ram_id')->nullable();
            $table->string('gpu_id')->nullable();
            $table->string('psu_id')->nullable();
            $table->string('case_id')->nullable();
            
            // Cached calculations for rapid UI rendering
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->integer('overall_score')->default(0);
            
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
        Schema::dropIfExists('builds');
    }
}