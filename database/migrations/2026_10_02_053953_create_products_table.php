<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                  ->constrained()
                  ->restrictOnDelete(); 
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes(); 
            $table->timestamps();

            $table->index(['category_id', 'is_active'],       'idx_products_category_active');

            $table->index(['is_active', 'created_at'],        'idx_products_active_created');
    
            $table->index(['deleted_at', 'is_active'],        'idx_products_deleted_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};