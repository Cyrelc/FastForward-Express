<?php

namespace App\Http\Filters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class IsNull implements Filter {
    public function __invoke(Builder $query, $value, string $property) : Builder {
        if(filter_var($value, FILTER_VALIDATE_BOOLEAN))
            return $query->whereNotNull($property);
        else
            return $query->whereNull($property);
    }
}

?>
