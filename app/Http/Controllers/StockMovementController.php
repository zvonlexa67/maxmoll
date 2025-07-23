<?php

namespace App\Http\Controllers;

use App\Actions\StockMovement\IndexAction;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/stock-movements",
     *     summary="Просмотр истории движений остатков",
     *     tags={"Stock Movements"},
     *     @OA\Parameter(
     *         name="warehouse_id",
     *         in="query",
     *         description="ID склада",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="product_id",
     *         in="query",
     *         description="ID товара",
     *         required=false,
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Начальная дата (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-07-01")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="Конечная дата (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-07-22")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Количество записей на страницу",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ: список движений остатков",
     *         @OA\JsonContent(
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="product_id", type="integer", example=5),
     *                     @OA\Property(property="warehouse_id", type="integer", example=1),
     *                     @OA\Property(property="quantity_change", type="integer", example=-3),
     *                     @OA\Property(property="reason", type="string", example="order_completed"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-07-22T15:30:00Z")
     *                 )
     *             ),
     *             @OA\Property(property="total", type="integer", example=100)
     *         )
     *     )
     * )
     */
    public function index(Request $request, IndexAction $action): array
    {
        return $action($request->all());
    }
}
