<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable()->unique(); 
            $table->json('shipping_address')->nullable();  
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index('email', 'idx_customers_email');
            $table->index(['is_active', 'created_at'], 'idx_customers_active_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};