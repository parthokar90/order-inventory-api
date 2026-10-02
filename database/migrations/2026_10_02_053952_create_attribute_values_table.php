<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->string('value');          
            $table->string('slug');           
            $table->timestamps();

            $table->unique(['attribute_id', 'slug'], 'idx_attr_val_unique');

            $table->index('attribute_id', 'idx_attribute_values_attr_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
    }
};