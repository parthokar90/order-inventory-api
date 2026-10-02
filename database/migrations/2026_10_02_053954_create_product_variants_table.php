<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->decimal('price', 12, 2);
            $table->decimal('compare_at_price', 12, 2)->nullable(); 
            $table->decimal('cost_price', 12, 2)->nullable();       
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            
            $table->index(['product_id', 'is_active'],  'idx_variants_product_active');
            
            $table->index('sku',                        'idx_variants_sku');
            
            $table->index(['price', 'is_active'],       'idx_variants_price_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};