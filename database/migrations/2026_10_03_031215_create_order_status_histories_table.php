<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->enum('from_status', [
                'pending',
                'processing',
                'completed',
                'cancelled',
                'refunded',
            ])->nullable(); 

            $table->enum('to_status', [
                'pending',
                'processing',
                'completed',
                'cancelled',
                'refunded',
            ]);

            $table->string('changed_by_type')->nullable(); 
            $table->unsignedBigInteger('changed_by_id')->nullable(); 

            $table->text('note')->nullable(); 

            // Immutable log — no updated_at needed
            $table->timestamp('created_at')->useCurrent();

            $table->index(['order_id', 'created_at'], 'idx_osh_order_created');

            // Admin dashboard — recent status changes by type
            $table->index(['to_status', 'created_at'], 'idx_osh_to_status_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
    }
};