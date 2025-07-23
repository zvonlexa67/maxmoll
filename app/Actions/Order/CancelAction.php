<?php

namespace App\Actions\Order;

use App\Models\Order;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class CancelAction
{
    public function  __invoke(int $id): array
    {
        $result = [
            'ret' => null,
            'status' => 200
        ];

        $order = Order::with('items')->findOrFail($id);

        if ($order->status !== 'completed') {
            $result = [
                'ret' => ['error' => 'Отменить можно только выполненные заказы.'],
                'status' => 400
            ];
        } else {
            DB::transaction(function () use ($order) {
                foreach ($order->items as $item) {
                    $stock = Stock::where('warehouse_id', $order->warehouse_id)
                        ->where('product_id', $item->product_id)
                        ->lockForUpdate()
                        ->first();

                    $stock->increment('stock', $item->count);

                    StockMovement::create([
                        'product_id' => $item->product_id,
                        'warehouse_id' => $order->warehouse_id,
                        'change' => $item->count,
                    ]);
                }

                $order->update(['status' => 'canceled']);
            });

            $result[ 'ret' ] = $order->fresh();
        }

        return $result;
    }
}