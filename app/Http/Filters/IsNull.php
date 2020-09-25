<?php

namespace App\Http\Filters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class IsNull implements Filter {
    public function __invoke(Builder $query, $value, string $property) : Builder {
        if($value === false)
            return $query->whereNull($property);
        else
            return $query->whereNotNull($property);
    }
}

?>
