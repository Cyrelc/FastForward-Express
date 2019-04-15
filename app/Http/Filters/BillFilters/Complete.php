<?php

namespace App\Http\Filters\BillFilters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class Complete implements Filter {
    public function __invoke(Builder $query, $value, string $property) : Builder {
        if($value)
            return $query->where('percentage_complete', 1);
        return $query->where('percentage_complete', '<', 1);
    }
}

?>
