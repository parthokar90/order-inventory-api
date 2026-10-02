<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Order;
use Illuminate\Http\Request;

class EnsureIdempotency
{
    public function handle(Request $request, Closure $next)
    {
        $key = $request->header('X-Idempotency-Key');

        if (!$key) {
            return response()->json([
                'success' => false,
                'message' => 'X-Idempotency-Key header is required.'
            ], 400);
        }

        // Check if an order was already processed with this key
        $existingOrder = Order::where('idempotency_key', $key)->first();

        if ($existingOrder) {
            return response()->json([
                'success' => true,
                'message' => 'Order retrieved from idempotency cache.',
                'data'    => $existingOrder->load(['items', 'payment', 'customer'])
            ], 200);
        }

        return $next($request);
    }
}