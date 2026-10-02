<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')
                  ->nullable()
                  ->constrained('categories')
                  ->nullOnDelete(); 
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('depth')->default(0); 
            $table->timestamps();

            $table->index(['parent_id', 'is_active'], 'idx_categories_parent_active');
            $table->index(['is_active', 'depth'],     'idx_categories_active_depth');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};