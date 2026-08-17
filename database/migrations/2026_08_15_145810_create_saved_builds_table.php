<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSavedBuildsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('saved_builds', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('name');
        
        // Store the UUIDs of the selected parts
        $table->uuid('cpu_id')->nullable();
        $table->uuid('cooler_id')->nullable();
        $table->uuid('mobo_id')->nullable();
        $table->uuid('ram_id')->nullable();
        $table->uuid('gpu_id')->nullable();
        $table->uuid('psu_id')->nullable();
        $table->uuid('case_id')->nullable();
        
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
        Schema::dropIfExists('saved_builds');
    }
}
