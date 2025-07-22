<?php

namespace App\Http\Controllers;

use App\Models\Product;
//use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function productsWithStock()
    {
        $products = Product::with(['stocks.warehouse'])->get();
        return $products;
    }
}
