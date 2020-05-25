<?php

namespace App\Http\Filters\BillFilters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class Dispatch implements Filter {
    public function __invoke(Builder $query, $value, string $property) : Builder {
        return $query->where(function($query) {
            $query->where('pickup_driver_id', null)
            ->orWhere('delivery_driver_id', null)
            ->orWhere('pickup_driver_commission', null)
            ->orWhere('delivery_driver_commission', null)
            ->orWhere('delivery_type', null)
            ->orWhere('time_dispatched', null)
            ->orWhere('time_picked_up', null)
            ->orWhere('time_delivered', null);
        });
    }
}

?>
