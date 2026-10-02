<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();      
            $table->foreignId('customer_id')
                  ->constrained()
                  ->restrictOnDelete();                    
            $table->string('idempotency_key')->unique();    
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->enum('status', [
                'pending',     
                'processing',   
                'completed',    
                'cancelled',    
                'refunded',     
            ])->default('pending');
            $table->json('shipping_address');               
            $table->text('notes')->nullable();
            $table->timestamps();

            // Customer order history 
            $table->index(['customer_id', 'status'],       'idx_orders_customer_status');
            // Admin order management by date
            $table->index(['status', 'created_at'],        'idx_orders_status_created');
            // Idempotency lookup
            $table->index('idempotency_key',               'idx_orders_idempotency');
            // Reporting: date range queries
            $table->index('created_at',                    'idx_orders_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};