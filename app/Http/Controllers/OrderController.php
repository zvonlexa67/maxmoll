<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    // Список заказов (с фильтрами и пагинацией)

    /**
     * @OA\Get(
     *     path="/api/orders",
     *     summary="Получить список заказов",
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Фильтр по статусу",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Order::with(['items.product', 'warehouse']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('customer')) {
            $query->where('customer', 'like', '%' . $request->customer . '%');
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return $query->paginate($request->get('per_page', 10));
    }

    // Создание заказа
    /**
     * @OA\Post(
     *     path="/api/orders",
     *     summary="Создать заказ",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="customer", type="string"),
     *             @OA\Property(property="warehouse_id", type="integer"),
     *             @OA\Property(
     *                 property="items",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="product_id", type="integer"),
     *                     @OA\Property(property="count", type="integer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer' => 'required|string',
            'warehouse_id' => 'required|exists:warehouses,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.count' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request, &$order) {
            $order = Order::create([
                'customer' => $request->customer,
                'warehouse_id' => $request->warehouse_id,
                'status' => 'active',
            ]);

            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'count' => $item['count'],
                ]);
            }
        });

        return response()->json($order->load('items.product'), 201);
    }

    // Обновление заказа (только данные клиента и позиции)
    /**
     * @OA\Put(
     *     path="/api/orders/{id}",
     *     summary="Обновить заказ (только клиент и товары)",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID заказа",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Данные для обновления заказа",
     *         @OA\JsonContent(
     *             required={"customer","items"},
     *             @OA\Property(property="customer", type="string", example="Иван Иванов"),
     *             @OA\Property(
     *                 property="items",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="product_id", type="integer", example=1),
     *                     @OA\Property(property="count", type="integer", example=2)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешное обновление заказа",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="customer", type="string"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(
     *                 property="items",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="product_id", type="integer"),
     *                     @OA\Property(property="count", type="integer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Ошибка запроса"),
     *     @OA\Response(response=404, description="Заказ не найден"),
     *     @OA\Response(response=422, description="Ошибка валидации")
     * )
     */
    public function update(Request $request, $id)
    {
        $order = Order::where('status', 'active')->findOrFail($id);

        $request->validate([
            'customer' => 'sometimes|string',
            'items' => 'sometimes|array|min:1',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.count' => 'required_with:items|integer|min:1',
        ]);

        DB::transaction(function () use ($request, $order) {
            if ($request->has('customer')) {
                $order->update(['customer' => $request->customer]);
            }

            if ($request->has('items')) {
                $order->items()->delete();

                foreach ($request->items as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'count' => $item['count'],
                    ]);
                }
            }
        });

        return response()->json($order->load('items.product'));
    }

    // Завершить заказ (проверка остатков)
    /**
     * @OA\Post(
     *     path="/api/orders/{id}/complete",
     *     summary="Завершить заказ (списание остатков)",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID заказа для завершения",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Заказ успешно завершен",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="status", type="string", example="completed"),
     *             @OA\Property(property="completed_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка (например, недостаточно остатков или заказ уже не активен)",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Not enough stock for product ID 1")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Заказ не найден"
     *     )
     * )
     */
    public function complete($id)
    {
        $order = Order::with('items')->findOrFail($id);

        if ($order->status !== 'active') {
            return response()->json(['error' => 'Только активный заказ можно завершить'], 400);
        }

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

        return response()->json($order->fresh());
    }

    // Отменить заказ (возврат остатков)
    /**
     * @OA\Post(
     *     path="/api/orders/{id}/cancel",
     *     summary="Отменить заказ (возврат остатков на склад)",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID заказа для отмены",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Заказ успешно отменён и остатки возвращены",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="status", type="string", example="canceled")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка — можно отменить только завершённый заказ",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Only completed orders can be canceled")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Заказ не найден"
     *     )
     * )
     */
    public function cancel($id)
    {
        $order = Order::with('items')->findOrFail($id);

        if ($order->status !== 'completed') {
            return response()->json(['error' => 'Отменить можно только выполненные заказы.'], 400);
        }

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

        return response()->json($order->fresh());
    }

    // Возобновить заказ (проверка остатков)
    /**
     * @OA\Post(
     *     path="/api/orders/{id}/resume",
     *     summary="Возобновить отменённый заказ (снова списать остатки)",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID отменённого заказа",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Заказ успешно возобновлён и остатки списаны",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="status", type="string", example="completed"),
     *             @OA\Property(property="completed_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка: недостаточно остатков или заказ не отменён",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Not enough stock for product ID 3")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Заказ не найден"
     *     )
     * )
     */
    public function resume($id)
    {
        $order = Order::with('items')->findOrFail($id);

        if ($order->status !== 'canceled') {
            return response()->json(['error' => 'СХЕМЫ УКАЗАННЫХ ТАБЛИЦ МЕНЯТЬ НЕЛЬЗЯ!'], 400);
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $stock = Stock::where('warehouse_id', $order->warehouse_id)
                            ->where('product_id', $item->product_id)
                            ->lockForUpdate()
                            ->first();

                if (!$stock || $stock->stock < $item->count) {
                    throw new \Exception("Недостаточно запасов для возобновления производства продукта ID {$item->product_id}");
                }

                $stock->decrement('stock', $item->count);

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'warehouse_id' => $order->warehouse_id,
                    'change' => -$item->count,
                ]);
            }

            $order->update(['status' => 'active']);
        });

        return response()->json($order->fresh());
    }
}
