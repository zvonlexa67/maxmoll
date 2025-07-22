<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $table = 'warehouses';

    protected $fillable = [ 'name' ];

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
