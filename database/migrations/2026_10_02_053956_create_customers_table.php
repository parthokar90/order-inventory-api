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
            $table->foreignId('user_id')
                  ->unique()                
                  ->constrained()
                  ->cascadeOnDelete();
            $table->string('phone')->nullable()->unique();
            $table->string('city')->nullable();
            $table->text('shipping_address')->nullable();
            $table->text('billing_address')->nullable();
            $table->timestamps();

            $table->index('user_id', 'idx_customers_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};