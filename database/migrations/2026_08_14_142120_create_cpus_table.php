<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cpus', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100)->unique();
            $table->string('manufacturer');
            $table->integer('cores')->default(0);
            $table->integer('threads')->default(0);
            $table->string('socket');
            $table->integer('tdp')->nullable();
            $table->boolean('has_igpu')->default(true);
            $table->decimal('base_clock', 8, 2)->nullable();
            $table->decimal('boost_clock', 8, 2)->nullable();
            $table->decimal('price', 8, 2)->default(0.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cpus');
    }
};