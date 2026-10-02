<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->foreignId('attribute_value_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'attribute_value_id'], 'idx_prod_attr_val_unique');
            $table->index('attribute_value_id',                  'idx_prod_attr_val_attr_val');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attribute_values');
    }
};