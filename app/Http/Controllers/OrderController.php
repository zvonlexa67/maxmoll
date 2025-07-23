<?php

namespace App\Http\Controllers;

use App\Actions\Order\{ IndexAction, StoreAction, UpdateAction, CompleteAction, CancelAction, ResumeAction };
use App\Http\Requests\Order\{ StoreRequest, UpdateRequest };
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
    public function index(Request $request, IndexAction $action): array
    {
        return $action($request->all());
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
    public function store(StoreRequest $request, StoreAction $action): JsonResponse
    {
        return response()->json($action($request->all()), 201);
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
    public function update(UpdateRequest $request, int $id, UpdateAction $action): JsonResponse
    {
        return response()->json($action($id, $request->all()));
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
    public function complete(int $id, CompleteAction $action): JsonResponse
    {
        $result = $action($id);

        return response()->json($result[ 'ret' ], $result[ 'status' ]);
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
    public function cancel(int $id, CancelAction $action): JsonResponse
    {
        $result = $action($id);

        return response()->json($result[ 'ret' ], $result[ 'status' ]);
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
    public function resume(int $id, ResumeAction $action): JsonResponse
    {
        $result = $action($id);

        return response()->json($result[ 'ret' ], $result[ 'status' ]);
    }
}
