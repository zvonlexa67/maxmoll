<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductController extends Controller
{
    public function productsWithStock(): Collection
    {
        return Product::with(['stocks.warehouse'])->get();
    }
}
