<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::insert([
            ['name' => 'Laptop', 'price' => 1000],
            ['name' => 'Phone', 'price' => 500],
            ['name' => 'Tablet', 'price' => 750],
            ['name' => 'Monitor', 'price' => 200],
        ]);
    }
}
