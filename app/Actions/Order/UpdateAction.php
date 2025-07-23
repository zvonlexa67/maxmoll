<?php

namespace App\Actions\Order;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class UpdateAction
{
    public function __invoke(int $id, array $data): array
    {
        $order = Order::where('status', 'active')->findOrFail($id);

        DB::transaction(function () use ($data, $order) {
            if (isset($data[ 'customer' ])) {
                $order->update(['customer' => $data[ 'customer' ]]);
            }

            if (isset($data[ 'items' ])) {
                $order->items()->delete();

                foreach ($data[ 'items' ] as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'count' => $item['count'],
                    ]);
                }
            }
        });

        // return response()->json($order->load('items.product'));
        return $order->load('items.product');
    }
}