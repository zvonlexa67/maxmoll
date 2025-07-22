<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\{WarehouseController, ProductController, OrderController, StockMovementController};

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/warehouses', [WarehouseController::class, 'index']);
Route::get('/products-with-stock', [ProductController::class, 'productsWithStock']);
Route::get('/orders', [OrderController::class, 'index']);
Route::post('/orders', [OrderController::class, 'store']);
Route::put('/orders/{id}', [OrderController::class, 'update']);
Route::post('/orders/{id}/complete', [OrderController::class, 'complete']);
Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
Route::post('/orders/{id}/resume', [OrderController::class, 'resume']);
Route::get('/stock-movements', [StockMovementController::class, 'index']);