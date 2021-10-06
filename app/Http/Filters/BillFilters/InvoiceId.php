<?php

namespace App\Http\Filters\BillFilters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class InvoiceId implements Filter {
    public function __invoke(Builder $query, $value, string $property) : Builder {
        if(is_array($value))
            return $query->whereIn('pickup_manifest_id', $value)
                ->orWhereIn('delivery_manifest_id', $value);
        return $query->where('pickup_manifest_id', $value)
            ->orWhere('delivery_manifest_id', $value);
    }
}

?>
