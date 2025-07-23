<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;

class WarehouseController extends Controller
{
    public function index(): Collection
    {
        return Warehouse::all();
    }
}
