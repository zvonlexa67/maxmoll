<?php

namespace App\Actions\Order;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class StoreAction
{
    public function __invoke(array $data): array
    {
        DB::transaction(function () use ($data, &$order) {
            $order = Order::create([
                'customer' => $data[ 'customer' ],
                'warehouse_id' => $data[ 'warehouse_id' ],
                'status' => 'active',
            ]);

            foreach ($data[ 'items' ] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item[ 'product_id' ],
                    'count' => $item[ 'count' ],
                ]);
            }
        });

        return $order->load('items.product');

        
        // return response()->json($order->load('items.product'), 201);
    }
}