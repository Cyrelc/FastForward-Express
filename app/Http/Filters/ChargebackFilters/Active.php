<?php

namespace App\Http\Filters\ChargebackFilters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class Active implements Filter {
    public function __invoke(Builder $query, $value, string $property) : Builder {
        return $query->where(function($query) {
            $query->where('continuous', 1)
                ->orWhere('count_remaining', '>', 0);
        });
    }
}

?>
