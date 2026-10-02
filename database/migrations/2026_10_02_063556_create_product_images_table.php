<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('product_variant_id')
                  ->nullable()
                  ->constrained('product_variants')
                  ->nullOnDelete();

            $table->string('path');                               
            $table->string('disk')->default('s3');                 
            $table->string('alt_text')->nullable();
            $table->enum('image_type', ['primary', 'gallery'])
                  ->default('gallery');
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(
                ['product_id', 'image_type', 'sort_order'],
                'idx_pimg_product_type_sort'
            );
            $table->index(
                ['product_variant_id', 'image_type'],
                'idx_pimg_variant_type'
            );
            $table->index(
                ['product_id', 'image_type'],
                'idx_pimg_product_type'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};