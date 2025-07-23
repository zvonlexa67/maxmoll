<?php

namespace App\Actions\Order;

use App\Models\Order;

class IndexAction
{
    public function __invoke(array $data): array
    {
        $query = Order::with([ 'items.product', 'warehouse' ]);

        if (isset($data[ 'status' ])) {
            $query->where('status', $data[ 'status' ]);
        }

        if (isset($data[ 'customer' ])) {
            // $query->where('customer', 'like', '%' . $data[ 'customer' ] . '%');
            $query->where('customer', 'like', "%{$data['customer']}%");
        }

        if (isset($data[ 'date_from' ])) {
            $query->whereDate('created_at', '>=', $data[ 'date_from' ]);
        }

        if (isset($data[ 'date_to' ])) {
            $query->whereDate('created_at', '<=', $data[ 'date_to' ]);
        }

        return $query->paginate($data[ 'per_page' ], 10)->toArray();
    }
}