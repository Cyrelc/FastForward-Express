<?php

namespace App\Http\Filters\BillFilters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class Invoiced implements Filter {
    public function __invoke(Builder $query, $value, string $property) : Builder {
        if($value)
            return $query->whereNotNull('invoice_id');
        return $query->whereNull('invoice_id');
    }
}

?>
