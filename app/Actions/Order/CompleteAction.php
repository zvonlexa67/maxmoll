<?php

namespace App\Actions\Order;

use App\Models\Order;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class CompleteAction
{
    public function __invoke(int $id): array
    {
        $result = [
            'ret' => null,
            'status' => 200,
        ];

        $order = Order::with('items')->findOrFail($id);

        if ($order->status !== 'active') {
            $result['status'] = 400;
            $result['ret'] = ['error' => 'Только активный заказ можно завершить'];
        } else {
            DB::transaction(function () use ($order) {
                foreach ($order->items as $item) {
                    $stock = Stock::where('warehouse_id', $order->warehouse_id)
                        ->where('product_id', $item->product_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$stock || $stock->stock < $item->count) {
                        throw new \Exception("Недостаточно запасов для продукта ID {$item->product_id}");
                    }

                    $stock->decrement('stock', $item->count);

                    StockMovement::create([
                        'product_id' => $item->product_id,
                        'warehouse_id' => $order->warehouse_id,
                        'change' => -$item->count,
                    ]);
                }

                $order->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            });

            $result['ret'] = $order->fresh();
        }

        return $result;

        // return response()->json($order->fresh());

    }
}