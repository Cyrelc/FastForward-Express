<?php

namespace App\Http\Filters\BillFilters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class CustomFieldValue implements Filter {
    public function __invoke(Builder $query, $value, string $property) : Builder {
        return $query->where(function($query) use ($value) {
            $query->where('pickup_reference_value', 'like', '%' . $value . '%')
            ->orWhere('delivery_reference_value', 'like', '%' . $value . '%')
            ->orWhere('charge_reference_value', 'like', '%' . $value . '%');
        });
    }
}

?>
