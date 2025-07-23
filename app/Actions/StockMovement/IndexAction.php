<?php

namespace App\Actions\StockMovement;

use App\Models\StockMovement;

class IndexAction
{
    public function __invoke(array $data): array
    {
        $query = StockMovement::with([ 'product', 'warehouse' ]);

        if (isset($data[ 'warehouse_id' ])) {
            $query->where('warehouse_id', $data[ 'warehouse_id' ]);
        }

        if (isset($data[ 'product_id' ])) {
            $query->where('product_id', $data[ 'product_id' ]);
        }

        if (isset($data[ 'date_from' ])) {
            $query->whereDate('created_at', '>=', $data[ 'date_from' ]);
        }

        if (isset($data[ 'date_to' ])) {
            $query->whereDate('created_at', '<=', $data[ 'date_to' ]);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($data[ 'per_page' ], 10)
            ->toArray();
    }
}