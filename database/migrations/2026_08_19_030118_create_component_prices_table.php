<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('component_prices', function (Blueprint $table) {
            $table->id();
            // Polymorphic relation to link to Cpu, Gpu, Ram, etc.
            $table->string('component_type');
            $table->uuid('component_id');
            
            $table->string('vendor');
            $table->decimal('price', 10, 2);
            $table->text('url');
            $table->boolean('in_stock')->default(true);
            $table->timestamps();

            $table->index(['component_type', 'component_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('component_prices');
    }
};