<?php

namespace Database\Seeders;

use App\Models\Stock;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    public function run()
    {
        $products = Product::all();
        $warehouses = Warehouse::all();

        foreach ($warehouses as $warehouse) {
            foreach ($products as $product) {
                Stock::create([
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $product->id,
                    'stock' => rand(10, 100),
                ]);
            }
        }
    }
}
