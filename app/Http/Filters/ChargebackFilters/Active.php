<?php

namespace App\Http\Filters\ChargebackFilters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class Active implements Filter {
    public function __invoke(Builder $query, $value, string $property) : Builder {
        if(filter_var($value, FILTER_VALIDATE_BOOLEAN))
            return $query->where(function($query) {
                $query->where('continuous', 1)
                    ->orWhere('count_remaining', '>', 0);
            });
        else
            return $query->where(function($query) {
                $query->where('continuous', '!=', 1)
                    ->where('count_remaining', 0);
            });
    }
}

?>
